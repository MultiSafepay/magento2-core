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

namespace MultiSafepay\ConnectCore\Model\Ui\Giftcard;

use MultiSafepay\ConnectCore\Model\Ui\GenericGiftcardConfigProvider;

class EdenredGiftcardConfigProvider extends GenericGiftcardConfigProvider
{
    public const CODE = 'multisafepay_edenred';
    public const EDENCOM_COUPON_CODE = 'edencom';
    public const EDENECO_COUPON_CODE = 'edeneco';
    public const EDENRES_COUPON_CODE = 'edenres';
    public const EDENSPORTS_COUPON_CODE = 'edensports';

    public function getAvailableCategoriesAndCoupons(int $storeId = null): array
    {
        return [
            self::EDENCOM_COUPON_CODE => $this->getAvailableCategoriesByCouponCode(
                self::EDENCOM_COUPON_CODE,
                $storeId
            ),
            self::EDENECO_COUPON_CODE => $this->getAvailableCategoriesByCouponCode(
                self::EDENECO_COUPON_CODE,
                $storeId
            ),
            self::EDENRES_COUPON_CODE => $this->getAvailableCategoriesByCouponCode(
                self::EDENRES_COUPON_CODE,
                $storeId
            ),
            self::EDENSPORTS_COUPON_CODE => $this->getAvailableCategoriesByCouponCode(
                self::EDENSPORTS_COUPON_CODE,
                $storeId
            ),
        ];
    }

    /**
     * @param string $couponCode
     * @param int|null $storeId
     * @return array
     */
    public function getAvailableCategoriesByCouponCode(string $couponCode, int $storeId = null): array
    {
        return $this->getPaymentConfig($storeId)[$couponCode . '_categories'];
    }
}
