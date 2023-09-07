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
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Exception\CouldNotRefundException;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Util\CurrencyUtil;

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
     * ShoppingCartRefundRequestBuilder constructor.
     *
     * @param CurrencyUtil $currencyUtil
     * @param Logger $logger
     * @param Config $config
     */
    public function __construct(
        CurrencyUtil $currencyUtil,
        Logger $logger,
        Config $config
    ) {
        $this->currencyUtil = $currencyUtil;
        $this->logger = $logger;
        $this->config = $config;
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

        $message = 'Refunds with 0 amount can not be processed. Please set a different amount';
        if ($amount === 0.0) {
            $this->logger->logInfoForOrder($orderId, $message);

            throw new CouldNotRefundException(__($message));
        }

        $response['order_id'] = $orderId;
        $response['store_id'] = (int)$order->getStoreId();
        $response['currency'] = $this->currencyUtil->getCurrencyCode($order);
        $response['items'] = $this->buildItems($creditMemo);
        $response['shipping'] = $this->getShippingAmount($creditMemo);
        $response['adjustment'] = $this->getAdjustment($creditMemo);

        return $response;
    }

    /**
     * Build the items that need to be refunded
     *
     * @param CreditmemoInterface $creditMemo
     * @return array
     */
    private function buildItems(CreditmemoInterface $creditMemo): array
    {
        $itemsToRefund = [];

        foreach ($creditMemo->getItems() as $item) {
            if (($item->getOrderItem() !== null) && $item->getOrderItem()->getParentItem() !== null) {
                continue;
            }

            if ($item->getQty() > 0) {
                $itemsToRefund[] = [
                    'sku' => $item->getSku(),
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
}
