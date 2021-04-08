<?php
/**
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is provided with Magento in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * Copyright © 2021 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 *
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Util;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Module\Manager;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote\Address\TotalFactory;
use Magento\Quote\Model\Quote\ShippingAssignment\ShippingAssignmentProcessor;

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
     * ThirdPartyPluginsUtil constructor.
     *
     * @param Manager $moduleManager
     * @param ShippingAssignmentProcessor $shippingAssignmentProcessor
     * @param TotalFactory $totalFactory
     */
    public function __construct(
        Manager $moduleManager,
        ShippingAssignmentProcessor $shippingAssignmentProcessor,
        TotalFactory $totalFactory
    ) {
        $this->moduleManager = $moduleManager;
        $this->shippingAssignmentProcessor = $shippingAssignmentProcessor;
        $this->totalFactory = $totalFactory;
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
     */
    private function getFoomanTotals($subject, CartInterface $quote, array &$resultData): void
    {
        if (method_exists($subject, 'collect') && method_exists($subject, 'fetch')) {
            $total = $quote->getTotals()['fooman_surcharge'] ?? null;

            if ($total) {
                $foomanTotal = $subject->collect($quote, $this->getShippingAssignment($quote), $total)
                    ->fetch($quote, $total);

                if ($foomanTotal && isset($foomanTotal['code'], $foomanTotal['full_info'])) {
                    $fullTotalInfo = $foomanTotal['full_info'];
                    foreach ($fullTotalInfo as $totalInfo) {
                        $totalInfo->setTaxAmount($total->getTaxAmount());
                        $totalInfo->setBaseTaxAmount($total->getBaseTaxAmount() ?: $total->getTaxAmount());
                        $resultData[$totalInfo->getTypeId()] = $totalInfo;
                    }
                }
            }
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
}
