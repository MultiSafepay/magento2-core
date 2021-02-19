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

namespace MultiSafepay\ConnectCore\Model\Api\Builder;

use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\OrderRequestBuilderInterface;

class OrderRequestBuilderPool
{
    /**
     * @var OrderRequestBuilderInterface[]
     */
    private $orderRequestBuilders;

    /**
     * OrderRequestBuilderPool constructor.
     *
     * @param OrderRequestBuilderInterface[] $orderRequestBuilders
     */
    public function __construct(
        array $orderRequestBuilders = []
    ) {
        $this->orderRequestBuilders = $orderRequestBuilders;
    }

    /**
     * @return OrderRequestBuilderInterface[]
     */
    public function getOrderRequestBuilders(): array
    {
        return $this->orderRequestBuilders;
    }

    /**
     * @param string $builderCode
     * @return OrderRequestBuilderInterface|null
     */
    public function getOrderRequestBuilderByCode(string $builderCode): ?OrderRequestBuilderInterface
    {
        return $this->orderRequestBuilders[$builderCode] ?? null;
    }
}
