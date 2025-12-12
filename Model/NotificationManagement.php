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

namespace MultiSafepay\ConnectCore\Model;

use Exception;
use Magento\Framework\Webapi\Rest\Request;
use MultiSafepay\Client\Client;
use MultiSafepay\ConnectCore\Api\NotificationInterface;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Service\GetNotification;
use MultiSafepay\ConnectCore\Service\PostNotification;
use MultiSafepay\ConnectCore\Util\OrderUtil;

class NotificationManagement implements NotificationInterface
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
     * @var Request
     */
    private $request;

    /**
     * NotificationManagement constructor.
     *
     * @param PostNotification $postNotification
     * @param GetNotification $getNotification
     * @param OrderUtil $orderUtil
     * @param Logger $logger
     * @param Request $request
     */
    public function __construct(
        PostNotification $postNotification,
        GetNotification $getNotification,
        OrderUtil $orderUtil,
        Logger $logger,
        Request $request
    ) {
        $this->postNotification = $postNotification;
        $this->getNotification = $getNotification;
        $this->orderUtil = $orderUtil;
        $this->logger = $logger;
        $this->request = $request;
    }

    /**
     * Process notification from MultiSafepay
     *
     * @return string
     * @throws Exception
     */
    public function process(): string
    {
        $transactionId = $this->request->getParam('transactionid') ?? '';
        $timestamp = $this->request->getParam('timestamp') ?? '';
        $storeId = $this->request->getParam('store_id') ?? null;
        $payloadType = $this->request->getParam('payload_type') ?? '';

        $validationError = $this->validateParameters($transactionId, $timestamp);

        if ($validationError) {
            return $validationError;
        }

        if ($this->isPretransaction($payloadType, $transactionId)) {
            return 'ok';
        }

        if ($storeId === null) {
            try {
                $order = $this->orderUtil->getOrderByIncrementId($transactionId);
                $storeId = (int)$order->getStoreId();
            } catch (Exception $e) {
                $this->logger->logExceptionForOrder($transactionId, $e);
                return 'ng: ' . $e->getMessage();
            }
        }

        $response = $this->handleNotification($transactionId, (int)$storeId);

        return $this->processResponse($response);
    }

    /**
     * Validate required parameters
     *
     * @param string $transactionId
     * @param string $timestamp
     * @return string|null
     */
    private function validateParameters(string $transactionId, string $timestamp): ?string
    {
        if (!$transactionId) {
            return 'ng: transactionId is required';
        }

        if (!$timestamp) {
            return 'ng: timestamp is required';
        }

        return null;
    }

    /**
     * Check if the notification is a pretransaction
     *
     * @param string $payloadType
     * @param string $transactionId
     * @return bool
     * @throws Exception
     */
    private function isPretransaction(string $payloadType, string $transactionId): bool
    {
        if ($payloadType === 'pretransaction') {
            $this->logger->logInfoForNotification($transactionId, 'pretransaction, skipping', []);

            return true;
        }

        return false;
    }

    /**
     * Handle notification based on request method
     *
     * @param string $transactionId
     * @param int $storeId
     * @return array
     * @throws Exception
     */
    private function handleNotification(string $transactionId, int $storeId): array
    {
        $method = $this->request->getMethod();

        if ($method === Client::METHOD_POST) {
            return $this->handlePostNotification($transactionId, $storeId);
        }

        if ($method === Client::METHOD_GET) {
            return $this->getNotification->execute($transactionId, $storeId);
        }

        return ['success' => false, 'message' => 'ng: no incoming POST or GET notification detected'];
    }

    /**
     * Handle POST notification with fallback to GET if necessary
     *
     * @param string $transactionId
     * @param int $storeId
     * @return array
     * @throws Exception
     */
    private function handlePostNotification(string $transactionId, int $storeId): array
    {
        $response = $this->postNotification->execute($this->request, $storeId);

        if ($this->shouldFallbackToGet($response)) {
            return $this->getNotification->execute($transactionId, $storeId);
        }

        return $response;
    }

    /**
     * Check if we should fallback to GET notification if the POST verification failed
     *
     * @param array $response
     * @return bool
     */
    private function shouldFallbackToGet(array $response): bool
    {
        if ($response['success'] || !isset($response['message'])) {
            return false;
        }

        return in_array($response['message'], [
            'Unable to verify POST notification',
            'Exception occurred when verifying POST notification'
        ]);
    }

    /**
     * Process the response and return the message
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
}
