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
 * Copyright © 2022 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 *
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Model\Ui\Gateway;

use Magento\Framework\Exception\LocalizedException;
use MultiSafepay\ConnectCore\Model\Ui\GenericConfigProvider;
use Magento\Framework\Exception\NoSuchEntityException;

class AfterpayConfigProvider extends GenericConfigProvider
{
    public const CODE = 'multisafepay_afterpay';
    private const AFTERPAY_LANGUAGE_URL_MAP = [
        'NL_NL' => 'https://www.afterpay.nl/nl/algemeen/betalen-met-afterpay/betalingsvoorwaarden',
        'BE_NL' => 'https://www.afterpay.be/be/footer/betalen-met-afterpay/betalingsvoorwaarden',
        'BE_FR' => 'https://www.afterpay.be/fr/footer/payer-avec-afterpay/conditions-de-paiement',
        'DEFAULT' => 'https://www.afterpay.nl/en/about/pay-with-afterpay/payment-conditions'
    ];

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getConfig(): array
    {
        return [
            'payment' => [
                $this->getCode() => [
                    'image' => $this->getImage(),
                    'is_preselected' => $this->isPreselected(),
                    'transaction_type' => $this->getTransactionType(),
                    'afterpay_terms_url' => $this->getAfterpayTermsAndConditionUrl(),
                ],
            ],
        ];
    }

    /**
     * Return the Afterpay terms and conditions URL according locale
     *
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getAfterpayTermsAndConditionUrl(): string
    {
        $billingCountryLanguageLocale = $this->getBillingCountryCodeFromQuote() . '_' . $this->getLanguageLocaleCode();

        foreach (self::AFTERPAY_LANGUAGE_URL_MAP as $afterPayLocale => $url) {
            if ($billingCountryLanguageLocale === $afterPayLocale) {
                return $url;
            }
        }

        return self::AFTERPAY_LANGUAGE_URL_MAP['DEFAULT'];
    }

    /**
     * Return the billing country code from the quote
     *
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getBillingCountryCodeFromQuote(): string
    {
        return $this->checkoutSession->getQuote()->getBillingAddress()->getCountry() ?? '';
    }

    /**
     * Return the last two characters from the locale
     *
     * @return string
     */
    private function getLanguageLocaleCode(): string
    {
        return substr($this->localeResolver->getLocale(), -2);
    }
}
