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

use DateTime;
use Magento\Framework\Exception\NoSuchEntityException;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\Description;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\Exception\InvalidArgumentException;
use MultiSafepay\ValueObject\CartItem;
use MultiSafepay\ValueObject\UnitPrice;

class RefundUtil
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Description
     */
    private $description;

    /**
     * @var OrderUtil
     */
    private $orderUtil;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var TaxUtil
     */
    private $taxUtil;

    /**
     * @param Config $config
     * @param Description $description
     * @param OrderUtil $orderUtil
     * @param Logger $logger
     * @param TaxUtil $taxUtil
     */
    public function __construct(
        Config $config,
        Description $description,
        OrderUtil $orderUtil,
        Logger $logger,
        TaxUtil $taxUtil
    ) {
        $this->config = $config;
        $this->description = $description;
        $this->orderUtil = $orderUtil;
        $this->logger = $logger;
        $this->taxUtil = $taxUtil;
    }

    /**
     * Build the Fooman surcharge item that needs to be refunded
     *
     * @param array $request
     * @return CartItem
     * @throws InvalidArgumentException
     */
    public function buildFoomanSurcharge(array $request): CartItem
    {
        $amount = $this->config->useBaseCurrency($request['store_id'])
            ? $request['fooman_surcharge']['base_amount']
            : $request['fooman_surcharge']['amount'];

        return (new CartItem())->addMerchantItemId('fooman_surcharge-' . (new DateTime())->getTimestamp())
            ->addQuantity(1)
            ->addName(__('Fooman Surcharge')->render())
            ->addDescription(__('Fooman Surcharge')->render())
            ->addUnitPriceValue((new UnitPrice(-abs($amount))))
            ->addTaxRate($request['fooman_surcharge']['tax_rate'] ?? 0);
    }

    /**
     * Build the adjustment that needs to be refunded
     *
     * @param array $request
     * @return CartItem
     * @throws InvalidArgumentException
     */
    public function buildAdjustment(array $request): CartItem
    {
        return (new CartItem())->addMerchantItemId('adjustment-' . (new DateTime())->getTimestamp())
            ->addQuantity(1)
            ->addName(__('Adjustment for refund')->render())
            ->addDescription(__('Adjustment for refund')->render())
            ->addUnitPriceValue((new UnitPrice(-abs($request['adjustment']))))
            ->addTaxRate(0);
    }

    /**
     * Build the shipping item that needs to be refunded
     *
     * @param array $request
     * @return CartItem
     * @throws InvalidArgumentException
     */
    public function buildShipping(array $request): CartItem
    {
        try {
            $order = $this->orderUtil->getOrderByIncrementId($request['order_id']);
            $shippingTaxRate = $this->taxUtil->getShippingTaxRate($order);
        } catch (NoSuchEntityException $exception) {
            $this->logger->logExceptionForOrder($request['order_id'], $exception);
        }

        return (new CartItem())->addMerchantItemId('msp-shipping-' . (new DateTime())->getTimestamp())
            ->addQuantity(1)
            ->addName(__('Refund for shipping')->render())
            ->addDescription(__('Refund for shipping')->render())
            ->addUnitPriceValue(new UnitPrice(-abs($request['shipping'])))
            ->addTaxRate($shippingTaxRate ?? 0);
    }

    /**
     * Build the refund description
     *
     * @param string $orderId
     * @param int $storeId
     * @return Description
     */
    public function buildDescription(string $orderId, int $storeId): Description
    {
        return $this->description->addDescription($this->config->getRefundDescription($orderId, $storeId));
    }
}
