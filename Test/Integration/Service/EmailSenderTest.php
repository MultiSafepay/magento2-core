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
 * Copyright © 2021 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 *
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Test\Integration\Service;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use MultiSafepay\ConnectCore\Service\EmailSender;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;

class EmailSenderTest extends AbstractTestCase
{
    /**
     * Test to see if an invoice could be sent
     *
     * @magentoConfigFixture default_store sales_email/invoice/enabled 1
     * @throws Exception
     */
    public function testSendInvoiceEmailWhileEmailHasBeenSentAlready()
    {
        $orderPayment = $this->getNewOrderPayment();
        $invoice = $this->getNewInvoice();
        $invoice->setEmailSent(true);

        $emailSender = $this->getEmailSender();
        $this->assertFalse($emailSender->sendInvoiceEmail($orderPayment, $invoice));
    }

    /**
     * Test to see if an invoice could be sent
     *
     * @magentoConfigFixture default_store sales_email/invoice/enabled foobar
     * @throws Exception
     */
    /*public function testSendInvoiceEmailWhenEmailingHasBeenDisabled()
    {
        $this->assertEquals(0, $this->getScopeConfig()->getValue('sales_email/invoice/enabled'));

        $orderPayment = $this->getNewOrderPayment();
        $invoice = $this->getNewInvoice();
        $emailSender = $this->getEmailSender();
        $this->assertFalse($emailSender->sendInvoiceEmail($orderPayment, $invoice));
    }*/

    /**
     * Test to see if an invoice could be sent
     *
     * @magentoConfigFixture default_store sales_email/invoice/enabled 1
     * @throws Exception
     */
    public function testSendInvoiceEmailWhileAllGoesWell()
    {
        $this->assertEquals(1, $this->getScopeConfig()->getValue('sales_email/invoice/enabled'));

        $orderPayment = $this->getNewOrderPayment();
        $invoice = $this->getNewInvoice();
        $emailSender = $this->getEmailSender();
        $this->assertTrue($emailSender->sendInvoiceEmail($orderPayment, $invoice));
    }

    /**
     * @return EmailSender
     */
    private function getEmailSender(): EmailSender
    {
        $arguments = [];
        $arguments['invoiceSender'] = $this->createMock(InvoiceSender::class);
        $arguments['orderSender'] = $this->createMock(OrderSender::class);
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
