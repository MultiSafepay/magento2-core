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

namespace MultiSafepay\ConnectCore\Test\Integration\Model;

use Magento\Framework\Exception\LocalizedException;
use MultiSafepay\ConnectCore\Model\StockReducer;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;

class StockReducerTest extends AbstractTestCase
{
    /**
     * Test to the stock could be reduced from the order
     *
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @throws LocalizedException
     */
    public function testReduce()
    {
        /** @var StockReducer $stockReducer */
        $stockReducer = $this->getObjectManager()->get(StockReducer::class);
        $stockReducer->reduce($this->getOrder());
    }
}
