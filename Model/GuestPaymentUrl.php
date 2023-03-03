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

namespace MultiSafepay\ConnectCore\Model;

use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Quote\Model\ResourceModel\Quote\QuoteIdMask as QuoteIdMaskResource;
use MultiSafepay\ConnectCore\Api\GuestPaymentUrlInterface;

class GuestPaymentUrl implements GuestPaymentUrlInterface
{
    /**
     * @var PaymentUrl
     */
    private $paymentUrl;

    /**
     * @var QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;

    /**
     * @var QuoteIdMaskResource
     */
    private $quoteIdMaskResource;

    public function __construct(
        PaymentUrl $paymentUrl,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        QuoteIdMaskResource $quoteIdMaskResource
    ) {
        $this->paymentUrl = $paymentUrl;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->quoteIdMaskResource = $quoteIdMaskResource;
    }

    /**
     * @param int $orderId
     * @param string $cartId
     * @return string
     */
    public function getPaymentUrl(int $orderId, string $cartId): string
    {
        $quoteIdMask = $this->quoteIdMaskFactory->create();
        $this->quoteIdMaskResource->load($quoteIdMask, $cartId, 'masked_id');

        if ($quoteIdMask->getQuoteId() === null) {
            return '';
        }

        return $this->paymentUrl->getPaymentUrl($orderId, null, (int)$quoteIdMask->getQuoteId());
    }
}
