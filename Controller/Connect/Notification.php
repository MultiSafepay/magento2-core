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
use Laminas\Http\Request;
use Laminas\Http\Response;
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
        $response = $this->checkParams($params);

        /** @var Response $httpResponse */
        $httpResponse = $this->getResponse();

        /** @var Request $httpRequest */
        $httpRequest = $this->getRequest();

        if ($response !== null) {
            return $httpResponse->setContent($response);
        }

        $orderIncrementId = $params['transactionid'];

        try {
            // Try to retrieve store id from order if it is not in request parameters
            $storeId = $this->getStoreId($params);
        } catch (NoSuchEntityException $noSuchEntityException) {
            $this->logger->logExceptionForOrder($orderIncrementId, $noSuchEntityException);

            $message = $noSuchEntityException->getMessage();

            $this->logger->logInfoForOrder($orderIncrementId, 'Webhook response set: ' . $message);

            return $httpResponse->setContent($message);
        }

        $response = ['success' => false, 'message' => 'ng: no incoming POST or GET notification detected'];

        if ($httpRequest->getMethod() === Client::METHOD_POST) {
            $response = $this->postNotification->execute(
                $httpRequest,
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

        if ($httpRequest->getMethod() === Client::METHOD_GET) {
            $response = $this->getNotification->execute($orderIncrementId, $storeId);
        }

        return $httpResponse->setContent($this->processResponse($response));
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

    /**
     * Check if it is a pretransaction
     *
     * @param array $params
     * @return bool
     * @throws Exception
     */
    private function isPreTransaction(array $params): bool
    {
        if (isset($params['payload_type']) && $params['payload_type'] === 'pretransaction') {
            $this->logger->logInfoForNotification($params['transactionid'], 'pretransaction, skipping', []);

            return true;
        }

        return false;
    }

    /**
     * Check if the request has the required parameters
     *
     * @param array $params
     * @return bool
     */
    private function hasRequiredParams(array $params): bool
    {
        if (isset($params['transactionid'], $params['timestamp'])) {
            return true;
        }

        return false;
    }

    /**
     * Check the params and return the appropriate response
     *
     * @param array $params
     * @return string|null
     * @throws Exception
     */
    private function checkParams(array $params): ?string
    {
        if ($this->isPreTransaction($params)) {
            return 'ok';
        }

        if (!$this->hasRequiredParams($params)) {
            return 'ng: Missing transaction id or timestamp';
        }

        return null;
    }
}
