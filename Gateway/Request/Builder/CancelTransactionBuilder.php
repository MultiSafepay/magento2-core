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

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\Store;
use MultiSafepay\Api\Transactions\CaptureRequest;
use MultiSafepay\Api\Transactions\Transaction;
use MultiSafepay\ConnectCore\Factory\SdkFactory;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Util\CaptureUtil;
use MultiSafepay\ConnectCore\Util\PaymentMethodUtil;
use MultiSafepay\Exception\ApiException;
use Psr\Http\Client\ClientExceptionInterface;

class CancelTransactionBuilder implements BuilderInterface
{
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
     * @var Logger
     */
    private $logger;

    /**
     * @var PaymentMethodUtil
     */
    private $paymentMethodUtil;

    /**
     * CancelTransactionBuilder constructor.
     *
     * @param CaptureUtil $captureUtil
     * @param SdkFactory $sdkFactory
     * @param CaptureRequest $captureRequest
     * @param Logger $logger
     * @param PaymentMethodUtil $paymentMethodUtil
     */
    public function __construct(
        CaptureUtil $captureUtil,
        SdkFactory $sdkFactory,
        CaptureRequest $captureRequest,
        Logger $logger,
        PaymentMethodUtil $paymentMethodUtil
    ) {
        $this->captureUtil = $captureUtil;
        $this->sdkFactory = $sdkFactory;
        $this->captureRequest = $captureRequest;
        $this->logger = $logger;
        $this->paymentMethodUtil = $paymentMethodUtil;
    }

    /**
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject): array
    {
        /** @var OrderInterface $order */
        $order = SubjectReader::readPayment($buildSubject)->getPayment()->getOrder();
        $orderIncrementId = $order->getIncrementId();
        $storeId = (int)$order->getStoreId();
        $result = [
            'order_id' => $orderIncrementId,
            Store::STORE_ID => $storeId,
        ];

        try {
            if ($this->paymentMethodUtil->isMultisafepayOrder($order)
                && $this->captureUtil->isCaptureManualPayment($order->getPayment())
            ) {
                $transaction = $this->sdkFactory->create($storeId)
                    ->getTransactionManager()
                    ->get($orderIncrementId)
                    ->getData();

                if ($this->captureUtil->isCaptureManualTransaction($transaction)) {
                    if ($this->captureUtil->isCaptureManualReservationExpired($transaction)) {
                        $this->logger->logInfoForOrder($orderIncrementId, 'Capture reservation is expired.');

                        return $result;
                    }

                    $captureRequest = $this->captureRequest->addData(
                        [
                            "status" => Transaction::CANCELLED,
                            "reason" => "Order cancelled",
                        ]
                    );
                    $result['payload'] = $captureRequest;

                    return $result;
                }
            }
        } catch (ClientExceptionInterface $clientException) {
            $this->logger->logExceptionForOrder($orderIncrementId, $clientException);
        } catch (ApiException $apiException) {
            $this->logger->logExceptionForOrder($orderIncrementId, $apiException);
        }

        return $result;
    }
}
