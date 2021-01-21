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

namespace MultiSafepay\ConnectCore\Util;

class IpAddressUtil
{
    /**
     * @param string $ipAddress
     * @return string
     */
    public function validateIpAddress(string $ipAddress): string
    {
        $ipAddressList = explode(',', $ipAddress);
        return (string) trim(reset($ipAddressList));
    }
}
