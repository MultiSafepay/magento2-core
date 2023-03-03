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
use MultiSafepay\ConnectCore\Api\GuestPaymentMethodsInterface;

class GuestPaymentMethods implements GuestPaymentMethodsInterface
{
    /**
     * @var PaymentMethods
     */
    private $paymentMethods;

    /**
     * @var QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;

    /**
     * @var QuoteIdMaskResource
     */
    private $quoteIdMaskResource;

    /**
     * GuestPaymentMethods constructor.
     *
     * @param PaymentMethods $paymentMethods
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param QuoteIdMaskResource $quoteIdMaskResource
     */
    public function __construct(
        PaymentMethods $paymentMethods,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        QuoteIdMaskResource $quoteIdMaskResource
    ) {
        $this->paymentMethods = $paymentMethods;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->quoteIdMaskResource = $quoteIdMaskResource;
    }

    /**
     * @param string $cartId
     * @return array
     */
    public function getList(string $cartId): array
    {
        $quoteIdMask = $this->quoteIdMaskFactory->create();
        $this->quoteIdMaskResource->load($quoteIdMask, $cartId, 'masked_id');

        return $quoteIdMask->getQuoteId() ? $this->paymentMethods->getList((int)$quoteIdMask->getQuoteId()) : [];
    }
}
