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

namespace MultiSafepay\ConnectCore\Gateway\Http\Client;

use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Store\Model\Store;
use MultiSafepay\ConnectCore\Factory\SdkFactory;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\Exception\ApiException;
use Psr\Http\Client\ClientExceptionInterface;

class CancelClient implements ClientInterface
{
    /**
     * @var SdkFactory
     */
    private $sdkFactory;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * CancelClient constructor.
     *
     * @param SdkFactory $sdkFactory
     * @param Logger $logger
     */
    public function __construct(
        SdkFactory $sdkFactory,
        Logger $logger
    ) {
        $this->sdkFactory = $sdkFactory;
        $this->logger = $logger;
    }

    /**
     * @param TransferInterface $transferObject
     * @return array|null
     */
    public function placeRequest(TransferInterface $transferObject): ?array
    {
        $request = $transferObject->getBody();
        $orderId = $request['order_id'] ?? '';

        if (!isset($request['payload'])) {
            $this->logger->logInfoForOrder(
                $orderId,
                'Transaction wasn\'t cancelled, request because payload doesn\'t exist'
            );

            return null;
        }

        $responseData = [];

        try {
            $responseData = $this->sdkFactory->create($request[Store::STORE_ID] ?? null)->getTransactionManager()
                ->captureReservationCancel($orderId, $request['payload'])->getResponseData();
        } catch (ClientExceptionInterface $clientException) {
            $this->logger->logClientException($orderId, $clientException);
        } catch (ApiException $apiException) {
            $this->logger->logExceptionForOrder($orderId, $apiException);
        }

        return array_merge($responseData, $request);
    }
}
