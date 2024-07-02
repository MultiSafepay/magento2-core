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
use Magento\Sales\Exception\CouldNotInvoiceException;
use Magento\Store\Model\Store;
use MultiSafepay\ConnectCore\Factory\SdkFactory;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\Exception\ApiException;
use Psr\Http\Client\ClientExceptionInterface;

class CaptureClient implements ClientInterface
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
     * CaptureClient constructor.
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
     * @throws Exception
     */
    public function placeRequest(TransferInterface $transferObject): ?array
    {
        $request = $transferObject->getBody();
        $orderId = $request['order_id'];

        try {
            return $this->sdkFactory->create($request[Store::STORE_ID] ?? null)
                ->getTransactionManager()->capture($orderId, $request['payload'])->getResponseData();
        } catch (ClientExceptionInterface $clientException) {
            $this->logger->logClientException($orderId, $clientException);

            throw new CouldNotInvoiceException(__($clientException->getMessage()));
        } catch (ApiException $apiException) {
            $this->logger->logExceptionForOrder($orderId, $apiException);

            throw new CouldNotInvoiceException(__($apiException->getMessage()));
        }
    }
}
