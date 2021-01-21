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

use Magento\Framework\DataObject;
use Magento\Sales\Model\Order;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Gateway\Request\Builder\RedirectTransactionBuilder;

class RedirectTransactionBuilderTest extends AbstractTestCase
{
    /**
     * Test to see if this could be build
     *
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testBuild()
    {
        /** @var RedirectTransactionBuilder $genericTransactionBuilder */
        $genericTransactionBuilder = $this->getObjectManager()->get(RedirectTransactionBuilder::class);

        $stateObject = new DataObject();
        $buildSubject = [
            'payment' => $this->getPaymentDataObject(),
            'stateObject' => $stateObject
        ];
        $genericTransactionBuilder->build($buildSubject);

        $modifiedStateObject = $buildSubject['stateObject'];
        $this->assertEquals('pending', $modifiedStateObject->getStatus());
        $this->assertEquals(Order::STATE_NEW, $modifiedStateObject->getState());
        $this->assertEquals(false, $modifiedStateObject->getIsNotified());
    }
}
