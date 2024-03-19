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

namespace MultiSafepay\ConnectCore\Model\Ui;

use Magento\Vault\Api\Data\PaymentTokenInterface;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;

class VaultTokenUiComponentProviderTest extends AbstractTestCase
{
    public function testGetComponentForToken()
    {
        $vaultTokenUiComponentProvider = $this->getObjectManager()->create(VaultTokenUiComponentProvider::class);
        $paymentToken = $this->createMock(PaymentTokenInterface::class);
        $paymentToken->method('getGatewayToken')->willReturn('TEST-RECURRING');
        $paymentToken->method('getIsActive')->willReturn(true);
        $paymentToken->method('getIsVisible')->willReturn(true);
        $paymentToken->method('getTokenDetails')->willReturn(
            '{"type":"VISA","maskedCC":"1111","expirationDate":"12/2045"}'
        );

        $component = $vaultTokenUiComponentProvider->getComponentForToken($paymentToken);
        $expectedComponent = [
            'name' => 'MultiSafepay_ConnectFrontend/js/view/payment/gateway/method-renderer/vault',
            'config' => [
                'code' => '',
                'details' => [
                    'type' => 'VISA',
                    'maskedCC' => '1111',
                    'expirationDate' => '12/2045',
                    'icon' => [
                        'url' => '',
                        'width' => 0,
                        'height' => 0
                    ]
                ],
                'publicHash' => null
            ]
        ];

        $this->assertEquals($expectedComponent['config'], $component->getConfig());
        $this->assertEquals($expectedComponent['name'], $component->getName());
    }
}
