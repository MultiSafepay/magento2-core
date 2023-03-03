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

namespace MultiSafepay\ConnectCore\Test\Integration\Service;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use MultiSafepay\ConnectCore\Service\PaymentLink;
use MultiSafepay\ConnectCore\Test\Integration\Payment\AbstractPaymentTestCase;
use MultiSafepay\Exception\ApiException;
use Psr\Http\Client\ClientExceptionInterface;

class PaymentLinkTest extends AbstractPaymentTestCase
{
    /**
     * @var PaymentLink
     */
    private $paymentLinkService;

    /**
     * @throws LocalizedException
     */
    protected function setUp(): void
    {
        $this->paymentLinkService = $this->getObjectManager()->create(PaymentLink::class);
        $this->getObjectManager()->get(State::class)->setAreaCode(Area::AREA_ADMINHTML);
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store multisafepay/general/live_api_key livekey
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     * @throws LocalizedException
     * @throws ClientExceptionInterface
     */
    public function testGetPaymentLinkByOrder(): void
    {
        $order = $this->getOrderWithVisaPaymentMethod();

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Invalid API key');

        $this->paymentLinkService->getPaymentLinkByOrder($order);
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @throws LocalizedException
     */
    public function testAddPaymentLink(): void
    {
        $fakePaymentLink = 'https://test.com?12345';
        $order = $this->getOrderWithVisaPaymentMethod();
        $payment = $order->getPayment();
        $this->paymentLinkService->addPaymentLink($order, $fakePaymentLink);

        self::assertEquals(
            $fakePaymentLink,
            $payment->getAdditionalInformation(PaymentLink::MULTISAFEPAY_PAYMENT_LINK_PARAM_NAME)
        );
        self::assertEquals(
            __('Payment link for this transaction: %1', $fakePaymentLink)->render(),
            $order->getStatusHistoryCollection()->getFirstItem()->getComment()
        );
    }
}
