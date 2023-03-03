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

namespace MultiSafepay\ConnectCore\Api;

interface GuestPaymentUrlInterface
{
    /**
     * GET for paymentUrl api
     * @param int $orderId
     * @param string $cartId
     * @return string
     */
    public function getPaymentUrl(int $orderId, string $cartId): string;
}
