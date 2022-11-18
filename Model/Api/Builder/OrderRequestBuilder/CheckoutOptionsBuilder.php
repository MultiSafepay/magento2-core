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

namespace MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\TaxTable\TaxRate;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\TaxTable\TaxRule;

class CheckoutOptionsBuilder
{
    /**
     * Create the checkout_options argument and add 0 tax table
     *
     * @param OrderInterface $order
     * @param OrderPaymentInterface $payment
     * @param OrderRequest $orderRequest
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function build(
        OrderInterface $order,
        OrderPaymentInterface $payment,
        OrderRequest $orderRequest
    ): void {
        if ($orderRequest->getShoppingCart() === null) {
            return;
        }

        if ($orderRequest->getCheckoutOptions()->getTaxTable() === null) {
            return;
        }

        $shoppingCart = $orderRequest->getShoppingCart()->getData();

        if (isset($shoppingCart['items'])) {
            foreach ($shoppingCart['items'] as $item) {
                if ($item['tax_table_selector'] === '0') {
                    return;
                }
            }
        }

        $taxRate = (new TaxRate())->addRate(0);
        $taxRule = (new TaxRule())->addTaxRate($taxRate)->addName('0');
        $orderRequest->getCheckoutOptions()->getTaxTable()->addTaxRule($taxRule);
    }
}
