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

use Magento\Framework\Exception\LocalizedException;
use MultiSafepay\Api\Base\Response;
use MultiSafepay\Api\TransactionManager;
use MultiSafepay\Api\Transactions\UpdateRequest;
use MultiSafepay\ConnectCore\Service\Order\AddInvoicesDataToTransactionAndSendEmail;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Util\InvoiceUtil;
use Psr\Http\Client\ClientExceptionInterface;

class AddInvoicesDataToTransactionAndSendEmailTest extends AbstractTestCase
{
    /**
     * @var AddInvoicesDataToTransactionAndSendEmail
     */
    private $addInvoicesDataToTransactionAndSendEmail;

    /**
     * @var InvoiceUtil
     */
    private $invoiceUtil;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->invoiceUtil = $this->getObjectManager()->get(InvoiceUtil::class);
        $this->addInvoicesDataToTransactionAndSendEmail =
            $this->getObjectManager()->get(AddInvoicesDataToTransactionAndSendEmail::class);
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoDataFixture   Magento/Sales/_files/invoice.php
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     * @magentoConfigFixture default_store multisafepay/general/mode 0
     * @throws ClientExceptionInterface
     * @throws LocalizedException
     */
    public function testSendInoiceEmail(): void
    {
        $order = $this->getOrderWithVisaPaymentMethod();
        $orderId = $order->getIncrementId();

        self::assertNull($this->invoiceUtil->getLastCreatedInvoiceByOrderId((string)$order->getId())->getEmailSent());

        $this->addInvoicesDataToTransactionAndSendEmail->execute(
            $order,
            $order->getPayment(),
            $this->getTransactionManagerMock($orderId)
        );

        self::assertTrue(
            (bool)$this->invoiceUtil->getLastCreatedInvoiceByOrderId((string)$order->getId())->getEmailSent()
        );
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoDataFixture   Magento/Sales/_files/invoice.php
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     * @magentoConfigFixture default/sales_email/invoice/enabled 0
     * @throws ClientExceptionInterface
     * @throws LocalizedException
     */
    public function testSendInoiceEmailWithDisabledEmails(): void
    {
        $order = $this->getOrderWithVisaPaymentMethod();

        $this->addInvoicesDataToTransactionAndSendEmail->execute(
            $order,
            $order->getPayment(),
            $this->getTransactionManagerMock($order->getIncrementId())
        );

        self::assertFalse(
            (bool)$this->invoiceUtil->getLastCreatedInvoiceByOrderId((string)$order->getId())->getEmailSent()
        );
    }

    /**
     * @param string $orderId
     * @return TransactionManager
     */
    private function getTransactionManagerMock(string $orderId): TransactionManager
    {
        $transactionManagerMock = $this->getMockBuilder(TransactionManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockResponse = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockResponse->expects(self::once())
            ->method('getResponseData')
            ->willReturn([]);

        $transactionManagerMock->expects(self::once())
            ->method('update')
            ->with($orderId, $this->getObjectManager()->get(UpdateRequest::class))
            ->willReturn($mockResponse);

        return $transactionManagerMock;
    }
}
