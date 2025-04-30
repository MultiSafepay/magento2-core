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

namespace MultiSafepay\ConnectCore\Model\Ui\Giftcard;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use MultiSafepay\ConnectCore\Model\Ui\GenericGiftcardConfigProvider;

class EdenredGiftcardConfigProvider extends GenericGiftcardConfigProvider
{
    public const CODE = 'multisafepay_edenred';
    public const EDENCOM_COUPON_CODE = 'edencom';
    public const EDENECO_COUPON_CODE = 'edeneco';
    public const EDENRES_COUPON_CODE = 'edenres';
    public const EDENSPORTS_COUPON_CODE = 'edensports';
    public const CVE_COUPON_CODE = 'edenconsum';
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
                    'instructions' => $this->getInstructions(),
                    'coupons' => $this->getAvailableCouponsByQuote($this->checkoutSession->getQuote()),
                ],
            ],
        ];
    }

    /**
     * @param int|null $storeId
     * @return array
     */
    public function getAvailableCategoriesAndCoupons(?int $storeId = null): array
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
            self::CVE_COUPON_CODE => $this->getAvailableCategoriesByCouponCode(
                self::CVE_COUPON_CODE,
                $storeId
            ),
        ]);
    }

    /**
     * @param string $couponCode
     * @param int|null $storeId
     * @return array
     */
    public function getAvailableCategoriesByCouponCode(string $couponCode, ?int $storeId = null): array
    {
        if ($ids = ($this->getPaymentConfig($storeId)[$couponCode . '_categories'] ?? null)) {
            return explode(',', $ids);
        }

        return [];
    }

    /**
     * @param Quote $quote
     * @return array
     * @throws NoSuchEntityException
     */
    public function getAvailableCouponsByQuote(Quote $quote): array
    {
        return $this->getAvailableCouponsForSpecificItems($quote->getAllItems(), (int)$quote->getStoreId());
    }

    /**
     * @param Order $order
     * @return array
     */
    public function getAvailableCouponsByOrder(Order $order): array
    {
        return $this->getAvailableCouponsForSpecificItems($order->getAllItems(), (int)$order->getStoreId());
    }

    /**
     * @param array $items
     * @param int|null $storeId
     * @return array
     */
    public function getAvailableCouponsForSpecificItems(array $items, ?int $storeId = null): array
    {
        $result = [];
        // All available coupons with assigned categories in configuration panel
        $availableCategoriesAndCoupons = $this->getAvailableCategoriesAndCoupons($storeId);
        $temporaryResult = [];
        $counter = 0;

        foreach ($items as $item) {
            // Parent configurable products should be skipped, instead we have to check child products
            if (!$item->getHasParentId() && $item->getProduct()->getTypeId() === Configurable::TYPE_CODE) {
                continue;
            }

            // Assigned categories for current product
            $categoryIds = $item->getProduct()->getCategoryIds();

            foreach ($availableCategoriesAndCoupons as $couponCode => $availableCategoryIds) {
                // Check if we have intersection between product categories and coupon categories or if current
                // coupon assigned to all categories
                if (array_intersect($categoryIds, $availableCategoryIds)
                    || in_array(self::CONFIG_ALL_CATEGORIES_VALUE, $availableCategoryIds, true)
                ) {
                    // Set all available coupons for current product
                    $temporaryResult[] = $couponCode;
                }
            }

            // for first iteration/product we should save $temporaryResult to $result for next comparisons with the
            // $temporaryResult
            if ($counter === 0) {
                $result = $temporaryResult;

                // if first product doesn't have any available categories then we can stop the comparison process,
                // because the Edenred doesn't available for a current cart
                if (!$result) {
                    break;
                }
            } else {
                // compare if previous saved result for previous product have any intersected categories with a
                // current product and save this intersection
                $result = array_intersect($result, $temporaryResult);
            }

            $temporaryResult = [];
            $counter++;
        }

        // delete duplicate coupons
        return array_values(array_unique($result));
    }
}
