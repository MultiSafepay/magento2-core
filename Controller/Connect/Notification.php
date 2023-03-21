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
 * Copyright © 2022 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 *
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Controller\Connect;

use Exception;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\Exception\NoSuchEntityException;
use MultiSafepay\Client\Client;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Util\OrderUtil;
use MultiSafepay\ConnectCore\Service\GetNotification;
use MultiSafepay\ConnectCore\Service\PostNotification;

class Notification extends Action implements CsrfAwareActionInterface
{

    /**
     * @var PostNotification
     */
    private $postNotification;

    /**
     * @var GetNotification
     */
    private $getNotification;

    /**
     * @var OrderUtil
     */
    private $orderUtil;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * Notification constructor.
     *
     * @param PostNotification $postNotification
     * @param GetNotification $getNotification
     * @param OrderUtil $orderUtil
     * @param Logger $logger
     * @param Context $context
     */
    public function __construct(
        PostNotification $postNotification,
        GetNotification $getNotification,
        OrderUtil $orderUtil,
        Logger $logger,
        Context $context
    ) {
        $this->postNotification = $postNotification;
        $this->getNotification = $getNotification;
        $this->orderUtil = $orderUtil;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * @param RequestInterface $request
     * @return InvalidRequestException|null
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @param RequestInterface $request
     * @return bool|null
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * @inheritDoc
     *
     * @throws Exception
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();

        if (!isset($params['transactionid'], $params['timestamp'])) {
            return $this->getResponse()->setContent('ng: Missing transaction id or timestamp');
        }

        $orderIncrementId = $params['transactionid'];

        try {
            // Try to retrieve store id from order if it is not in request parameters
            $storeId = $this->getStoreId($params);
        } catch (NoSuchEntityException $noSuchEntityException) {
            $this->logger->logExceptionForOrder($orderIncrementId, $noSuchEntityException);

            $message = $noSuchEntityException->getMessage();

            $this->logger->logInfoForOrder($orderIncrementId, 'Webhook response set: ' . $message);

            return $this->getResponse()->setContent($message);
        }

        $response = ['success' => false, 'message' => 'ng: no incoming POST or GET notification detected'];

        if ($this->getRequest()->getMethod() === Client::METHOD_POST) {
            $response = $this->postNotification->execute(
                $this->getRequest(),
                $storeId
            );
        }

        // Process GET notification when POST notification failed
        if ($response['success'] === false && isset($response['message'])) {
            if ($response['message'] === 'Unable to verify POST notification' ||
                $response['message'] === 'Exception occurred when verifying POST notification') {
                $response = $this->getNotification->execute($orderIncrementId, $storeId);
            }
        }

        if ($this->getRequest()->getMethod() === Client::METHOD_GET) {
            $response = $this->getNotification->execute($orderIncrementId, $storeId);
        }

        return $this->getResponse()->setContent($this->processResponse($response));
    }

    /**
     * Process the response retrieved from the GET/POST execution processes
     *
     * @param array $response
     * @return string
     */
    private function processResponse(array $response): string
    {
        if ($response['success']) {
            return 'ok';
        }

        return 'ng: ' . ($response['message'] ?? 'reason unknown');
    }

    /**
     * Get the store id which is to be used for the transaction requests
     *
     * @param array $params
     * @return int
     * @throws NoSuchEntityException
     */
    private function getStoreId(array $params): int
    {
        if (isset($params['store_id'])) {
            return (int)$params['store_id'];
        }

        $order = $this->orderUtil->getOrderByIncrementId($params['transactionid']);
        return (int)$order->getStoreId();
    }
}
