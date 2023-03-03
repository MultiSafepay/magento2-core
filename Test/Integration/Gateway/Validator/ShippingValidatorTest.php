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

namespace MultiSafepay\ConnectCore\Test\Integration\Gateway\Validator;

use Exception;
use Magento\Payment\Gateway\Config\Config as PaymentGatewayConfig;
use MultiSafepay\ConnectCore\Gateway\Validator\ShippingValidator;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\VisaConfigProvider;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;

class ShippingValidatorTest extends AbstractTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/quote_with_multiple_products.php
     * @magentoConfigFixture default_store payment/multisafepay_boekenbon/allow_specific_shipping_method 0
     *
     * @throws Exception
     */
    public function testValidateWithAllowShippingMethodDisabled()
    {
        $quote = $this->getQuote('tableRate');

        /** @var PaymentGatewayConfig $config */
        $config = $this->getObjectManager()->get(PaymentGatewayConfig::class);

        /** @var ShippingValidator $shippingValidator */
        $shippingValidator = $this->getObjectManager()->get(ShippingValidator::class);
        $this->assertFalse(
            $shippingValidator->validate($quote, $config, VisaConfigProvider::CODE)
        );
    }

    /**
     * @magentoConfigFixture default_store payment/multisafepay_boekenbon/allow_specific_shipping_method 1
     * @magentoConfigFixture default_store payment/multisafepay_boekenbon/allowed_shipping_method tableRate
     *
     * @throws Exception
     */
    public function testValidateWithAllowShippingMethodEnabled()
    {
        $quote = $this->getQuote('tableRate');

        /** @var PaymentGatewayConfig $config */
        $config = $this->getObjectManager()->get(PaymentGatewayConfig::class);
        $config->setMethodCode('multisafepay_boekenbon');

        /** @var ShippingValidator $shippingValidator */
        $shippingValidator = $this->getObjectManager()->get(ShippingValidator::class);
        $this->assertTrue($shippingValidator->validate($quote, $config, VisaConfigProvider::CODE));
    }
}
