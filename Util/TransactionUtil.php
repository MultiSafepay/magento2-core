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

namespace MultiSafepay\ConnectCore\Util;

use Exception;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Exception\CouldNotRefundException;
use MultiSafepay\Api\Transactions\TransactionResponse;
use MultiSafepay\ConnectCore\Factory\SdkFactory;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\Exception\ApiException;
use Psr\Http\Client\ClientExceptionInterface;

class TransactionUtil
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
     * Retrieve the transaction from MultiSafepay
     *
     * @param OrderInterface $order
     * @return TransactionResponse|null
     */
    public function getTransaction(OrderInterface $order): ?TransactionResponse
    {
        $orderIncrementId = $order->getIncrementId();

        try {
            //Retrieve transaction from API call
            $transactionManager = $this->sdkFactory->create((int)$order->getStoreId())->getTransactionManager();
            $transactionResponse = $transactionManager->get($orderIncrementId);
        } catch (ClientExceptionInterface | ApiException | Exception $exception) {
            $this->logger->logExceptionForOrder($orderIncrementId, $exception);

            return null;
        }

        return $transactionResponse;
    }
}
