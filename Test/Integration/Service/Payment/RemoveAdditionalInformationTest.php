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

namespace MultiSafepay\ConnectCore\Test\Integration\Service\Payment;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use MultiSafepay\ConnectCore\Service\Payment\RemoveAdditionalInformation;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;

class RemoveAdditionalInformationTest extends AbstractTestCase
{
    /**
     * @var RemoveAdditionalInformation
     */
    private $removeAdditionalInformation;

    /**
     * @var OrderPaymentRepositoryInterface
     */
    private $orderPaymentRepository;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->removeAdditionalInformation = $this->getObjectManager()->get(RemoveAdditionalInformation::class);
        $this->orderPaymentRepository = $this->getObjectManager()->get(OrderPaymentRepositoryInterface::class);
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     * @magentoConfigFixture default_store multisafepay/general/mode 0
     * @throws LocalizedException
     */
    public function testCancelMultisafepayOrderPretransactionSuccess(): void
    {
        $order = $this->getOrderWithVisaPaymentMethod();
        $payment = $order->getPayment();
        $payment->setAdditionalInformation(
            [
                'account_holder_iban' => '10-10-1990',
                'account_number' => 'NL87ABNA0000000004',
                'account_holder_bic' => 'mr',
                'test_key' => 'test'
            ]
        )->save();
        $this->removeAdditionalInformation->execute($order);

        $orderPayment = $this->orderPaymentRepository->get($order->getPayment()->getEntityId());

        self::assertEquals(
            ['test_key' => 'test'],
            $orderPayment->getAdditionalInformation()
        );
    }
}
