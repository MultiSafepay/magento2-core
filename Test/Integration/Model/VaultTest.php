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

namespace MultiSafepay\ConnectCore\Test\Integration\Model;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use MultiSafepay\ConnectCore\Model\Vault;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;

class VaultTest extends AbstractTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order_with_customer.php
     * @throws LocalizedException
     * @throws Exception
     */
    public function testGetVaultPaymentToken(): void
    {
        $order = $this->getOrder();
        $customerId = (int)$order->getCustomerId();
        $code = 'multisafepay_creditcard_recurring';
        $recurringDetails = $this->getRecurringDetails();

        $vaultObject = $this->getVaultObject();
        $paymentToken = $vaultObject->getVaultPaymentToken($customerId, $code, $recurringDetails);

        self::assertSame($recurringDetails['recurringId'], $paymentToken->getGatewayToken());
        self::assertTrue($paymentToken->getIsActive());
        self::assertTrue($paymentToken->getIsVisible());

        $serializer = $this->getSerializer();

        $expectedTokenDetails = $serializer->serialize([
            'type' => 'VISA',
            'maskedCC' => '1111',
            'expirationDate' => '12/2045'
        ]);

        self::assertSame($expectedTokenDetails, $paymentToken->getTokenDetails());
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order_with_customer.php
     * @throws Exception
     */
    public function testRemovePaymentTokensByList()
    {
        $order = $this->getOrder();
        $customerId = $order->getCustomerId();
        $code = 'multisafepay_creditcard_recurring';
        $recurringDetails = $this->getRecurringDetails();

        $vaultObject = $this->getVaultObject();
        $paymentToken = $vaultObject->getVaultPaymentToken((int)$customerId, $code, $recurringDetails);
        $paymentTokenRepository = $this->getPaymentTokenRepositoryInterface();

        $paymentToken->setIsActive(true);
        $paymentToken->setIsVisible(true);

        $savedPaymentToken = $paymentTokenRepository->save($paymentToken);

        $paymentTokenEntityId = $savedPaymentToken->getEntityId();
        $paymentToken = $paymentTokenRepository->getById($paymentTokenEntityId);

        $vaultObject->removePaymentTokensByList([$paymentToken], (string)$customerId);
        $paymentToken = $paymentTokenRepository->getById($paymentTokenEntityId);

        self::assertFalse($paymentToken->getIsActive());
        self::assertFalse($paymentToken->getIsVisible());
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order_with_customer.php
     * @return void
     * @throws Exception
     */
    public function testGetExtensionAttributesWillBeTheSameAsBefore()
    {
        $order = $this->getOrder();
        $payment = $order->getPayment();
        $vaultObject = $this->getVaultObject();
        $extensionAttributes = $vaultObject->getExtensionAttributes($payment);
        self::assertSame($extensionAttributes, $payment->getExtensionAttributes());
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order_with_customer.php
     * @return void
     * @throws Exception
     */
    public function testGetExtensionAttributesWillReturnVaultPaymentToken()
    {
        $order = $this->getOrder();
        $customerId = $order->getCustomerId();
        $code = 'multisafepay_creditcard_recurring';
        $recurringDetails = $this->getRecurringDetails();

        $payment = $order->getPayment();
        $vaultObject = $this->getVaultObject();
        $extensionAttributes = $vaultObject->getExtensionAttributes($payment);
        $paymentToken = $vaultObject->getVaultPaymentToken((int)$customerId, $code, $recurringDetails);

        $extensionAttributes->setVaultPaymentToken($paymentToken);

        self::assertSame($paymentToken, $extensionAttributes->getVaultPaymentToken());
    }

    /**
     * @return Vault
     */
    private function getVaultObject(): Vault
    {
        return $this->getObjectManager()->create(Vault::class);
    }

    /**
     * @return Json
     */
    private function getSerializer(): Json
    {
        return $this->getObjectManager()->create(Json::class);
    }

    private function getPaymentTokenRepositoryInterface(): PaymentTokenRepositoryInterface
    {
        return $this->getObjectManager()->create(PaymentTokenRepositoryInterface::class);
    }

    /**
     * @return string[]
     */
    private function getRecurringDetails(): array
    {
        return [
            'recurringId' => 'TEST-RECURRING',
            'type' => 'VISA',
            'expirationDate' => '4512',
            'last4' => '1111'
        ];
    }
}
