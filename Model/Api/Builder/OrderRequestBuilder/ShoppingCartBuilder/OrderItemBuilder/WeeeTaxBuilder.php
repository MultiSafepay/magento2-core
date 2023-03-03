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

namespace MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\ShoppingCartBuilder\OrderItemBuilder;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Weee\Model\Config;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\ShoppingCart\Item;
use MultiSafepay\ConnectCore\Util\JsonHandler;
use MultiSafepay\ValueObject\Money;

class WeeeTaxBuilder
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var JsonHandler
     */
    private $jsonHandler;

    /**
     * @var \MultiSafepay\ConnectCore\Config\Config
     */
    private $config;

    /**
     * WeeeTaxBuilder constructor.
     *
     * @param \MultiSafepay\ConnectCore\Config\Config $config
     * @param ScopeConfigInterface $scopeConfig
     * @param JsonHandler $jsonHandler
     */
    public function __construct(
        \MultiSafepay\ConnectCore\Config\Config $config,
        ScopeConfigInterface $scopeConfig,
        JsonHandler $jsonHandler
    ) {
        $this->config = $config;
        $this->scopeConfig = $scopeConfig;
        $this->jsonHandler = $jsonHandler;
    }

    /**
     * @param array $items
     * @param $orderItems
     * @param int $storeId
     * @param string $currency
     * @return array
     */
    public function addWeeeTaxToItems(array $items, $orderItems, int $storeId, string $currency): array
    {
        $weeeTaxAmount = 0;
        $weeeTaxTitle = '';
        $weeeTaxes = [];

        foreach ($orderItems as $item) {
            if (!$this->shouldApplyWeeeTax($item->getWeeeTaxApplied())) {

                continue;
            }

            if ($this->shouldApplyItemTaxOnWeeeTax($storeId)) {
                $weeeTaxes = $this->buildWeeeTaxDataWithTax($item, $weeeTaxes);

                continue;
            }

            $weeeTaxData = $this->buildWeeeTaxDataWithoutTax($item, $weeeTaxTitle, (float)$weeeTaxAmount);
            $weeeTaxTitle = $weeeTaxData['title'];
            $weeeTaxAmount = $weeeTaxData['amount'];
        }

        $items = $this->buildWeeeTaxItemsWithTax($weeeTaxes, $items, $currency);

        if ($weeeTaxTitle && $weeeTaxAmount && !$this->shouldApplyItemTaxOnWeeeTax($storeId)) {
            $items = $this->buildWeeeTaxItemWithoutTax(
                $items,
                $weeeTaxTitle,
                $weeeTaxAmount,
                $currency
            );
        }

        return $items;
    }

    /**
     * @param string|null $weeeTaxApplied
     * @return bool
     */
    private function shouldApplyWeeeTax(?string $weeeTaxApplied): bool
    {
        return ($weeeTaxApplied !== '[]' && !empty($weeeTaxApplied))
               && !empty($this->jsonHandler->readJSON($weeeTaxApplied));
    }

    /**
     * @param OrderItemInterface $item
     * @param array $weeeTaxes
     * @return array
     */
    private function buildWeeeTaxDataWithTax(OrderItemInterface $item, array $weeeTaxes): array
    {
        $weeeTaxData = $this->jsonHandler->readJSON($item->getWeeeTaxApplied());

        foreach ($weeeTaxData as $weeeTaxItem) {
            foreach ($weeeTaxes as &$weeeTax) {
                if ($weeeTax['tax'] === $item->getTaxPercent()) {
                    $weeeTax['amount'] += $this->getWeeeTaxAmount(
                        (float)$weeeTaxItem['base_row_amount'],
                        (float)$weeeTaxItem['row_amount'],
                        (int)$item->getStoreId()
                    );

                    continue 2;
                }
            }

            unset($weeeTax);

            $weeeTaxes[] = [
                'title' => $weeeTaxItem['title'],
                'amount' => $this->getWeeeTaxAmount(
                    (float)$weeeTaxItem['base_row_amount'],
                    (float)$weeeTaxItem['row_amount'],
                    (int)$item->getStoreId()
                ),
                'tax' => $item->getTaxPercent(),
            ];
        }

        return $weeeTaxes;
    }

    /**
     * @param OrderItemInterface $item
     * @param string $weeeTaxTitle
     * @param float $weeeTaxAmount
     * @return array
     */
    private function buildWeeeTaxDataWithoutTax(
        OrderItemInterface $item,
        string $weeeTaxTitle,
        float $weeeTaxAmount
    ): array {
        $weeeTaxData = $this->jsonHandler->readJSON($item->getWeeeTaxApplied());

        foreach ($weeeTaxData as $weeeTaxItem) {
            $weeeTaxTitle = $weeeTaxItem['title'];
            $weeeTaxAmount += $this->getWeeeTaxAmount(
                (float)$weeeTaxItem['base_row_amount'],
                (float)$weeeTaxItem['row_amount'],
                (int)$item->getStoreId()
            );
        }

        return ['title' => $weeeTaxTitle, 'amount' => $weeeTaxAmount];
    }

    /**
     * @param array $weeeTaxes
     * @param array $items
     * @param string $currency
     * @return array
     */
    private function buildWeeeTaxItemsWithTax(array $weeeTaxes, array $items, string $currency): array
    {
        foreach ($weeeTaxes as $weeeTax) {
            $items[] = (new Item())
                ->addName($weeeTax['title'])
                ->addUnitPrice(new Money(round($weeeTax['amount'] * 100, 10), $currency))
                ->addQuantity(1)
                ->addDescription($weeeTax['title'])
                ->addMerchantItemId($weeeTax['title'] . $weeeTax['tax'])
                ->addTaxRate((float)$weeeTax['tax']);
        }

        return $items;
    }

    /**
     * @param array $items
     * @param string $weeeTaxTitle
     * @param float $weeeTaxAmount
     * @param string $currency
     * @return array
     */
    private function buildWeeeTaxItemWithoutTax(
        array $items,
        string $weeeTaxTitle,
        float $weeeTaxAmount,
        string $currency
    ): array {
        $items[] = (new Item())
            ->addName($weeeTaxTitle)
            ->addUnitPrice(new Money(round($weeeTaxAmount * 100, 10), $currency))
            ->addQuantity(1)
            ->addDescription($weeeTaxTitle)
            ->addMerchantItemId('FPT')
            ->addTaxRate(0);

        return $items;
    }

    /**
     * @param int $storeId
     * @return bool
     */
    private function shouldApplyItemTaxOnWeeeTax(int $storeId): bool
    {
        return (bool)$this->scopeConfig->getValue(
            Config::XML_PATH_FPT_TAXABLE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param float $baseAmount
     * @param float $amount
     * @param int $storeId
     * @return float
     */
    private function getWeeeTaxAmount(float $baseAmount, float $amount, int $storeId): float
    {
        if ($this->config->useBaseCurrency($storeId)) {
            return $baseAmount;
        }

        return $amount;
    }
}
