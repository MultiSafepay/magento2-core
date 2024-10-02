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

use Magento\Customer\Model\Session;
use Magento\Customer\Model\Vat;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Tax\Model\Calculation;
use Magento\Tax\Model\Config as TaxConfig;

class TaxUtil
{
    /**
     * @var Calculation
     */
    private $calculation;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * GrandTotalUtil constructor.
     *
     * @param CartRepositoryInterface $quoteRepository
     * @param Calculation $calculation
     * @param ScopeConfigInterface $scopeConfig
     * @param Session $customerSession
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        Calculation $calculation,
        ScopeConfigInterface $scopeConfig,
        Session $customerSession
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->calculation = $calculation;
        $this->scopeConfig = $scopeConfig;
        $this->customerSession = $customerSession;
    }

    /**
     * @param OrderInterface $order
     * @return float
     * @throws NoSuchEntityException
     */
    public function getShippingTaxRate(OrderInterface $order): float
    {
        $quote = $this->quoteRepository->get($order->getQuoteId());

        return $this->getTaxRateByTaxRateIdAndCart(
            $quote,
            $this->scopeConfig->getValue(
                TaxConfig::CONFIG_XML_PATH_SHIPPING_TAX_CLASS,
                ScopeInterface::SCOPE_STORES,
                $quote->getStoreId()
            )
        );
    }

    /**
     * @param Quote $cart
     * @param $taxRateId
     * @return float
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getTaxRateByTaxRateIdAndCart(Quote $cart, $taxRateId): float
    {
        $request = $this->calculation->getRateRequest(
            $cart->getShippingAddress(),
            $cart->getBillingAddress(),
            $this->getCustomerTaxClassId($cart),
            $cart->getStore()
        );

        return (float)$this->calculation->getRate($request->setProductClassId($taxRateId));
    }

    /**
     * @param CartInterface $quote
     * @return int|null
     */
    private function getCustomerTaxClassId(CartInterface $quote): ?int
    {
        if ($this->scopeConfig->isSetFlag(
            Vat::XML_PATH_CUSTOMER_GROUP_AUTO_ASSIGN,
            ScopeInterface::SCOPE_STORE,
            $quote->getStoreId()
        )) {
            return (int)$this->customerSession->getCustomer()->getTaxClassId();
        }

        return (int)$quote->getCustomerTaxClassId();
    }
}
