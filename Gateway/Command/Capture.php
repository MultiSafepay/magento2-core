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
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use MultiSafepay\Api\Transactions\CaptureRequest;
use MultiSafepay\ConnectCore\Factory\SdkFactory;
use MultiSafepay\ConnectCore\Service\InvoiceService;
use MultiSafepay\ConnectCore\Util\AmountUtil;
use MultiSafepay\ConnectCore\Util\CaptureUtil;
use Psr\Http\Client\ClientExceptionInterface;

class Capture extends AbstractCommand
{
    /**
     * @var RequestInterface
     */
    private $request;

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
     * @param RequestInterface $request
     * @param CaptureRequest $captureRequest
     * @param InvoiceService $invoiceService
     * @param array $data
     */
    public function __construct(
        CaptureUtil $captureUtil,
        MessageManager $messageManager,
        SdkFactory $sdkFactory,
        AmountUtil $amountUtil,
        RequestInterface $request,
        CaptureRequest $captureRequest,
        InvoiceService $invoiceService,
        array $data = []
    ) {
        $this->request = $request;
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
        $requestData = $this->request->getPost();
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

        if ($this->captureUtil->isManualCapturePossibleForAmount($transaction, $amount)) {
            if (isset($requestData['tracking']) && !$this->isTrackingInfoValid($requestData['tracking'])) {
                return false;
            }

            $captureRequest = $this->captureRequest->addData(
                $this->prepareCaptureRequestData($amount, $order, $payment)
            );

            $response = $transactionManager->capture($orderIncrementId, $captureRequest)->getResponseData();

            //if (!$response->getIsSuccessful()) {
            //    $errorMessage = __('Payment capture failed, please try again.');
            //    if ($response->getErrorCode() === 'CAPTURE_NOT_ALLOWED') {
            //        $errorMessage = __('Payment capture not allowed.');
            //    }
            //
            //    $errorMessage = $this->getFullErrorMessage($response, $errorMessage, 'capture');
            //    throw new Exception($errorMessage);
            //}

            if (!isset($response['transaction_id'])) {
                return false;
            }

            $payment->setTransactionId($response['transaction_id']);

            return true;
        }

        return false;
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

        return [
            "amount" => round($this->amountUtil->getAmount($amount, $order) * 100, 10),
            "new_order_status" => "completed",
            "invoice_id" => $invoice ? $invoice->getIncrementId() : "",
            "carrier" => $order->getShippingDescription(),
            "reason" => "Shipped",
            "memo" => "",
        ];
    }

    /**
     * vaidate tracking info
     *
     * @param array $trackingInfo
     * @return bool
     */
    private function isTrackingInfoValid($trackingInfo)
    {
        foreach ($trackingInfo as $var) {
            if (empty($var['carrier_code']) || empty($var['title']) || empty($var['number'])) {
                return false;
            }
        }

        return true;
    }
}
