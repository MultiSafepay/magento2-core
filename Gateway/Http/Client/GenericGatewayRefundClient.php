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

namespace MultiSafepay\ConnectCore\Gateway\Http\Client;

use Exception;
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
     * @param Config $config
     * @param RefundClient $refundClient
     * @param ShoppingCartRefundClient $shoppingCartRefundClient
     */
    public function __construct(
        Config $config,
        RefundClient $refundClient,
        ShoppingCartRefundClient $shoppingCartRefundClient
    ) {
        $this->config = $config;
        $this->refundClient = $refundClient;
        $this->shoppingCartRefundClient = $shoppingCartRefundClient;
    }

    /**
     * Place the refund request
     *
     * @param TransferInterface $transferObject
     * @return array|null
     * @throws Exception
     */
    public function placeRequest(TransferInterface $transferObject): ?array
    {
        $request = $transferObject->getBody();
        $methodCode = $request['method_code'];

        $this->config->setMethodCode($methodCode);

        if ($this->config->getValue(GenericGatewayConfigProvider::REQUIRE_SHOPPING_CART)) {
            return $this->shoppingCartRefundClient->placeRequest($transferObject);
        }

        return $this->refundClient->placeRequest($transferObject);
    }
}
