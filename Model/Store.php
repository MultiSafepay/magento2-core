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
 * Copyright © 2021 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 *
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use MultiSafepay\ConnectCore\Logger\Logger;

class Store
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * Store constructor.
     *
     * @param Logger $logger
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Logger $logger,
        StoreManagerInterface $storeManager
    ) {
        $this->logger = $logger;
        $this->storeManager = $storeManager;
    }

    /**
     * @return int
     * @throws NoSuchEntityException
     */
    public function getStoreId(): int
    {
        return (int) $this->storeManager->getStore()->getId();
    }

    /**
     * @return string|null
     */
    public function getBaseUrl(): ?string
    {
        try {
            return $this->storeManager->getStore()->getBaseUrl();
        } catch (NoSuchEntityException $e) {
            $this->logger->critical($e);
            return null;
        }
    }
}
