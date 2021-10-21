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

namespace MultiSafepay\ConnectCore\Plugin\Payment\Gateway\Config;

use Magento\Payment\Helper\Data;
use Magento\Payment\Gateway\Config\Config;

class ConfigPlugin
{
    /**
     * @param Data $subject
     * @param array $result
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetValue(Config $subject, $field, $storeId, $result)
    {
        //if (isset($result[GenericGatewayConfigProvider::CODE])) {
        //    unset($result[GenericGatewayConfigProvider::CODE]);
        //}

        return $result;
    }
}
