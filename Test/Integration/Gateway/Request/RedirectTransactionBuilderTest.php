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

namespace MultiSafepay\ConnectCore\Test\Integration\Gateway\Request;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\BankTransferConfigProvider;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Gateway\Request\Builder\RedirectTransactionBuilder;

class RedirectTransactionBuilderTest extends AbstractTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @dataProvider       builderDataProvider
     *
     * @param string $paymentMethod
     * @param string $status
     * @param string $state
     * @param bool $isNotified
     * @param string $areaCode
     * @throws LocalizedException
     */
    public function testBuildBankTransfer(
        string $paymentMethod,
        string $status,
        string $state,
        bool $isNotified,
        string $areaCode
    ): void {
        if ($paymentMethod) {
            $this->getOrder()->getPayment()->setMethod($paymentMethod);
        }

        $this->getAreaStateObject()->setAreaCode($areaCode);

        $modifiedStateObject = $this->prepareRedirectTransactionBuilder();

        self::assertEquals($status, $modifiedStateObject->getStatus());
        self::assertEquals($state, $modifiedStateObject->getState());
        self::assertEquals($isNotified, $modifiedStateObject->getIsNotified());
    }

    /**
     * @return DataObject
     * @throws LocalizedException
     */
    private function prepareRedirectTransactionBuilder(): DataObject
    {
        $buildSubject = [
            'payment' => $this->getPaymentDataObject(),
            'stateObject' => new DataObject(),
        ];
        $this->getRedirectTransactionBuilder()->build($buildSubject);

        return $buildSubject['stateObject'];
    }

    /**
     * @return array[]
     */
    public function builderDataProvider(): array
    {
        return [
            [
                '',
                'pending_payment',
                Order::STATE_PENDING_PAYMENT,
                false,
                Area::AREA_FRONTEND,
            ],
            [
                '',
                'pending',
                Order::STATE_NEW,
                false,
                Area::AREA_ADMINHTML,
            ],
            [
                BankTransferConfigProvider::CODE,
                'pending',
                Order::STATE_NEW,
                false,
                Area::AREA_FRONTEND,
            ],
        ];
    }

    /**
     * @return RedirectTransactionBuilder
     */
    private function getRedirectTransactionBuilder(): RedirectTransactionBuilder
    {
        return $this->getObjectManager()->get(RedirectTransactionBuilder::class);
    }

    /**
     * @return State
     */
    private function getAreaStateObject(): State
    {
        return $this->getObjectManager()->get(State::class);
    }
}
