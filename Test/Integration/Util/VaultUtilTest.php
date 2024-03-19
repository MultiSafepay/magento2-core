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
 * Copyright © 2020 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 *
 */

declare(strict_types=1);

namespace MultiSafepay\Test\Integration\Util;

use Magento\Framework\Exception\LocalizedException;
use MultiSafepay\ConnectCore\Api\PaymentTokenInterface;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Util\VaultUtil;

class VaultUtilTest extends AbstractTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order_with_customer.php
     * @throws LocalizedException
     */
    public function testValidateVaultTokenEnablerIsTrue(): void
    {
        $this->validateVaultTokenEnablerByType(true);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order_with_customer.php
     * @throws LocalizedException
     */
    public function testValidateVaultTokenEnablerIsFalse(): void
    {
        $this->validateVaultTokenEnablerByType(false);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order_with_customer.php
     * @throws LocalizedException
     */
    public function testValidateVaultTokenEnablerIsNull(): void
    {
        $this->validateVaultTokenEnablerByType(null);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order_with_customer.php
     * @throws LocalizedException
     */
    public function testValidateVaultTokenEnablerIsEmpty(): void
    {
        $this->validateVaultTokenEnablerByType('');
    }

    /**
     * @return void
     */
    public function testGetIconWillReturnEmptyString()
    {
        $vaultUtil = $this->getVaulUtiltObject();
        $icon = $vaultUtil->getIcon('')[PaymentTokenInterface::ICON_URL];

        self::assertStringContainsString('', $icon);
    }

    /**
     * @return void
     */
    public function testGetActiveConfigPath()
    {
        $vaultUtil = $this->getVaulUtiltObject();
        $activeConfigPath = $vaultUtil->getActiveConfigPath('multisafepay_visa');

        self::assertStringContainsString('payment/multisafepay_visa_vault/active', $activeConfigPath);
    }

    /**
     * @param $type
     * @throws LocalizedException
     */
    private function validateVaultTokenEnablerByType($type): void
    {
        $order = $this->getOrder();
        $payment = $order->getPayment();

        //Add is_active_payment_token_enabler to additionalInformation
        $payment->setAdditionalInformation('is_active_payment_token_enabler', $type);
        $vaultUtil = $this->getVaulUtiltObject();

        if ($type) {
            self::assertTrue($vaultUtil->validateVaultTokenEnabler($payment->getAdditionalInformation()));

            return;
        }

        self::assertFalse($vaultUtil->validateVaultTokenEnabler($payment->getAdditionalInformation()));
    }

    /**
     * @return VaultUtil
     */
    private function getVaulUtiltObject(): VaultUtil
    {
        return $this->getObjectManager()->create(VaultUtil::class);
    }
}
