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
use MultiSafepay\ConnectCore\Observer\Gateway\AfterpayDataAssignObserver;
use MultiSafepay\ConnectCore\Observer\Gateway\DirectBankTransferDataAssignObserver;
use MultiSafepay\ConnectCore\Observer\Gateway\DirectDebitDataAssignObserver;
use MultiSafepay\ConnectCore\Observer\Gateway\EinvoicingDataAssignObserver;
use MultiSafepay\ConnectCore\Observer\Gateway\IdealDataAssignObserver;
use MultiSafepay\ConnectCore\Observer\Gateway\PayafterDataAssignObserver;

class EventsTest extends EventsTestCase
{
    /**
     * @throws Exception
     */
    public function testForAdminObservers()
    {
        $this->findObserverForEvent(
            IdealDataAssignObserver::class,
            'payment_method_assign_data_multisafepay_ideal'
        );

        $this->findObserverForEvent(
            PayafterDataAssignObserver::class,
            'payment_method_assign_data_multisafepay_payafter'
        );

        $this->findObserverForEvent(
            AfterpayDataAssignObserver::class,
            'payment_method_assign_data_multisafepay_afterpay'
        );

        $this->findObserverForEvent(
            DirectBankTransferDataAssignObserver::class,
            'payment_method_assign_data_multisafepay_directbanktransfer'
        );

        $this->findObserverForEvent(
            DirectDebitDataAssignObserver::class,
            'payment_method_assign_data_multisafepay_directdebit'
        );

        $this->findObserverForEvent(
            EinvoicingDataAssignObserver::class,
            'payment_method_assign_data_multisafepay_einvoicing'
        );
    }
}
