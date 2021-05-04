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

namespace MultiSafepay\ConnectCore\Gateway\Command;

use Exception;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use MultiSafepay\Api\Transactions\CaptureRequest;
use MultiSafepay\Api\Transactions\Transaction;
use MultiSafepay\ConnectCore\Factory\SdkFactory;
use MultiSafepay\ConnectCore\Service\InvoiceService;
use MultiSafepay\ConnectCore\Util\AmountUtil;
use MultiSafepay\ConnectCore\Util\CaptureUtil;
use Psr\Http\Client\ClientExceptionInterface;

class Capture extends AbstractCommand
{
    public const CAPTURE_DATA_FIELD_NAME = "capture_data";

    /**
     * @var CaptureRequest
     */
    private $captureRequest;

    /**
     * Capture constructor.
     *
     * @param CaptureUtil $captureUtil
     * @param MessageManager $messageManager
     * @param SdkFactory $sdkFactory
     * @param AmountUtil $amountUtil
     * @param CaptureRequest $captureRequest
     * @param InvoiceService $invoiceService
     * @param array $data
     */
    public function __construct(
        CaptureUtil $captureUtil,
        MessageManager $messageManager,
        SdkFactory $sdkFactory,
        AmountUtil $amountUtil,
        CaptureRequest $captureRequest,
        InvoiceService $invoiceService,
        array $data = []
    ) {
        $this->captureRequest = $captureRequest;
        parent::__construct($captureUtil, $messageManager, $sdkFactory, $amountUtil, $invoiceService, $data);
    }

    /**
     * @param array $commandSubject
     * @return bool
     * @throws ClientExceptionInterface
     */
    public function execute(array $commandSubject): bool
    {
        $amount = (float)$commandSubject['amount'];
        /** @var InfoInterface $payment */
        $payment = $commandSubject['payment']->getPayment();
        /** @var OrderInterface $order */
        $order = $payment->getOrder();
        $orderIncrementId = $order->getIncrementId();
        $transactionManager = $this->getTransactionManagerByStoreId((int)$order->getStoreId());
        $transaction = $transactionManager->get($orderIncrementId);

        if (!($transaction && $transaction->getData())) {
            return false;
        }

        if (
        !$this->captureUtil->isManualCapturePossibleForAmount($transaction, round($amount * 100, 10))
        ) {
            throw new Exception(__('Payment capture amount is not valid, please try again.')->render());
        }

        $captureRequest = $this->captureRequest->addData(
            $this->prepareCaptureRequestData($amount, $order, $payment)
        );

        $response = $transactionManager->capture($orderIncrementId, $captureRequest)->getResponseData();

        if (!isset($response['transaction_id'], $response['order_id'])) {
            throw new Exception(__('Response API data is not valid.')->render());
        }

        $payment->setTransactionId($response['transaction_id']);
        $payment->setAdditionalInformation(
            self::CAPTURE_DATA_FIELD_NAME,
            array_merge(
                (array)$payment->getAdditionalInformation(self::CAPTURE_DATA_FIELD_NAME),
                [$this->prepareCaptureDataFromResponse($response, $amount)]
            )
        );

        return true;
    }

    /**
     * @param array $response
     * @param float $amount
     * @return array[]
     */
    private function prepareCaptureDataFromResponse(array $response, float $amount): array
    {
        return [
            'transaction_id' => $response['transaction_id'],
            'order_id' => $response['order_id'],
            'amount' => $amount,
        ];
    }

    /**
     * @param float $amount
     * @param OrderInterface $order
     * @param InfoInterface $payment
     * @return array
     */
    private function prepareCaptureRequestData(float $amount, OrderInterface $order, InfoInterface $payment): array
    {
        $invoice = $payment->getInvoice();

        return array_merge(
            [
                "amount" => round($this->amountUtil->getAmount($amount, $order) * 100, 10),
                "invoice_id" => $invoice ? $invoice->getIncrementId() : "",
            ],
            $this->captureStatusResolve($order, $payment)
        );
    }

    /**
     * @param OrderInterface $order
     * @param InfoInterface $payment
     * @return array
     */
    private function captureStatusResolve(OrderInterface $order, InfoInterface $payment): array
    {
        $shipment = $payment->getShipment();

        return [
            "new_order_status" => $shipment ? Transaction::SHIPPED : Transaction::COMPLETED,
            "carrier" => $order->getShippingDescription(),
            "reason" => $shipment ? "Shipped" : "Invoice created manually",
        ];
    }
}
