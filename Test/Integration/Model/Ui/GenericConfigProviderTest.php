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

namespace MultiSafepay\ConnectCore\Test\Integration\Model\Ui;

use Magento\Framework\App\RequestInterface;
use MultiSafepay\ConnectCore\Model\Ui\GenericConfigProvider;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;

class GenericConfigProviderTest extends AbstractTestCase
{
    /**
     * Test that isCartPage() returns true when on the cart page.
     *
     * @magentoAppArea frontend
     */
    public function testIsCartPageReturnsTrueOnCheckoutCartIndex(): void
    {
        $genericConfigProvider = $this->createMockProvider('checkout_cart_index');

        self::assertTrue($genericConfigProvider->isCartPage());
    }

    /**
     * Test that isCartPage() returns false when not on the cart page.
     *
     * @magentoAppArea frontend
     */
    public function testIsCartPageReturnsFalseOnCheckoutIndexIndex(): void
    {
        $genericConfigProvider = $this->createMockProvider('checkout_index_index');

        self::assertFalse($genericConfigProvider->isCartPage());
    }

    /**
     * @param string $fullActionName
     * @return RequestInterface
     */
    private function createMockRequest(string $fullActionName): RequestInterface
    {
        $request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getFullActionName'])
            ->getMockForAbstractClass();

        $request->method('getFullActionName')->willReturn($fullActionName);

        return $request;
    }

    /**
     * @param string $fullActionName
     * @return GenericConfigProvider
     */
    private function createMockProvider(string $fullActionName): GenericConfigProvider
    {
        $objectManager = $this->getObjectManager();

        return $this->getMockBuilder(GenericConfigProvider::class)
            ->setConstructorArgs([
                $objectManager->get(\Magento\Framework\View\Asset\Repository::class),
                $objectManager->get(\MultiSafepay\ConnectCore\Config\Config::class),
                $objectManager->get(\MultiSafepay\ConnectCore\Factory\SdkFactory::class),
                $objectManager->get(\Magento\Checkout\Model\Session::class),
                $objectManager->get(\MultiSafepay\ConnectCore\Logger\Logger::class),
                $objectManager->get(\Magento\Framework\Locale\ResolverInterface::class),
                $objectManager->get(\Magento\Payment\Gateway\Config\Config::class),
                $objectManager->get(\Magento\Framework\App\Config\Storage\WriterInterface::class),
                $objectManager->get(\MultiSafepay\ConnectCore\Util\JsonHandler::class),
                $objectManager->get(\MultiSafepay\ConnectCore\Util\CheckoutFieldsUtil::class),
                $this->createMockRequest($fullActionName),
            ])
            ->onlyMethods([])
            ->getMock();
    }
}
