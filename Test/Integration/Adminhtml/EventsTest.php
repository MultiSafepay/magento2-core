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

namespace MultiSafepay\ConnectCore\Test\Integration\Adminhtml;

use Exception;
use MultiSafepay\ConnectCore\Observer\OrderPlaceAfterObserver;
use MultiSafepay\ConnectCore\Observer\ShipmentSaveAfterObserver;
use MultiSafepay\ConnectCore\Test\Integration\EventsTestCase;

class EventsTest extends EventsTestCase
{
    /**
     * @magentoAppArea adminhtml
     * @throws Exception
     *
     */
    public function testForAdminObservers()
    {
        $this->findObserverForEvent(
            ShipmentSaveAfterObserver::class,
            'sales_order_shipment_save_after'
        );

        $this->findObserverForEvent(
            OrderPlaceAfterObserver::class,
            'sales_order_place_after'
        );
    }
}
