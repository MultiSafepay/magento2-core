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
use MultiSafepay\ConnectCore\Factory\SdkFactory;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Util\JsonHandler;
use MultiSafepay\ConnectCore\Util\RefundUtil;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\Exception\InvalidApiKeyException;
use Psr\Http\Client\ClientExceptionInterface;

class ShoppingCartRefundClient implements ClientInterface
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
     * @var RefundUtil
     */
    private $refundUtil;

    /**
     * @var JsonHandler
     */
    private $jsonHandler;

    /**
     * ShoppingCartRefundClient constructor.
     *
     * @param SdkFactory $sdkFactory
     * @param Logger $logger
     * @param RefundUtil $refundUtil
     * @param JsonHandler $jsonHandler
     */
    public function __construct(
        SdkFactory $sdkFactory,
        Logger $logger,
        RefundUtil $refundUtil,
        JsonHandler $jsonHandler
    ) {
        $this->sdkFactory = $sdkFactory;
        $this->logger = $logger;
        $this->refundUtil = $refundUtil;
        $this->jsonHandler = $jsonHandler;
    }

    /**
     * Place the refund request
     *
     * @param TransferInterface $transferObject
     * @return array
     * @throws Exception
     */
    public function placeRequest(TransferInterface $transferObject): array
    {
        $request = $transferObject->getBody();

        $orderId = (string)$request['order_id'];
        $storeId = $request['store_id'];
        $transaction = $request['transaction'];

        try {
            $transactionManager = $this->sdkFactory->create($storeId)->getTransactionManager();
            $refundRequest = $transactionManager->createRefundRequest($transaction);
            $refundRequest->addDescription($this->refundUtil->buildDescription($orderId, $storeId));

            foreach ($request['items'] as $refundItem) {
                $refundRequest->getCheckoutData()->refundByMerchantItemId(
                    $refundItem['merchant_item_id'],
                    $refundItem['quantity']
                );
            }

            $adjustmentAmount = $request['adjustment'];

            if ($adjustmentAmount && $adjustmentAmount !== 0.0) {
                $refundRequest->getCheckoutData()->addItem($this->refundUtil->buildAdjustment($request));
            }

            if (!empty($request['shipping'])) {
                $refundRequest->getCheckoutData()->addItem($this->refundUtil->buildShipping($request));
            }

            if (isset($request['fooman_surcharge'])) {
                $refundRequest->getCheckoutData()->addItem($this->refundUtil->buildFoomanSurcharge($request));
            }

            $this->logger->logRefundRequest($orderId, $this->jsonHandler->convertToJSON($refundRequest->getData()));

            return $transactionManager->refund($transaction, $refundRequest)->getResponseData();
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
