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

namespace MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\GatewayInfoBuilder;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfo\Wallet;
use MultiSafepay\ConnectCore\Util\JsonHandler;

class WalletPaymentTokenGatewayInfoBuilder implements GatewayInfoBuilderInterface
{
    /**
     * @var Wallet
     */
    private $walletGatewayInfoBuilder;

    /**
     * @var JsonHandler
     */
    private $jsonHandler;

    /**
     * WalletPaymentTokenGatewayInfoBuilder constructor.
     *
     * @param Wallet $walletGatewayInfoBuilder
     * @param JsonHandler $jsonHandler
     */
    public function __construct(
        Wallet $walletGatewayInfoBuilder,
        JsonHandler $jsonHandler
    ) {
        $this->walletGatewayInfoBuilder = $walletGatewayInfoBuilder;
        $this->jsonHandler = $jsonHandler;
    }

    /**
     * @param OrderInterface $order
     * @param OrderPaymentInterface $payment
     * @return Wallet|null
     */
    public function build(OrderInterface $order, OrderPaymentInterface $payment): ?Wallet
    {
        if (!isset($payment->getAdditionalInformation()['payment_token'])) {
            return $this->walletGatewayInfoBuilder->addPaymentToken('');
        }

        if (is_array($payment->getAdditionalInformation()['payment_token'])) {
            return $this->walletGatewayInfoBuilder->addPaymentToken(
                $this->jsonHandler->convertToJSON((array)$payment->getAdditionalInformation()['payment_token'])
            );
        }

        return $this->walletGatewayInfoBuilder->addPaymentToken($payment->getAdditionalInformation()['payment_token']);
    }
}
