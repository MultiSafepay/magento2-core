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

namespace MultiSafepay\ConnectCore\Gateway\Request\Builder;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Exception\CouldNotRefundException;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Creditmemo;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Util\CurrencyUtil;
use MultiSafepay\ConnectCore\Util\ShoppingCartRefundUtil;
use MultiSafepay\ConnectCore\Util\TransactionUtil;

class ShoppingCartRefundRequestBuilder implements BuilderInterface
{
    /**
     * @var CurrencyUtil
     */
    private $currencyUtil;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var TransactionUtil
     */
    private $transactionUtil;

    /**
     * @var ShoppingCartRefundUtil
     */
    private $shoppingCartRefundUtil;

    /**
     * ShoppingCartRefundRequestBuilder constructor.
     *
     * @param CurrencyUtil $currencyUtil
     * @param Logger $logger
     * @param TransactionUtil $transactionUtil
     * @param ShoppingCartRefundUtil $shoppingCartRefundUtil
     */
    public function __construct(
        CurrencyUtil $currencyUtil,
        Logger $logger,
        TransactionUtil $transactionUtil,
        ShoppingCartRefundUtil $shoppingCartRefundUtil
    ) {
        $this->currencyUtil = $currencyUtil;
        $this->logger = $logger;
        $this->transactionUtil = $transactionUtil;
        $this->shoppingCartRefundUtil = $shoppingCartRefundUtil;
    }

    /**
     * @param array $buildSubject
     * @return array
     * @throws CouldNotRefundException
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws Exception
     */
    public function build(array $buildSubject): array
    {
        $response = [];

        $paymentDataObject = SubjectReader::readPayment($buildSubject);
        $amount = (float)SubjectReader::readAmount($buildSubject);

        /** @var Payment $payment */
        $payment = $paymentDataObject->getPayment();

        $order = $payment->getOrder();
        $orderId = $order->getIncrementId();

        /** @var Creditmemo $creditMemo */
        $creditMemo = $payment->getCreditMemo();

        if ($creditMemo === null) {
            $message = __('The refund could not be created because the credit memo is missing');
            $this->logger->logInfoForOrder($orderId, $message->render());

            throw new NoSuchEntityException($message);
        }

        if ($amount === 0.0) {
            $message = 'Refunds with 0 amount can not be processed. Please set a different amount';
            $this->logger->logInfoForOrder($orderId, $message);

            throw new CouldNotRefundException(__($message));
        }

        $transaction = $this->transactionUtil->getTransaction($order);

        $response['order_id'] = $orderId;
        $response['store_id'] = (int)$order->getStoreId();
        $response['currency'] = $this->currencyUtil->getCurrencyCode($order);
        $response['shipping'] = $this->shoppingCartRefundUtil->getShippingAmount($creditMemo);
        $response['adjustment'] = $this->shoppingCartRefundUtil->getAdjustment($creditMemo);
        $response['transaction'] = $transaction;

        try {
            $response['items'] = $this->shoppingCartRefundUtil->buildItems($creditMemo, $transaction);
        } catch (NoSuchEntityException $noSuchEntityException) {
            $this->logger->logExceptionForOrder($orderId, $noSuchEntityException);

            $message = 'The refund can not be created because an error occurred while retrieving the items to refund';
            throw new CouldNotRefundException(__($message));
        }
        
        $extensionAttributes = $creditMemo->getExtensionAttributes();
        $foomanSurcharge = $this->shoppingCartRefundUtil->getFoomanSurcharge($extensionAttributes);
        
        if ($foomanSurcharge !== null) {
            $response['fooman_surcharge'] = $foomanSurcharge;
        }

        return $response;
    }
}
