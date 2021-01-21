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

namespace MultiSafepay\ConnectCore\Test\Integration\Gateway\Http;

use Magento\Payment\Gateway\Http\TransferInterface;
use MultiSafepay\ConnectCore\Gateway\Http\TransferFactory;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;

class TransferTest extends AbstractTestCase
{
    /**
     * Test to see if a dummy transfer object could be created
     */
    public function testTransferCreation()
    {
        /** @var TransferFactory $transferFactory */
        $transferFactory = $this->getObjectManager()->get(TransferFactory::class);

        $request = ['foo' => 'bar'];
        $transfer = $transferFactory->create($request);

        $this->assertInstanceOf(TransferInterface::class, $transfer);
        $this->assertEquals('POST', $transfer->getMethod());
        $this->assertEquals($request, $transfer->getBody());
        $this->assertEmpty($transfer->getHeaders());
    }
}
