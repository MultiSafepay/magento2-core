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
 * Copyright © 2020 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 *
 */

declare(strict_types=1);

namespace MultiSafepay\Test\Integration\Util;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote\Address\ToOrderAddress;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Util\TaxUtil;

class TaxUtilTest extends AbstractTestCase
{
    /**
     * @var TaxUtil
     */
    private $taxUtil;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->taxUtil = $this->getObjectManager()->create(TaxUtil::class);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     *
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function testGetShippingTaxRate(): void
    {
        $order = $this->getOrder();
        $quote = $this->getQuote('tableRate');
        $addressConverter = $this->getObjectManager()->create(ToOrderAddress::class);

        $order->setQuoteId($quote->getId())
            ->setStoreId($quote->getStoreId())
            ->setShippingAddress($addressConverter->convert($quote->getShippingAddress()))
            ->setBillingAddress($addressConverter->convert($quote->getBillingAddress()));

        self::assertEquals(0, $this->taxUtil->getShippingTaxRate($order));
    }
}
