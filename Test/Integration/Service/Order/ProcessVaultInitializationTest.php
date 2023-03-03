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

namespace MultiSafepay\ConnectCore\Test\Integration\Service\Order;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\Ui\VaultConfigProvider;
use MultiSafepay\ConnectCore\Service\Order\ProcessVaultInitialization;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;

class ProcessVaultInitializationTest extends AbstractTestCase
{

    /**
     * @var ProcessVaultInitialization
     */
    private $processVaultInitialization;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->processVaultInitialization = $this->getObjectManager()->create(ProcessVaultInitialization::class);
    }

    /**
     * @magentoDataFixture     Magento/Sales/_files/order_with_customer.php
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     * @magentoConfigFixture default_store multisafepay/general/mode 0
     * @magentoDbIsolation     enabled
     * @magentoAppIsolation    enabled
     * @throws LocalizedException
     * @throws Exception
     */
    public function testVaultInitialization(): void
    {
        $order = $this->getOrderWithVisaPaymentMethod();
        $payment = $order->getPayment();
        $payment->setAdditionalInformation(VaultConfigProvider::IS_ACTIVE_CODE, true);
        $gatewayToken = '12312312312';

        $isVaultInitialized = $this->processVaultInitialization->execute(
            $order->getIncrementId(),
            $payment,
            [
                'recurring_id' => $gatewayToken,
                'card_expiry_date' => '2512',
                'last4' => '1111',
                'type' => 'VISA'
            ]
        );

        self::assertTrue($isVaultInitialized);

        if ($isVaultInitialized) {
            $vaultData = $payment->getExtensionAttributes()->getVaultPaymentToken()->getData();

            self::assertEquals($gatewayToken, $vaultData[PaymentTokenInterface::GATEWAY_TOKEN]);
        }
    }
}
