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

namespace MultiSafepay\ConnectCore\Service\Order;

use Magento\Sales\Api\Data\OrderInterface;
use MultiSafepay\Api\Transactions\Transaction as TransactionStatus;
use MultiSafepay\Api\Transactions\UpdateRequest;
use MultiSafepay\ConnectCore\Factory\SdkFactory;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\Exception\ApiException;
use Psr\Http\Client\ClientExceptionInterface;

class CancelMultisafepayOrderPretransaction
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var UpdateRequest
     */
    private $updateRequest;

    /**
     * @var SdkFactory
     */
    private $sdkFactory;

    /**
     * CancelMultisafepayOrderPretransaction constructor.
     *
     * @param UpdateRequest $updateRequest
     * @param Logger $logger
     * @param SdkFactory $sdkFactory
     */
    public function __construct(
        UpdateRequest $updateRequest,
        Logger $logger,
        SdkFactory $sdkFactory
    ) {
        $this->updateRequest = $updateRequest;
        $this->logger = $logger;
        $this->sdkFactory = $sdkFactory;
    }

    /**
     * @param OrderInterface $order
     * @return bool
     */
    public function execute(OrderInterface $order): bool
    {
        $orderId = $order->getIncrementId();
        $transactionManager = $this->sdkFactory->create((int)$order->getStoreId())->getTransactionManager();
        $updateRequest = $this->updateRequest->addData([
            "status" => TransactionStatus::CANCELLED,
            "exclude_order" => 1,
        ]);

        try {
            $transactionManager->update($orderId, $updateRequest)->getResponseData();
            $this->logger->logInfoForOrder(
                $orderId,
                'MultiSafepay pretransaction was canceled..'
            );
        } catch (ApiException $apiException) {
            $this->logger->logUpdateRequestApiException($orderId, $apiException);

            return false;
        } catch (ClientExceptionInterface $clientException) {
            $this->logger->logClientException($orderId, $clientException);

            return false;
        }

        return true;
    }
}
