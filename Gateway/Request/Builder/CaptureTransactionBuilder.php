<?php
/**
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is provided with Magento in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * Copyright © 2021 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 *
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Gateway\Request\Builder;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Exception\CouldNotInvoiceException;
use Magento\SalesSequence\Model\Manager;
use Magento\Store\Model\Store;
use MultiSafepay\Api\Transactions\CaptureRequest;
use MultiSafepay\Api\Transactions\Transaction;
use MultiSafepay\ConnectCore\Factory\SdkFactory;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Util\AmountUtil;
use MultiSafepay\ConnectCore\Util\CaptureUtil;
use MultiSafepay\ConnectCore\Util\ShipmentUtil;
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
     * CaptureTransactionBuilder constructor.
     *
     * @param AmountUtil $amountUtil
     * @param CaptureUtil $captureUtil
     * @param SdkFactory $sdkFactory
     * @param CaptureRequest $captureRequest
     * @param ShipmentUtil $shipmentUtil
     * @param Manager $sequenceManager
     * @param Logger $logger
     */
    public function __construct(
        AmountUtil $amountUtil,
        CaptureUtil $captureUtil,
        SdkFactory $sdkFactory,
        CaptureRequest $captureRequest,
        ShipmentUtil $shipmentUtil,
        Manager $sequenceManager,
        Logger $logger
    ) {
        $this->amountUtil = $amountUtil;
        $this->captureUtil = $captureUtil;
        $this->sdkFactory = $sdkFactory;
        $this->captureRequest = $captureRequest;
        $this->shipmentUtil = $shipmentUtil;
        $this->sequenceManager = $sequenceManager;
        $this->logger = $logger;
    }

    /**
     * @param array $buildSubject
     * @return array
     * @throws CouldNotInvoiceException
     * @throws LocalizedException
     */
    public function build(array $buildSubject): array
    {
        $paymentDataObject = SubjectReader::readPayment($buildSubject);
        $amount = (float)SubjectReader::readAmount($buildSubject);

        if ($amount <= 0) {
            throw new CouldNotInvoiceException(
                __('Invoices with 0 or negative amount can not be processed. Please set a different amount')
            );
        }

        $payment = $paymentDataObject->getPayment();
        /** @var OrderInterface $order */
        $order = $payment->getOrder();
        $orderIncrementId = $order->getIncrementId();
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
     */
    private function validateManualCapture(float $invoiceAmount, string $orderIncrementId, int $storeId): void
    {
        try {
            $transactionManager = $this->sdkFactory->create($storeId)->getTransactionManager();
            $transaction = $transactionManager->get($orderIncrementId)->getData();
        } catch (ClientExceptionInterface $clientException) {
            $this->logger->logExceptionForOrder($orderIncrementId, $clientException);

            throw new CouldNotInvoiceException(__($clientException->getMessage()));
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
            $exceptionMessage = __('Manual payment capture amount is can\'t be processed,  please try again.');
            $this->logger->logInfoForOrder($orderIncrementId, $exceptionMessage->render());

            throw new CouldNotInvoiceException($exceptionMessage);
        }
    }

    /**
     * @param float $amount
     * @param OrderInterface $order
     * @param InfoInterface $payment
     * @return array
     * @throws LocalizedException
     */
    private function prepareCaptureRequestData(float $amount, OrderInterface $order, InfoInterface $payment): array
    {
        $invoice = $payment->getInvoice() ?: $order->getInvoiceCollection()->getLastItem();

        if ($invoice && !$invoice->getIncrementId()) {
            $invoice->setIncrementId(
                $this->sequenceManager->getSequence(
                    $invoice->getEntityType(),
                    $order->getStoreId()
                )->getNextValue()
            );
        }

        $shipment = $payment->getShipment();
        $invoiceIncrementId = $invoice ? $invoice->getIncrementId() : "";
        $result = [
            "amount" => round($this->amountUtil->getAmount($amount, $order) * 100, 10),
            "invoice_id" => $invoiceIncrementId,
            "new_order_status" => $shipment ? Transaction::SHIPPED : Transaction::COMPLETED,
        ];

        if ($invoiceIncrementId) {
            $result['new_order_id'] = $order->getIncrementId() . '_' . $invoiceIncrementId;
        }

        return $shipment ? array_merge($result, $this->shipmentUtil->getShipmentApiRequestData($order, $shipment))
            : $result;
    }
}
