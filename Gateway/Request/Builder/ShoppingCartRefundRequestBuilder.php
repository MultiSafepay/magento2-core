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

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\CreditmemoItemInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Exception\CouldNotRefundException;
use Magento\Sales\Model\Order\Payment;
use MultiSafepay\Api\Transactions\TransactionResponse;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Util\CurrencyUtil;
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
     * @var Config
     */
    private $config;

    /**
     * @var TransactionUtil
     */
    private $transactionUtil;

    /**
     * ShoppingCartRefundRequestBuilder constructor.
     *
     * @param CurrencyUtil $currencyUtil
     * @param Logger $logger
     * @param Config $config
     * @param TransactionUtil $transactionUtil
     */
    public function __construct(
        CurrencyUtil $currencyUtil,
        Logger $logger,
        Config $config,
        TransactionUtil $transactionUtil
    ) {
        $this->currencyUtil = $currencyUtil;
        $this->logger = $logger;
        $this->config = $config;
        $this->transactionUtil = $transactionUtil;
    }

    /**
     * @param array $buildSubject
     * @return array
     * @throws CouldNotRefundException
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function build(array $buildSubject): array
    {
        $response = [];

        $paymentDataObject = SubjectReader::readPayment($buildSubject);
        $amount = (float)SubjectReader::readAmount($buildSubject);

        /** @var Payment $payment */
        $payment = $paymentDataObject->getPayment();

        /** @var OrderInterface $order */
        $order = $payment->getOrder();

        $orderId = $order->getIncrementId();

        /** @var CreditmemoInterface $creditMemo */
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
        $response['items'] = $this->buildItems($creditMemo, $transaction);
        $response['shipping'] = $this->getShippingAmount($creditMemo);
        $response['adjustment'] = $this->getAdjustment($creditMemo);
        $response['transaction'] = $transaction;

        return $response;
    }

    /**
     * Build the items that need to be refunded
     *
     * @param CreditmemoInterface $creditMemo
     * @param TransactionResponse $transaction
     * @return array
     */
    private function buildItems(CreditmemoInterface $creditMemo, TransactionResponse $transaction): array
    {
        $itemsToRefund = [];

        foreach ($creditMemo->getItems() as $item) {
            $orderItem = $item->getOrderItem();

            if ($orderItem === null) {
                continue;
            }

            // Don't add the item if it's a bundle product
            if ($orderItem->getProductType() === 'bundle') {
                continue;
            }

            // Don't add the item if it has a parent item and the parent item is not a bundle
            if ($orderItem->getParentItem() !== null && !$this->isParentItemBundle($orderItem)) {
                continue;
            }

            if ($item->getQty() > 0) {
                $itemsToRefund[] = [
                    'merchant_item_id' => $this->getMerchantItemId($item, $transaction->getVar1()),
                    'quantity' => (int) $item->getQty(),
                ];
            }
        }

        return $itemsToRefund;
    }

    /**
     * Retrieve the shipping amount from the credit memo
     *
     * @param CreditmemoInterface $creditMemo
     * @return float|null
     */
    private function getShippingAmount(CreditmemoInterface $creditMemo): ?float
    {
        if ($this->config->useBaseCurrency($creditMemo->getStoreId())) {
            return $creditMemo->getBaseShippingAmount();
        }

        return $creditMemo->getShippingAmount();
    }

    /**
     * Retrieve the correct adjustment from the credit memo
     *
     * @param CreditmemoInterface $creditMemo
     * @return float|null
     */
    private function getAdjustment(CreditmemoInterface $creditMemo): ?float
    {
        if ($this->config->useBaseCurrency($creditMemo->getStoreId())) {
            return $creditMemo->getBaseAdjustment();
        }

        return $creditMemo->getAdjustment();
    }

    /**
     * Check if the parent item is bundle
     *
     * @param OrderItemInterface $orderItem
     * @return bool
     */
    private function isParentItemBundle(OrderItemInterface $orderItem): bool
    {
        $parentItem = $orderItem->getParentItem();

        // Parent item is not bundle, because it doesn't exist
        if ($parentItem === null) {
            return false;
        }

        if ($parentItem->getProductType() === 'bundle') {
            return true;
        }

        return false;
    }

    /**
     * Retrieve the merchant item id. It should be SKU for versions older than 3.2.0 or SKU_QuoteItemID
     *
     * @param CreditmemoItemInterface $item
     * @param string $var1
     * @return string
     */
    private function getMerchantItemId(CreditmemoItemInterface $item, string $var1): string
    {
        if (empty($var1) || $var1 < '3.2.0') {
            return $item->getSku();
        }

        return $item->getSku() . '_' . $item->getOrderItem()->getQuoteItemId();
    }
}
