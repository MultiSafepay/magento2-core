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

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Module\Manager;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote\Address\TotalFactory;
use Magento\Quote\Model\Quote\ShippingAssignment\ShippingAssignmentProcessor;
use Magento\Store\Model\ScopeInterface;

class ThirdPartyPluginsUtil
{
    public const TOTALS_STRATEGY_NAME = 'totals';

    /**
     * @var string[]
     */
    private $thirdPartyPluginsStrategy = [
        self::TOTALS_STRATEGY_NAME => [
            'Mageprince_ExtrafeePro' => [
                'class' => \Mageprince\ExtrafeePro\Model\Total\Fee::class,
                'processor' => 'getMageprinceQuoteTotal',
            ],
            'Fooman_Surcharge' => [
                'class' => \Fooman\Surcharge\Model\Total\Quote\Surcharge::class,
                'processor' => 'getFoomanTotals',
            ],
            'Magento_GiftWrapping' => [
                'class' => \Magento\GiftWrapping\Model\Total\Quote\Giftwrapping::class,
                'processor' => 'prepareMagentoGiftwrappingTotals',
            ],
        ],
    ];

    /**
     * @var Manager
     */
    private $moduleManager;

    /**
     * @var ShippingAssignmentProcessor
     */
    private $shippingAssignmentProcessor;

    /**
     * @var TotalFactory
     */
    private $totalFactory;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var TaxUtil
     */
    private $taxUtil;

    /**'
     * ThirdPartyPluginsUtil constructor.
     *
     * @param Manager $moduleManager
     * @param ShippingAssignmentProcessor $shippingAssignmentProcessor
     * @param TotalFactory $totalFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param TaxUtil $taxUtil
     */
    public function __construct(
        Manager $moduleManager,
        ShippingAssignmentProcessor $shippingAssignmentProcessor,
        TotalFactory $totalFactory,
        ScopeConfigInterface $scopeConfig,
        TaxUtil $taxUtil
    ) {
        $this->moduleManager = $moduleManager;
        $this->shippingAssignmentProcessor = $shippingAssignmentProcessor;
        $this->totalFactory = $totalFactory;
        $this->scopeConfig = $scopeConfig;
        $this->taxUtil = $taxUtil;
    }

    /**
     * @param CartInterface $quote
     * @param string $strategy
     * @return array
     */
    public function getThirdPartyPluginAdditionalData(
        CartInterface $quote,
        string $strategy = self::TOTALS_STRATEGY_NAME
    ): array {
        $resultData = [];

        foreach ($this->thirdPartyPluginsStrategy[$strategy] as $plugin => $data) {
            if ($this->moduleManager->isEnabled($plugin)
                && $object = ObjectManager::getInstance()->get($data['class'])
            ) {
                $this->{$data['processor']}($object, $quote, $resultData);
            }
        }

        return $resultData;
    }

    /**
     * @param $subject
     * @param CartInterface $quote
     * @param array $resultData
     *
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function getMageprinceQuoteTotal($subject, CartInterface $quote, array &$resultData): void
    {
        if (method_exists($subject, 'collect') && method_exists($subject, 'fetch')) {
            $addressTotal = $this->totalFactory->create();
            $totalData = $subject->collect($quote, $this->getShippingAssignment($quote), $addressTotal)
                ->fetch($quote, $addressTotal);

            if ($totalData && isset($totalData['code'])) {
                $totalCode = $totalData['code'];
                $addressTotal->setCode($totalCode)->setLabel($totalData['title'] ?? null)
                    ->setAmount($totalData['value'] ?? null)
                    ->setBaseAmount($addressTotal->getBaseFee());

                $resultData[$totalCode] = $addressTotal;
            }
        }
    }

    /**
     * @param $subject
     * @param CartInterface $quote
     * @param array $resultData
     *
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function getFoomanTotals($subject, CartInterface $quote, array &$resultData): void
    {
        if (!method_exists($subject, 'collect') || !method_exists($subject, 'fetch')) {
            return;
        }

        $total = $quote->getTotals()['fooman_surcharge'] ?? null;

        if (!$total) {
            return;
        }

        $totalCalculation = $subject->collect($quote, $this->getShippingAssignment($quote), $total)
            ->fetch($quote, $total);

        if ($totalCalculation && isset($totalCalculation['code'], $totalCalculation['full_info'])) {
            $fullTotalInfo = $totalCalculation['full_info'];
            foreach ($fullTotalInfo as $totalInfo) {
                if (isset($foomanTotal)) {
                    $foomanTotal->setAmount($foomanTotal->getAmount() + $totalInfo->getAmount());
                    $foomanTotal->setBaseAmount($foomanTotal->getBaseAmount() + $totalInfo->getBaseAmount());
                    $foomanTotal->setLabel($foomanTotal->getLabel() . ', ' . $totalInfo->getLabel());

                    continue;
                }

                $foomanTotal = $totalInfo;

                $foomanTotal->setTaxAmount($total->getTaxAmount());
                $foomanTotal->setBaseTaxAmount($total->getBaseTaxAmount() ?: $total->getTaxAmount());
                $resultData['multisafepay_custom_fooman_total'] = $foomanTotal;
            }
        }
    }

    /**
     * @param $subject
     * @param CartInterface $quote
     * @param array $resultData
     *
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    private function prepareMagentoGiftwrappingTotals($subject, CartInterface $quote, array &$resultData): void
    {
        if (($total = $quote->getTotals()['giftwrapping'] ?? null)
            && ((float)$total->getGwItemsPrice() > 0 || (float)$total->getGwPrice() > 0)
        ) {
            $taxRate = $this->taxUtil->getTaxRateByTaxRateIdAndCart(
                $quote,
                $this->scopeConfig->getValue(
                    \Magento\GiftWrapping\Helper\Data::XML_PATH_TAX_CLASS,
                    ScopeInterface::SCOPE_STORES,
                    $quote->getStoreId()
                )
            );

            $total->setAmount($total->getGwItemsPrice() + $total->getGwPrice())
                ->setBaseAmount($total->getGwItemsBasePrice() + $total->getGwBasePrice())
                ->setTaxRate($taxRate)
                ->setBaseTaxRate($taxRate);

            $resultData[$total->getCode()] = $total;
        }
    }

    /**
     * @param CartInterface $quote
     * @return ShippingAssignmentInterface
     */
    private function getShippingAssignment(CartInterface $quote): ShippingAssignmentInterface
    {
        return $this->shippingAssignmentProcessor->create($quote)
            ->setItems($quote->getAllItems());
    }

    /**
     * Check if Amasty Checkout is enabled and if the option to create an account after placing an order is set.
     *
     * @return bool
     */
    public function canCreateAccountAfterPlacingOrder(): bool
    {
        return $this->moduleManager->isEnabled('Amasty_CheckoutCore')
            && $this->scopeConfig->getValue(
                'amasty_checkout/additional_options/create_account',
                ScopeInterface::SCOPE_STORE
            ) === '1';
    }
}
