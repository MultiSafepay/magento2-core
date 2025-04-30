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
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order\Email\Container\OrderIdentity;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\BankTransferConfigProvider;
use MultiSafepay\ConnectCore\Service\EmailSender;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;

class EmailSenderTest extends AbstractTestCase
{
    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     *
     * @throws LocalizedException
     * @throws Exception
     */
    public function testsendOrderConfirmationEmailWithEmailAlreadySent(): void
    {
        $this->getOrder()->setEmailSent(1);

        $emailSender = $this->getEmailSender()->sendOrderConfirmationEmail(
            $this->getOrder(),
            EmailSender::AFTER_TRANSACTION_EMAIL_TYPE
        );

        self::assertFalse($emailSender);
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     *
     * @throws LocalizedException
     * @throws Exception
     */
    public function testsendOrderConfirmationEmailWithEmailSendingOff(): void
    {
        $this->getOrder()->setEmailSent(0);

        $emailSender = $this->getEmailSender(false)->sendOrderConfirmationEmail(
            $this->getOrder(),
            EmailSender::AFTER_TRANSACTION_EMAIL_TYPE
        );

        self::assertFalse($emailSender);
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     *
     * @throws LocalizedException
     * @throws Exception
     */
    public function testsendOrderConfirmationEmailWithEmailSendingOn(): void
    {
        $this->getOrder()->setEmailSent(0);

        $emailSender = $this->getEmailSender(true)->sendOrderConfirmationEmail(
            $this->getOrder(),
            EmailSender::AFTER_TRANSACTION_EMAIL_TYPE
        );

        self::assertTrue($emailSender);
    }

    /**
     * @magentoConfigFixture default_store multisafepay/general/order_confirmation_email after_transaction
     */
    public function testcheckOrderConfirmationBeforeTransactionWithEmptyGatewaySpecificSettingIsFalse(): void
    {
        self::assertFalse(
            $this->getEmailSender()->checkOrderConfirmationBeforeTransaction(BankTransferConfigProvider::CODE)
        );
    }

    /**
     * @magentoConfigFixture default_store multisafepay/general/order_confirmation_email before_transaction
     */
    public function testcheckOrderConfirmationBeforeTransactionWithEmptyGatewaySpecificSettingIsTrue(): void
    {
        self::assertTrue(
            $this->getEmailSender()->checkOrderConfirmationBeforeTransaction(BankTransferConfigProvider::CODE)
        );
    }

    /**
     * @magentoConfigFixture default_store multisafepay/general/order_confirmation_email after_transaction
     * @magentoConfigFixture default_store payment/multisafepay_banktransfer/order_confirmation_email after_transaction
     */
    public function testcheckOrderConfirmationBeforeTransactionWithGatewaySpecificSettingIsFalse(): void
    {
        self::assertFalse(
            $this->getEmailSender()->checkOrderConfirmationBeforeTransaction(BankTransferConfigProvider::CODE)
        );
    }

    /**
     * @magentoConfigFixture default_store multisafepay/general/order_confirmation_email after_transaction
     * @magentoConfigFixture default_store payment/multisafepay_banktransfer/order_confirmation_email before_transaction
     */
    public function testcheckOrderConfirmationBeforeTransactionWithGatewaySpecificSettingIsTrue(): void
    {
        self::assertTrue(
            $this->getEmailSender()->checkOrderConfirmationBeforeTransaction(BankTransferConfigProvider::CODE)
        );
    }

    /**
     * Test to see if an invoice could be sent
     *
     * @magentoConfigFixture default_store sales_email/invoice/enabled 1
     * @throws Exception
     */
    public function testSendInvoiceEmailWhileEmailHasBeenSentAlready(): void
    {
        $orderPayment = $this->getNewOrderPayment();
        $invoice = $this->getNewInvoice();
        $invoice->setEmailSent(true);

        $emailSender = $this->getEmailSender();
        self::assertFalse($emailSender->sendInvoiceEmail($orderPayment, $invoice));
    }

    /**
     * Test to see if an invoice could be sent
     *
     * @magentoConfigFixture default_store sales_email/invoice/enabled 1
     * @throws Exception
     */
    public function testSendInvoiceEmailWhileAllGoesWell(): void
    {
        self::assertEquals(1, $this->getScopeConfig()->getValue('sales_email/invoice/enabled'));

        $orderPayment = $this->getNewOrderPayment();
        $invoice = $this->getNewInvoice();
        $emailSender = $this->getEmailSender();
        self::assertTrue($emailSender->sendInvoiceEmail($orderPayment, $invoice));
    }

    /**
     * @param bool $isEnabled
     * @return EmailSender
     */
    private function getEmailSender(?bool $isEnabled = null): EmailSender
    {
        $orderIdentityMock = $this->getMockBuilder(OrderIdentity::class)
            ->disableOriginalConstructor()
            ->getMock();

        $orderIdentityMock->method('isEnabled')->willReturn($isEnabled);

        $arguments = [];
        $arguments['invoiceSender'] = $this->createMock(InvoiceSender::class);
        $arguments['orderSender'] = $this->createMock(OrderSender::class);
        $arguments['orderIdentity'] = $orderIdentityMock;

        return $this->getObjectManager()->create(EmailSender::class, $arguments);
    }

    /**
     * @return ScopeConfigInterface
     */
    private function getScopeConfig(): ScopeConfigInterface
    {
        return $this->getObjectManager()->get(ScopeConfigInterface::class);
    }

    /**
     * @return OrderPaymentInterface
     */
    private function getNewOrderPayment(): OrderPaymentInterface
    {
        return $this->getObjectManager()->get(OrderPaymentInterface::class);
    }

    /**
     * @return InvoiceInterface
     */
    private function getNewInvoice(): InvoiceInterface
    {
        return $this->getObjectManager()->create(InvoiceInterface::class);
    }
}
