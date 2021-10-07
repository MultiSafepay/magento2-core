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

namespace MultiSafepay\ConnectCore\Plugin;

use Magento\Payment\Gateway\Config\Config;
use Magento\Payment\Model\MethodInterface;
use Magento\Payment\Model\MethodList;
use Magento\Quote\Api\Data\CartInterface;
use MultiSafepay\ConnectCore\Gateway\Validator\AmountValidator;
use MultiSafepay\ConnectCore\Gateway\Validator\CustomerGroupValidator;
use MultiSafepay\ConnectCore\Gateway\Validator\ShippingValidator;

class MethodListPlugin
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var ShippingValidator
     */
    private $shippingValidator;

    /**
     * @var CustomerGroupValidator
     */
    private $customerGroupValidator;

    /**
     * @var AmountValidator
     */
    private $amountValidator;

    /**
     * MethodListPlugin constructor.
     *
     * @param AmountValidator $amountValidator
     * @param Config $config
     * @param CustomerGroupValidator $customerGroupValidator
     * @param ShippingValidator $shippingValidator
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        AmountValidator $amountValidator,
        Config $config,
        CustomerGroupValidator $customerGroupValidator,
        ShippingValidator $shippingValidator
    ) {
        $this->amountValidator = $amountValidator;
        $this->config = $config;
        $this->customerGroupValidator = $customerGroupValidator;
        $this->shippingValidator = $shippingValidator;
    }

    /**
     * @param MethodList $subject
     * @param $availableMethods
     * @param CartInterface|null $quote
     * @return MethodInterface[]
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetAvailableMethods(
        MethodList $subject,
        $availableMethods,
        CartInterface $quote
    ): array {
        $availableValidators = [$this->shippingValidator, $this->customerGroupValidator, $this->amountValidator];

        foreach ($availableMethods as $key => $method) {
            $this->config->setMethodCode($method->getCode());

            foreach ($availableValidators as $validator) {
                if ($validator->validate($quote, $this->config)) {
                    unset($availableMethods[$key]);
                    continue 2;
                }
            }
        }

        return $availableMethods;
    }
}
