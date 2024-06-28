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

use Exception;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Sales\Exception\CouldNotRefundException;
use Magento\Store\Model\Store;
use MultiSafepay\ConnectCore\Factory\SdkFactory;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Util\JsonHandler;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\Exception\InvalidApiKeyException;
use Psr\Http\Client\ClientExceptionInterface;

class RefundClient implements ClientInterface
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
     * @var JsonHandler
     */
    private $jsonHandler;

    /**
     * RefundClient constructor.
     *
     * @param SdkFactory $sdkFactory
     * @param Logger $logger
     */
    public function __construct(
        SdkFactory $sdkFactory,
        Logger $logger,
        JsonHandler $jsonHandler
    ) {
        $this->sdkFactory = $sdkFactory;
        $this->logger = $logger;
        $this->jsonHandler = $jsonHandler;
    }

    /**
     * @param TransferInterface $transferObject
     * @return array
     * @throws Exception
     */
    public function placeRequest(TransferInterface $transferObject): array
    {
        $request = $transferObject->getBody();
        $orderId = (string)$request['order_id'];

        try {
            $transactionManager = $this->sdkFactory->create($request[Store::STORE_ID])->getTransactionManager();
            $transaction = $transactionManager->get($orderId);

            $this->logger->logRefundRequest(
                $orderId,
                $this->jsonHandler->convertToJSON($request['payload']->getData())
            );

            return $transactionManager->refund($transaction, $request['payload'], $orderId)->getResponseData();
        } catch (InvalidApiKeyException $invalidApiKeyException) {
            $this->logger->logInvalidApiKeyException($invalidApiKeyException);

            throw new CouldNotRefundException(__($invalidApiKeyException->getMessage()));
        } catch (ApiException $apiException) {
            $this->logger->logExceptionForOrder($orderId, $apiException);

            throw new CouldNotRefundException(__($apiException->getMessage()));
        } catch (ClientExceptionInterface $clientException) {
            $this->logger->logClientException($orderId, $clientException);

            throw new CouldNotRefundException(__($clientException->getMessage()));
        }
    }
}
