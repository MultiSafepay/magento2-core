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

use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;
use MultiSafepay\ConnectCore\Model\Ui\GenericGiftcardConfigProvider;

class EdenredGiftcardConfigProvider extends GenericGiftcardConfigProvider
{
    public const CODE = 'multisafepay_edenred';
    public const EDENCOM_COUPON_CODE = 'edencom';
    public const EDENECO_COUPON_CODE = 'edeneco';
    public const EDENRES_COUPON_CODE = 'edenres';
    public const EDENSPORTS_COUPON_CODE = 'edensports';
    public const CONFIG_ALL_CATEGORIES_VALUE = 'all_categories';

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     * @throws LocalizedException
     */
    public function getConfig(): array
    {
        return [
            'payment' => [
                $this->getCode() => [
                    'image' => $this->getImage(),
                    'is_preselected' => $this->isPreselected(),
                    'transaction_type' => $this->getTransactionType(),
                    'coupons' => $this->getAvailableCouponsByQuote($this->checkoutSession->getQuote()),
                ],
            ],
        ];
    }

    /**
     * @param int|null $storeId
     * @return array
     */
    public function getAvailableCategoriesAndCoupons(int $storeId = null): array
    {
        return array_filter([
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
        ]);
    }

    /**
     * @param string $couponCode
     * @param int|null $storeId
     * @return array
     */
    public function getAvailableCategoriesByCouponCode(string $couponCode, int $storeId = null): array
    {
        if ($ids = $this->getPaymentConfig($storeId)[$couponCode . '_categories']) {
            return explode(',', $ids);
        }

        return [];
    }

    /**
     * @param CartInterface $quote
     * @return array
     */
    public function getAvailableCouponsByQuote(CartInterface $quote): array
    {
        return $this->getAvailableCouponsForSpecificItems($quote->getAllItems(), (int)$quote->getStoreId());
    }

    /**
     * @param OrderInterface $order
     * @return array
     */
    public function getAvailableCouponsByOrder(OrderInterface $order): array
    {
        return $this->getAvailableCouponsForSpecificItems($order->getAllItems(), (int)$order->getStoreId());
    }

    /**
     * @param array $items
     * @param int|null $storeId
     * @return array
     */
    public function getAvailableCouponsForSpecificItems(array $items, int $storeId = null): array
    {
        $result = [];
        $availableCategoriesAndCoupons = $this->getAvailableCategoriesAndCoupons($storeId);
        $temporaryResult = [];
        $counter = 0;

        foreach ($items as $item) {
            $categoryIds = $item->getProduct()->getCategoryIds();

            foreach ($availableCategoriesAndCoupons as $couponCode => $availableCategoryIds) {
                if (array_intersect($categoryIds, $availableCategoryIds)
                    || in_array(self::CONFIG_ALL_CATEGORIES_VALUE, $availableCategoryIds)
                ) {
                    $temporaryResult[] = $couponCode;
                }
            }

            if ($counter === 0) {
                $result = $temporaryResult;

                if (!$result) {
                    break;
                }
            } else {
                $result = array_intersect($result, $temporaryResult);
            }

            $temporaryResult = [];
            $counter++;
        }

        return array_unique($result);
    }
}
