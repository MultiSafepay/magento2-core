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

namespace MultiSafepay\ConnectCore\Util;

class LegacyUtil
{
    public const LEGACY_AFTERPAY_CODE = 'afterpaymsp';
    public const LEGACY_KLARNA_CODE = 'klarnainvoice';
    public const LEGACY_PAYAFTER_CODE = 'betaalnaontvangst';
    public const LEGACY_EINVOICING_CODE = 'einvoice';
}
