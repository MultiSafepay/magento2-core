<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is provided with Magento in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * Copyright © 2022 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Service;

use Exception;
use Laminas\Http\Request;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Util\JsonHandler;
use MultiSafepay\ConnectCore\Util\OrderUtil;
use MultiSafepay\ConnectCore\Service\Process\DelayExecution;
use MultiSafepay\ConnectCore\Service\Transaction\StatusOperationManager;
use MultiSafepay\Util\Notification as SdkNotification;

class PostNotification
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var JsonHandler
     */
    private $jsonHandler;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var OrderUtil
     */
    private $orderUtil;

    /**
     * @var DelayExecution
     */
    private $delayExecution;

    /**
     * @var StatusOperationManager
     */
    private $statusOperationManager;

    /**
     * PostNotification constructor.
     *
     * @param Config $config
     * @param JsonHandler $jsonHandler
     * @param Logger $logger
     * @param OrderUtil $orderUtil
     * @param DelayExecution $delayExecution
     * @param StatusOperationManager $statusOperationManager
     */
    public function __construct(
        Config $config,
        JsonHandler $jsonHandler,
        Logger $logger,
        OrderUtil $orderUtil,
        DelayExecution $delayExecution,
        StatusOperationManager $statusOperationManager
    ) {
        $this->config = $config;
        $this->jsonHandler = $jsonHandler;
        $this->logger = $logger;
        $this->orderUtil = $orderUtil;
        $this->delayExecution = $delayExecution;
        $this->statusOperationManager = $statusOperationManager;
    }

    /**
     * Execute the POST notification process
     *
     * @param Request $request
     * @param int $storeId
     * @return array
     * @throws Exception
     */
    public function execute(Request $request, int $storeId): array
    {
        $requestBody = $request->getContent();
        $authHeader = (string)$request->getHeader('Auth');

        $this->logger->logPOSTNotification($request->getHeaders()->toString(), $requestBody);

        $transaction = $this->jsonHandler->readJSON($requestBody);
        $orderIncrementId = $transaction['order_id'] ?? '';

        // Validate the POST notification
        try {
            if (!SdkNotification::verifyNotification($requestBody, $authHeader, $this->config->getApiKey($storeId))) {
                $message = 'Unable to verify POST notification';

                $this->logger->logInfoForNotification($orderIncrementId, $message, $transaction);

                return ['success' => false, 'message' => $message];
            }
        } catch (Exception $exception) {
            $this->logger->logNotificationException($orderIncrementId, $transaction, $exception);

            return ['success' => false, 'message' => 'Exception occurred when verifying POST notification'];
        }

        $this->delayExecution->execute($transaction['status'] ?? '');

        try {
            /** @var Order $order */
            $order = $this->orderUtil->getOrderByIncrementId($orderIncrementId);
        } catch (NoSuchEntityException $noSuchEntityException) {
            $this->logger->logExceptionForOrder($orderIncrementId, $noSuchEntityException);

            return ['success' => false, 'message' => sprintf('%1$s', $noSuchEntityException->getMessage())];
        }

        return $this->statusOperationManager->processStatusOperation($order, $transaction);
    }
}
