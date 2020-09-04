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

namespace MultiSafepay\ConnectCore\Util;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\StoreManagerInterface;

class CurrencyUtil
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Currency constructor.
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(StoreManagerInterface $storeManager)
    {
        $this->storeManager = $storeManager;
    }

    /**
     * @param OrderInterface $order
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getCurrencyCodeByOrder(OrderInterface $order): string
    {
        $currencyCode = $order->getBaseCurrencyCode();
        if (!empty($currencyCode)) {
            return $currencyCode;
        }

        $currencyCode = $this->storeManager->getStore($order->getStoreId())->getCurrentCurrency()->getCode();
        if (!empty($currencyCode)) {
            return $currencyCode;
        }

        throw new LocalizedException(__('No currency code set'));
    }
}
