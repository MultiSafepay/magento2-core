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

namespace MultiSafepay\ConnectCore\Test\Integration;

use Exception;
use Magento\Framework\Event\ConfigInterface as EventConfiguration;
use Magento\Framework\Event\ObserverInterface;

abstract class EventsTestCase extends AbstractTestCase
{
    /**
     * @param string $observerClass
     * @param string $eventName
     * @return ObserverInterface
     * @throws Exception
     */
    protected function findObserverForEvent(string $observerClass, string $eventName): ObserverInterface
    {
        /** @var EventConfiguration $eventConfiguration */
        $eventConfiguration = $this->getObjectManager()->get(EventConfiguration::class);
        $observers = $eventConfiguration->getObservers($eventName);
        $foundObservers = [];
        foreach ($observers as $observerData) {
            $observer = $this->getObjectManager()->get($observerData['instance']);
            if ($observer instanceof $observerClass) {
                return $observer;
            }

            $foundObservers[] = $observerData['instance'];
        }

        $msg = 'No observer "' . $observerClass . '" found for event "' . $eventName . '": ';
        $msg .= implode(',', $foundObservers);
        throw new Exception($msg);
    }
}
