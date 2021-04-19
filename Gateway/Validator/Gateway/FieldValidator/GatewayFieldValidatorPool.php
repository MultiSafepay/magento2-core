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

namespace MultiSafepay\ConnectCore\Gateway\Validator\Gateway\FieldValidator;

class GatewayFieldValidatorPool
{
    /**
     * @var GatewayFieldValidatorInterface[]
     */
    private $gatewayFieldValidators;

    /**
     * GatewayFieldValidatorPool constructor.
     *
     * @param array $gatewayFieldValidators
     */
    public function __construct(
        array $gatewayFieldValidators = []
    ) {
        $this->gatewayFieldValidators = $gatewayFieldValidators;
    }

    /**
     * @return GatewayFieldValidatorInterface[]
     */
    public function getGatewayFieldValidators(): array
    {
        return $this->gatewayFieldValidators;
    }

    /**
     * @param string $validatorCode
     * @return GatewayFieldValidatorInterface|null
     */
    public function getValidatorByCode(string $validatorCode): ?GatewayFieldValidatorInterface
    {
        return $this->gatewayFieldValidators[$validatorCode] ?? null;
    }
}
