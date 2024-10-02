<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is provided with Magento in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * See DISCLAIMER.md for disclaimer details.
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Gateway\Request\Builder;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment;
use Magento\SalesSequence\Model\Manager;
use Magento\Sales\Exception\CouldNotInvoiceException;
use Magento\Store\Model\Store;
use MultiSafepay\Api\Transactions\CaptureRequest;
use MultiSafepay\Api\Transactions\Transaction;
use MultiSafepay\ConnectCore\Factory\SdkFactory;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Util\AmountUtil;
use MultiSafepay\ConnectCore\Util\CaptureUtil;
use MultiSafepay\ConnectCore\Util\JsonHandler;
use MultiSafepay\ConnectCore\Util\ShipmentUtil;
use MultiSafepay\Exception\ApiException;
use Psr\Http\Client\ClientExceptionInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CaptureTransactionBuilder implements BuilderInterface
{
    /**
     * @var AmountUtil
     */
    private $amountUtil;

    /**
     * @var CaptureUtil
     */
    private $captureUtil;

    /**
     * @var SdkFactory
     */
    private $sdkFactory;

    /**
     * @var CaptureRequest
     */
    private $captureRequest;

    /**
     * @var ShipmentUtil
     */
    private $shipmentUtil;

    /**
     * @var Manager
     */
    private $sequenceManager;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var JsonHandler
     */
    private $jsonHandler;

    /**
     * CaptureTransactionBuilder constructor.
     *
     * @param AmountUtil $amountUtil
     * @param CaptureUtil $captureUtil
     * @param SdkFactory $sdkFactory
     * @param CaptureRequest $captureRequest
     * @param ShipmentUtil $shipmentUtil
     * @param Manager $sequenceManager
     * @param Logger $logger
     * @param JsonHandler $jsonHandler
     */
    public function __construct(
        AmountUtil $amountUtil,
        CaptureUtil $captureUtil,
        SdkFactory $sdkFactory,
        CaptureRequest $captureRequest,
        ShipmentUtil $shipmentUtil,
        Manager $sequenceManager,
        Logger $logger,
        JsonHandler $jsonHandler
    ) {
        $this->amountUtil = $amountUtil;
        $this->captureUtil = $captureUtil;
        $this->sdkFactory = $sdkFactory;
        $this->captureRequest = $captureRequest;
        $this->shipmentUtil = $shipmentUtil;
        $this->sequenceManager = $sequenceManager;
        $this->logger = $logger;
        $this->jsonHandler = $jsonHandler;
    }

    /**
     * @param array $buildSubject
     * @return array
     * @throws CouldNotInvoiceException
     * @throws LocalizedException
     * @throws Exception
     */
    public function build(array $buildSubject): array
    {
        $paymentDataObject = SubjectReader::readPayment($buildSubject);
        $amount = (float)SubjectReader::readAmount($buildSubject);

        /** @var Payment $payment */
        $payment = $paymentDataObject->getPayment();

        $order = $payment->getOrder();
        $orderIncrementId = $order->getIncrementId();

        if ($amount <= 0) {
            $exceptionMessage =
                __('Invoices with 0 or negative amount can not be processed. Please set a different amount');
            $this->logger->logInfoForOrder($orderIncrementId, $exceptionMessage->render());

            throw new CouldNotInvoiceException($exceptionMessage);
        }

        $storeId = (int)$order->getStoreId();
        $this->validateManualCapture($amount, $orderIncrementId, $storeId);

        return [
            'payload' => $this->captureRequest->addData(
                $this->prepareCaptureRequestData($amount, $order, $payment)
            ),
            'order_id' => $orderIncrementId,
            Store::STORE_ID => $storeId,
        ];
    }

    /**
     * @param float $invoiceAmount
     * @param string $orderIncrementId
     * @param int $storeId
     * @throws CouldNotInvoiceException
     * @throws Exception
     */
    private function validateManualCapture(float $invoiceAmount, string $orderIncrementId, int $storeId): void
    {
        try {
            $transactionManager = $this->sdkFactory->create($storeId)->getTransactionManager();
            $transaction = $transactionManager->get($orderIncrementId)->getData();
        } catch (ClientExceptionInterface | ApiException $exception) {
            $this->logger->logExceptionForOrder($orderIncrementId, $exception);

            throw new CouldNotInvoiceException(__($exception->getMessage()));
        }

        $exceptionMessage = __('Manual MultiSafepay online capture can\'t be processed for non manual capture orders');

        if (!$this->captureUtil->isCaptureManualTransaction($transaction)) {
            $this->logger->logInfoForOrder($orderIncrementId, $exceptionMessage->render());

            throw new CouldNotInvoiceException($exceptionMessage);
        }

        if ($this->captureUtil->isCaptureManualReservationExpired($transaction)) {
            $exceptionMessage = __('Reservation has been expired for current online capture.');
            $this->logger->logInfoForOrder($orderIncrementId, $exceptionMessage->render());

            throw new CouldNotInvoiceException($exceptionMessage);
        }

        if (!$this->captureUtil->isManualCapturePossibleForAmount($transaction, $invoiceAmount)) {
            $exceptionMessage = __('Manual payment capture amount can\'t be processed,  please try again.');
            $this->logger->logInfoForOrder($orderIncrementId, $exceptionMessage->render());

            throw new CouldNotInvoiceException($exceptionMessage);
        }
    }

    /**
     * @param float $amount
     * @param Order $order
     * @param Payment $payment
     * @return array
     * @throws CouldNotInvoiceException
     * @throws LocalizedException
     * @throws Exception
     */
    private function prepareCaptureRequestData(float $amount, Order $order, Payment $payment): array
    {
        /** @var Invoice $invoice */
        $invoice = $payment->getInvoice() ?: $order->getInvoiceCollection()->getLastItem();
        $orderIncrementId = $order->getIncrementId();

        if (!$invoice->getData()) {
            $exceptionMessage = __('Something went wrong. Invoice was not found.');
            $this->logger->logInfoForOrder($orderIncrementId, $exceptionMessage->render());

            throw new CouldNotInvoiceException($exceptionMessage);
        }

        if (!$invoice->getIncrementId()) {
            $invoice->setIncrementId(
                $this->sequenceManager->getSequence(
                    $invoice->getEntityType(),
                    $order->getStoreId()
                )->getNextValue()
            );
        }

        $shipment = $payment->getShipment();
        $invoiceIncrementId = $invoice->getIncrementId();
        $result = [
            "amount" => round($this->amountUtil->getAmount($amount, $order) * 100, 10),
            "invoice_id" => $invoiceIncrementId,
            "new_order_status" => $shipment ? Transaction::SHIPPED : Transaction::COMPLETED,
        ];

        if ($invoiceIncrementId
            && ((float)$invoice->getBaseGrandTotal() !== (float)$order->getBaseGrandTotal())
        ) {
            $result['new_order_id'] = $orderIncrementId . '_' . $invoiceIncrementId;
        }

        $captureRequestData = $shipment
            ? array_merge($result, $this->shipmentUtil->getShipmentApiRequestData($order, $shipment)) : $result;

        $this->logger->logInfoForOrder(
            $orderIncrementId,
            __('Prepared capture request data: %1', $this->jsonHandler->convertToPrettyJSON($captureRequestData))
                ->render(),
            Logger::DEBUG
        );

        return $captureRequestData;
    }
}
