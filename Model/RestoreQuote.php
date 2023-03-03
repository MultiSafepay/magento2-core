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

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Quote\Model\ResourceModel\Quote\QuoteIdMask as QuoteIdMaskResource;
use MultiSafepay\ConnectCore\Api\RestoreQuoteInterface;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Util\OrderUtil;
use MultiSafepay\ConnectCore\Util\PaymentMethodUtil;

class RestoreQuote implements RestoreQuoteInterface
{
    /**
     * @var QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;

    /**
     * @var QuoteIdMaskResource
     */
    private $quoteIdMaskResource;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var SecureToken
     */
    private $secureToken;

    /**
     * @var OrderUtil
     */
    private $orderUtil;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var PaymentMethodUtil
     */
    private $paymentMethodUtil;

    /**
     * RestoreQuote constructor.
     *
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param QuoteIdMaskResource $quoteIdMaskResource
     * @param SecureToken $secureToken
     * @param OrderUtil $orderUtil
     * @param CartRepositoryInterface $cartRepository
     * @param PaymentMethodUtil $paymentMethodUtil
     * @param Logger $logger
     */
    public function __construct(
        QuoteIdMaskFactory $quoteIdMaskFactory,
        QuoteIdMaskResource $quoteIdMaskResource,
        SecureToken $secureToken,
        OrderUtil $orderUtil,
        CartRepositoryInterface $cartRepository,
        PaymentMethodUtil $paymentMethodUtil,
        Logger $logger
    ) {
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->quoteIdMaskResource = $quoteIdMaskResource;
        $this->secureToken = $secureToken;
        $this->orderUtil = $orderUtil;
        $this->cartRepository = $cartRepository;
        $this->paymentMethodUtil = $paymentMethodUtil;
        $this->logger = $logger;
    }

    /**
     * @param string $orderId
     * @param string $secureToken
     * @return string
     */
    public function restoreQuote(string $orderId, string $secureToken): string
    {
        if (!$this->secureToken->validate($orderId, $secureToken)) {
            return '';
        }

        try {
            $order = $this->orderUtil->getOrderByIncrementId($orderId);
            $quote = $this->cartRepository->get($order->getQuoteId());

            if (!$this->paymentMethodUtil->isMultisafepayCart($quote)) {
                return '';
            }

            $quote->setIsActive(1)->setReservedOrderId(null);
            $this->cartRepository->save($quote);

            if ($order->getCustomerIsGuest()) {
                $quoteIdMask = $this->quoteIdMaskFactory->create();
                $this->quoteIdMaskResource->load($quoteIdMask, $order->getQuoteId(), 'quote_id');

                return (string)$quoteIdMask->getMaskedId();
            }
        } catch (NoSuchEntityException $noSuchEntityException) {
            $this->logger->logException($noSuchEntityException);

            return 'Unable to restore quote';
        }

        return 'Quote restored';
    }
}
