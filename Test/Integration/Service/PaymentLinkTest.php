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

use Exception;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\BankTransferConfigProvider;
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
     * Test that an ApiException is thrown when trying to get a payment link with invalid API keys
     *
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
     * Test adding a payment link to the order payment additional information and order comments
     *
     * @magentoDataFixture Magento/Sales/_files/order.php
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

    /**
     * Test adding a payment link to order comments in the frontend area with Bank Transfer payment method
     *
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @throws LocalizedException
     * @throws Exception
     */
    public function testAddPaymentLinkToOrderCommentsInFrontendWithBankTransfer(): void
    {
        $fakePaymentLink = 'https://test.com/';
        $order = $this->getOrderWithBankTransferPaymentMethod();

        $this->paymentLinkService->addPaymentLinkToOrderComments($order, $fakePaymentLink);

        self::assertEquals(
            __('Payment link for this transaction: %1', $fakePaymentLink)->render(),
            $order->getStatusHistoryCollection()->getFirstItem()->getComment()
        );
    }

    /**
     * Test adding a payment link to order comments in the frontend area with non-Bank Transfer payment method
     *
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @throws LocalizedException
     * @throws Exception
     */
    public function testAddPaymentLinkToOrderCommentsInFrontendWithoutNotification(): void
    {
        // Explicitly set frontend area for this test
        $this->getObjectManager()->get(State::class)->setAreaCode(Area::AREA_FRONTEND);

        /** @var Order $order */
        $order = $this->getOrderWithVisaPaymentMethod();
        $initialCommentCount = $order->getStatusHistoryCollection()->count();
        $fakePaymentLink = 'https://test.com/';

        $this->paymentLinkService->addPaymentLinkToOrderComments($order, $fakePaymentLink);

        // Should not add comment for non-bank transfer payments in frontend without notification
        self::assertEquals($initialCommentCount, $order->getStatusHistoryCollection()->count());
    }

    /**
     * Test adding a payment link to order comments with notification flag set to true
     *
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @throws LocalizedException
     * @throws Exception
     */
    public function testAddPaymentLinkToOrderCommentsWithNotification(): void
    {
        /** @var Order $order */
        $order = $this->getOrderWithVisaPaymentMethod();
        $fakePaymentLink = 'https://test.com?notification';

        $this->paymentLinkService->addPaymentLinkToOrderComments($order, $fakePaymentLink, true);

        self::assertEquals(
            __('Payment link for this transaction: %1', $fakePaymentLink)->render(),
            $order->getStatusHistoryCollection()->getFirstItem()->getComment()
        );
    }

    /**
     * Test that adding a payment link to order comments skips if the comment already exists
     *
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @throws LocalizedException
     * @throws Exception
     */
    public function testAddPaymentLinkToOrderCommentsSkipsIfAlreadyExists(): void
    {
        /** @var Order $order */
        $order = $this->getOrderWithVisaPaymentMethod();
        $payment = $order->getPayment();
        $fakePaymentLink = 'https://test.com?duplicate';

        // Set flag to indicate comment already exists
        $payment->setAdditionalInformation('has_multisafepay_paymentlink_comment', true);

        $initialCommentCount = $order->getStatusHistoryCollection()->count();
        $this->paymentLinkService->addPaymentLinkToOrderComments($order, $fakePaymentLink, true);

        // Should not add duplicate comment
        self::assertEquals($initialCommentCount, $order->getStatusHistoryCollection()->count());
    }

    /**
     * Create an order with Bank Transfer payment method
     *
     * @throws LocalizedException
     * @return Order
     */
    private function getOrderWithBankTransferPaymentMethod(): Order
    {
        /** @var Order $order */
        $order = $this->getOrder();
        $payment = $order->getPayment();
        $payment->setMethod(BankTransferConfigProvider::CODE);

        return $order;
    }
}
