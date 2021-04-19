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

namespace MultiSafepay\ConnectCore\Gateway\Validator;

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;

class CurrencyValidator extends AbstractValidator
{
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * CurrencyValidator constructor.
     *
     * @param ResultInterfaceFactory $resultFactory
     * @param ConfigInterface $config
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory,
        ConfigInterface $config
    ) {
        $this->config = $config;
        parent::__construct($resultFactory);
    }

    /**
     * @inheritDoc
     */
    public function validate(array $validationSubject): ResultInterface
    {
        $storeId = $validationSubject['storeId'];

        if ((int)$this->config->getValue('allow_specific_currency', $storeId) === 1) {
            $availableCurrencies = explode(
                ',',
                (string)$this->config->getValue('allowed_currency', $storeId)
            );

            if (!in_array($validationSubject['currency'], $availableCurrencies, true)) {
                return $this->createResult(false);
            }
        }
        return $this->createResult(true);
    }
}
