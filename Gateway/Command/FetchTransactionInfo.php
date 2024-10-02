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

namespace MultiSafepay\ConnectCore\Gateway\Command;

use Exception;
use Magento\Framework\DataObject;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Sales\Model\Order\Payment;
use MultiSafepay\ConnectCore\Factory\SdkFactory;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\Exception\ApiException;
use Psr\Http\Client\ClientExceptionInterface;

class FetchTransactionInfo extends DataObject implements CommandInterface
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
     * FetchTransactionInfo constructor.
     *
     * @param SdkFactory $sdkFactory
     * @param Logger $logger
     * @param array $data
     */
    public function __construct(
        SdkFactory $sdkFactory,
        Logger $logger,
        array $data = []
    ) {
        $this->sdkFactory = $sdkFactory;
        $this->logger = $logger;
        parent::__construct($data);
    }

    /**
     * @param array $commandSubject
     * @return array
     * @throws Exception
     */
    public function execute(array $commandSubject): array
    {
        /** @var Payment $payment */
        $payment = $commandSubject['payment']->getPayment();
        $order = $payment->getOrder();
        $orderId = $order->getIncrementId();
        $result = [];

        try {
            $transactionManager = $this->sdkFactory->create((int)$order->getStoreId())->getTransactionManager();
            $transactionResponse = $transactionManager->get($orderId);
            $transaction = $transactionResponse->getData();

            unset(
                $transaction['checkout_options'],
                $transaction['costs'],
                $transaction['custom_info'],
                $transaction['customer'],
                $transaction['payment_details'],
                $transaction['payment_methods'],
                $transaction['shopping_cart']
            );

            array_walk_recursive($transaction, static function ($value, $key) use (&$result) {
                $result[$key] = $value;
            });
        } catch (ApiException $apiException) {
            $this->logger->logExceptionForOrder($orderId, $apiException);
        } catch (ClientExceptionInterface $clientException) {
            $this->logger->logClientException($orderId, $clientException);
        }

        return $result;
    }
}
