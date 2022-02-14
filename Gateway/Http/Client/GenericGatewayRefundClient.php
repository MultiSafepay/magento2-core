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

namespace MultiSafepay\ConnectCore\Gateway\Http\Client;

use Magento\Payment\Gateway\Config\Config;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\GenericGatewayConfigProvider;

class GenericGatewayRefundClient implements ClientInterface
{
    /**
     * @var RefundClient
     */
    private $refundClient;

    /**
     * @var ShoppingCartRefundClient
     */
    private $shoppingCartRefundClient;

    /**
     * @var Config
     */
    private $config;

    /**
     * GenericGatewayRefundClient constructor.
     *
     * @param RefundClient $refundClient
     * @param ShoppingCartRefundClient $shoppingCartRefundClient
     */
    public function __construct(
        RefundClient $refundClient,
        ShoppingCartRefundClient $shoppingCartRefundClient
    ) {
        $this->refundClient = $refundClient;
        $this->shoppingCartRefundClient = $shoppingCartRefundClient;
    }

    /**
     * @param TransferInterface $transferObject
     * @return array|null
     */
    public function placeRequest(TransferInterface $transferObject): ?array
    {
        $this->config->setMethodCode(GenericGatewayConfigProvider::CODE);

        if ($this->config->getValue(GenericGatewayConfigProvider::REQUIRE_SHOPPING_CART)) {
            return $this->shoppingCartRefundClient->placeRequest($transferObject);
        }

        return $this->refundClient->placeRequest($transferObject);
    }
}
