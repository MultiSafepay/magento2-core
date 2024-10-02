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

namespace MultiSafepay\ConnectCore\CustomerData\PaymentRequest;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\Quote;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\ApplePayConfigProvider;

class ApplePayRequest
{
    /**
     * @var ApplePayConfigProvider
     */
    private $applePayConfigProvider;

    /**
     * @param ApplePayConfigProvider $applePayConfigProvider
     */
    public function __construct(
        ApplePayConfigProvider $applePayConfigProvider
    ) {
        $this->applePayConfigProvider = $applePayConfigProvider;
    }

    /**
     * Create the Apple Pay Direct request data
     *
     * @param Quote|null $quote
     * @return array
     * @throws NoSuchEntityException
     */
    public function create(?Quote $quote): ?array
    {
        if ($quote === null) {
            return null;
        }

        $storeId = $quote->getStoreId();
        $isActive = $this->applePayConfigProvider->isApplePayActive($storeId);

        if (!$isActive) {
            return null;
        }

        try {
            return [
                'isActive' => $this->applePayConfigProvider->isApplePayActive($storeId),
                'applePayButtonId' => ApplePayConfigProvider::APPLE_PAY_BUTTON_ID,
                'getMerchantSessionUrl' => $this->applePayConfigProvider->getApplePayMerchantSessionUrl($storeId),
                "cartItems" => $this->getQuoteItems($quote),
                "additionalTotalItems" => $this->getAdditionalTotalItems($quote)
            ];
        } catch (NoSuchEntityException $suchEntityException) {
            return null;
        }
    }

    /**
     * Get quote items
     *
     * @param Quote $quote
     * @return array
     */
    private function getQuoteItems(Quote $quote): array
    {
        $products = [];

        foreach ($quote->getAllVisibleItems() as $item) {
            $products[$item->getSku()] = [
                "label" => $this->generateProductLabel($item),
                "price" => (float)$item->getPrice() * (float)$item->getQty(),
            ];
        }

        return $products;
    }

    /**
     * Get additional line items from quote
     *
     * @param Quote $quote
     * @return array
     */
    private function getAdditionalTotalItems(Quote $quote): array
    {
        $result = [];

        $shippingAddress = $quote->getShippingAddress();
        $taxAmount = $shippingAddress->getTaxAmount();

        if ($shippingAddress->getShippingMethod()) {
            $result[] = [
                "label" => __("Shipping Method: (%1)", $shippingAddress->getShippingDescription())->render(),
                "amount" => $shippingAddress->getShippingInclTax(),
            ];
        }

        if ((float)$taxAmount) {
            $result[] = [
                "label" => "Tax:",
                "amount" => $taxAmount,
            ];
        }

        if ($coupon = (string)$quote->getCouponCode()) {
            $result[] = [
                "label" => __("Discount (%1)", $coupon)->render(),
                "amount" => $shippingAddress->getDiscountAmount(),
            ];
        }

        return $result;
    }

    /**
     * Generate product label
     *
     * @param CartItemInterface $item
     * @return string
     */
    private function generateProductLabel(CartItemInterface $item): string
    {
        $quantity = $item->getQty();
        $result = $item->getName() . " (SKU: " . $item->getSku() . ")";

        return $quantity > 0
            ? $result . "; Qty: " . (is_int($quantity) ? (int)$quantity
                : number_format((float)$quantity, 2, '.', ''))
            : $result;
    }
}
