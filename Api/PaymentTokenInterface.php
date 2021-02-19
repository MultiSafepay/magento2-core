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

namespace MultiSafepay\ConnectCore\Api;

interface PaymentTokenInterface
{
    public const TYPE = 'type';
    public const MASKED_CC = 'maskedCC';
    public const EXPIRATION_DATE = 'expirationDate';
    public const ICON_URL = 'url';
    public const ICON_HEIGHT = 'height';
    public const ICON_WIDTH = 'width';
}
