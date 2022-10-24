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

namespace MultiSafepay\ConnectCore\Test\Integration\Model\Ui\Gateway;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Payment\Gateway\Config\Config as PaymentConfig;
use Magento\Store\Model\StoreManagerInterface;
use MultiSafepay\Api\WalletManager;
use MultiSafepay\Api\Wallets\ApplePay\MerchantSession;
use MultiSafepay\Api\Wallets\ApplePay\MerchantSessionRequest;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\ApplePayConfigProvider;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Util\CheckoutFieldsUtil;
use MultiSafepay\ConnectCore\Util\JsonHandler;
use MultiSafepay\Sdk;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ApplePayConfigProviderTest extends AbstractTestCase
{
    private const FAKE_APPLE_SESSION_RESPONSE = '12312312qweqweqweqwe123123';

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->storeManager = $this->getObjectManager()->get(StoreManagerInterface::class);
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     * @magentoConfigFixture default_store multisafepay/general/mode 0
     */
    public function testCreateApplePayMerchantSession(): void
    {
        $order = $this->getOrder();
        $storeId = (int)$order->getStoreId();
        $originDomain = $this->storeManager->getStore($storeId)->getBaseUrl();
        $requestData = [
            'origin_domain' => $originDomain,
            'validation_url' => 'testValidationUrl',
        ];
        $applePayConfigProvider = $this->getMockBuilder(ApplePayConfigProvider::class)->setConstructorArgs([
            $this->getObjectManager()->get(AssetRepository::class),
            $this->getObjectManager()->get(Config::class),
            $this->setupSdkFactory($this->getSdkMockWithWalletManager($requestData)),
            $this->getObjectManager()->get(Session::class),
            $this->getObjectManager()->get(Logger::class),
            $this->getObjectManager()->get(ResolverInterface::class),
            $this->getObjectManager()->get(PaymentConfig::class),
            $this->getObjectManager()->get(WriterInterface::class),
            $this->getObjectManager()->get(JsonHandler::class),
            $this->getObjectManager()->get(CheckoutFieldsUtil::class),
            $this->storeManager,
            $this->getObjectManager()->get(MerchantSessionRequest::class),
        ])->setMethodsExcept(['createApplePayMerchantSession', 'getSdk'])->getMock();

        self::assertSame(
            self::FAKE_APPLE_SESSION_RESPONSE,
            $applePayConfigProvider->createApplePayMerchantSession($requestData, $storeId)
        );
    }

    /**
     * @param array $requestData
     * @return MockObject
     */
    private function getSdkMockWithWalletManager(array $requestData): MockObject
    {
        $merchantSessionRequest = $this->getObjectManager()->get(MerchantSessionRequest::class)->addData($requestData);
        $sdk = $this->getMockBuilder(Sdk::class)
            ->disableOriginalConstructor()
            ->getMock();

        $walletManagerMock = $this->getMockBuilder(WalletManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockResponse = $this->getMockBuilder(MerchantSession::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockResponse->method('getMerchantSession')
            ->willReturn(self::FAKE_APPLE_SESSION_RESPONSE);

        $walletManagerMock->method('createApplePayMerchantSession')
            ->with($merchantSessionRequest)
            ->willReturn($mockResponse);

        $sdk->method('getWalletManager')
            ->willReturn($walletManagerMock);

        return $sdk;
    }
}
