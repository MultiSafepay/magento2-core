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

namespace MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\GatewayInfoBuilder\GatewayInfoBuilderInterface;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\MyBankConfigProvider;

class GatewayInfoBuilder implements OrderRequestBuilderInterface
{
    public const GATEWAY_WITH_ISSUER_LIST = [MyBankConfigProvider::CODE];

    /**
     * @var GatewayInfoBuilderInterface[]
     */
    private $gatewayBuilders;

    /**
     * GatewayInfoBuilder constructor.
     *
     * @param GatewayInfoBuilderInterface[] $gatewayBuilders
     */
    public function __construct(
        array $gatewayBuilders
    ) {
        $this->gatewayBuilders = $gatewayBuilders;
    }

    /**
     * @param Order $order
     * @param Payment $payment
     * @param OrderRequest $orderRequest
     * @throws LocalizedException
     */
    public function build(Order $order, Payment $payment, OrderRequest $orderRequest): void
    {
        $paymentCode = $payment->getMethod();

        if (!isset($this->gatewayBuilders[$paymentCode])) {
            return;
        }

        if (in_array($paymentCode, self::GATEWAY_WITH_ISSUER_LIST, true)
            && !isset($payment->getAdditionalInformation()['issuer_id'])
        ) {
            return;
        }

        $transactionType = $payment->getMethodInstance()->getConfigData('transaction_type') ?:
            $payment->getAdditionalInformation()['transaction_type'] ??
            TransactionTypeBuilder::TRANSACTION_TYPE_REDIRECT_VALUE;

        // If transaction type is not set to 'direct' then do not add gateway info
        if ($transactionType !== TransactionTypeBuilder::TRANSACTION_TYPE_DIRECT_VALUE) {
            return;
        }

        $orderRequest->addGatewayInfo(
            $this->gatewayBuilders[$paymentCode]->build($order, $payment)
        );
    }
}
