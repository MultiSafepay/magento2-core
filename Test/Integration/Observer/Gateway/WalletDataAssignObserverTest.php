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

namespace MultiSafepay\ConnectCore\Test\Integration\Observer\Gateway;

use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\TransactionTypeBuilder;
use MultiSafepay\ConnectCore\Observer\Gateway\WalletDataAssignObserver;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;

class WalletDataAssignObserverTest extends AbstractTestCase
{
    private const TEST_TOKEN = 'a3f063403e62e20ac64ea8a7d96bcd81887143ce61799729b217725358b6bbad';

    /**
     * @var WalletDataAssignObserver
     */
    private $walletDataAssignObserver;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->walletDataAssignObserver = $this->getObjectManager()->create(WalletDataAssignObserver::class);
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     * @magentoConfigFixture default_store multisafepay/general/mode 0
     *
     * @throws LocalizedException
     */
    public function testSetTokenAsAdditionalData(): void
    {
        $payment = $this->getPaymentDataObject(TransactionTypeBuilder::TRANSACTION_TYPE_REDIRECT_VALUE)
            ->getPayment();

        $fakeObserver = $this->getObserverObjectWithData(
            [
                'data' => new DataObject(
                    [
                        PaymentInterface::KEY_ADDITIONAL_DATA => [
                        ],
                    ]
                ),
                AbstractDataAssignObserver::MODEL_CODE => $payment,
            ]
        );

        $this->walletDataAssignObserver->execute($fakeObserver);
        $additionalInformation = $payment->getAdditionalInformation();

        self::assertSame(
            TransactionTypeBuilder::TRANSACTION_TYPE_REDIRECT_VALUE,
            $additionalInformation['transaction_type']
        );
        self::isFalse(isset($additionalInformation['payment_token']));

        $fakeObserver = $this->getObserverObjectWithData(
            [
                'data' => new DataObject(
                    [
                        PaymentInterface::KEY_ADDITIONAL_DATA => [
                            'payload' => json_encode([
                                'token' => self::TEST_TOKEN,
                                'browser_info' => ''
                            ]),
                        ],
                    ]
                ),
                AbstractDataAssignObserver::MODEL_CODE => $payment,
            ]
        );

        $this->walletDataAssignObserver->execute($fakeObserver);
        $additionalInformation = $payment->getAdditionalInformation();

        self::assertSame(
            TransactionTypeBuilder::TRANSACTION_TYPE_DIRECT_VALUE,
            $additionalInformation['transaction_type']
        );
        self::assertSame(self::TEST_TOKEN, $additionalInformation['payment_token']);
    }

    /**
     * @param array $data
     * @return Observer
     */
    private function getObserverObjectWithData(array $data): Observer
    {
        return $this->getObjectManager()->create(Observer::class, [
            'data' => [
                'event' => $this->getObjectManager()->create(Event::class, ['data' => $data]),
            ],
        ]);
    }
}
