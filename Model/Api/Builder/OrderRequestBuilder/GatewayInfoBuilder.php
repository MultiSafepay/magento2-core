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

namespace MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\GatewayInfoBuilder\
AfterpayGatewayInfoBuilder;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\GatewayInfoBuilder\
DirectBankTransferGatewayInfoBuilder;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\GatewayInfoBuilder\DirectDebitGatewayInfoBuilder;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\GatewayInfoBuilder\IdealGatewayInfoBuilder;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\GatewayInfoBuilder\
PayAfterEinvoicingGatewayInfoBuilder;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\GatewayInfoBuilder\In3GatewayInfoBuilder;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\AfterpayConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\DirectBankTransferConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\DirectDebitConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\EinvoicingConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\IdealConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\In3ConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\PayafterConfigProvider;

class GatewayInfoBuilder
{
    /**
     * @var AfterpayGatewayInfoBuilder
     */
    private $afterpayGatewayInfoBuilder;

    /**
     * @var DirectBankTransferGatewayInfoBuilder
     */
    private $directBankTransferGatewayInfoBuilder;

    /**
     * @var DirectDebitGatewayInfoBuilder
     */
    private $directDebitGatewayInfoBuilder;

    /**
     * @var IdealGatewayInfoBuilder
     */
    private $idealGatewayInfoBuilder;

    /**
     * @var PayAfterEinvoicingGatewayInfoBuilder
     */
    private $payAfterEinvoicingGatewayInfoBuilder;

    /**
     * @var In3GatewayInfoBuilder
     */
    private $in3GatewayInfoBuilder;

    /**
     * GatewayInfoBuilder constructor.
     *
     * @param AfterpayGatewayInfoBuilder $afterpayGatewayInfoBuilder
     * @param DirectBankTransferGatewayInfoBuilder $directBankTransferGatewayInfoBuilder
     * @param DirectDebitGatewayInfoBuilder $directDebitGatewayInfoBuilder
     * @param IdealGatewayInfoBuilder $idealGatewayInfoBuilder
     * @param In3GatewayInfoBuilder $in3GatewayInfoBuilder
     * @param PayAfterEinvoicingGatewayInfoBuilder $payAfterEinvoicingGatewayInfoBuilder
     */
    public function __construct(
        AfterpayGatewayInfoBuilder $afterpayGatewayInfoBuilder,
        DirectBankTransferGatewayInfoBuilder $directBankTransferGatewayInfoBuilder,
        DirectDebitGatewayInfoBuilder $directDebitGatewayInfoBuilder,
        IdealGatewayInfoBuilder $idealGatewayInfoBuilder,
        In3GatewayInfoBuilder $in3GatewayInfoBuilder,
        PayAfterEinvoicingGatewayInfoBuilder $payAfterEinvoicingGatewayInfoBuilder
    ) {
        $this->afterpayGatewayInfoBuilder = $afterpayGatewayInfoBuilder;
        $this->directBankTransferGatewayInfoBuilder = $directBankTransferGatewayInfoBuilder;
        $this->directDebitGatewayInfoBuilder = $directDebitGatewayInfoBuilder;
        $this->idealGatewayInfoBuilder = $idealGatewayInfoBuilder;
        $this->payAfterEinvoicingGatewayInfoBuilder = $payAfterEinvoicingGatewayInfoBuilder;
        $this->in3GatewayInfoBuilder = $in3GatewayInfoBuilder;
    }

    /**
     * @param OrderInterface $order
     * @param OrderPaymentInterface $payment
     * @param OrderRequest $orderRequest
     * @return void
     * @throws LocalizedException
     */
    public function build(OrderInterface $order, OrderPaymentInterface $payment, OrderRequest $orderRequest): void
    {
        switch ($payment->getMethod()) {
            case IdealConfigProvider::CODE:
                if (isset($payment->getAdditionalInformation()['issuer_id'])) {
                    $orderRequest->addGatewayInfo($this->idealGatewayInfoBuilder->build($payment));
                }
                break;
            case EinvoicingConfigProvider::CODE:
            case PayafterConfigProvider::CODE:
                $orderRequest->addGatewayInfo($this->payAfterEinvoicingGatewayInfoBuilder->build($order, $payment));
                break;
            case AfterpayConfigProvider::CODE:
                $orderRequest->addGatewayInfo($this->afterpayGatewayInfoBuilder->build($order, $payment));
                break;
            case DirectBankTransferConfigProvider::CODE:
                $orderRequest->addGatewayInfo($this->directBankTransferGatewayInfoBuilder->build($payment));
                break;
            case DirectDebitConfigProvider::CODE:
                $orderRequest->addGatewayInfo($this->directDebitGatewayInfoBuilder->build($payment));
                break;
            case In3ConfigProvider::CODE:
                $orderRequest->addGatewayInfo($this->in3GatewayInfoBuilder->build($order, $payment));
        }
    }
}
