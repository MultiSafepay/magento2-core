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

namespace MultiSafepay\ConnectCore\Gateway\Request\Builder;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Config\Config;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Exception\CouldNotRefundException;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\GenericGatewayConfigProvider;

class GenericGatewayRefundTransactionBuilder implements BuilderInterface
{
    /**
     * @var RefundTransactionBuilder
     */
    private $refundTransactionBuilder;

    /**
     * @var ShoppingCartRefundRequestBuilder
     */
    private $shoppingCartRefundRequestBuilder;

    /**
     * @var Config
     */
    private $config;

    /**
     * GenericGatewayRefundTransactionBuilder constructor.
     *
     * @param Config $config
     * @param RefundTransactionBuilder $refundTransactionBuilder
     * @param ShoppingCartRefundRequestBuilder $shoppingCartRefundRequestBuilder
     */
    public function __construct(
        Config $config,
        RefundTransactionBuilder $refundTransactionBuilder,
        ShoppingCartRefundRequestBuilder $shoppingCartRefundRequestBuilder
    ) {
        $this->refundTransactionBuilder = $refundTransactionBuilder;
        $this->shoppingCartRefundRequestBuilder = $shoppingCartRefundRequestBuilder;
        $this->config = $config;
    }

    /**
     * Build the refund transaction data
     *
     * @param array $buildSubject
     * @return array
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws CouldNotRefundException
     */
    public function build(array $buildSubject): array
    {
        $paymentDataObject = SubjectReader::readPayment($buildSubject);
        $methodInstance = $paymentDataObject->getPayment()->getMethodInstance();
        $methodCode = $methodInstance->getCode();
        $storeId = (int)$methodInstance->getStore();

        $this->config->setMethodCode($methodCode);

        $refundData = $this->buildRefundData($buildSubject, $storeId);
        $refundData['method_code'] = $methodCode;

        return $refundData;
    }

    /**
     * Build either the shopping cart refund or normal refund data depending on the selected setting
     *
     * @param array $buildSubject
     * @param int $storeId
     * @return array
     * @throws CouldNotRefundException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function buildRefundData(array $buildSubject, int $storeId): array
    {
        if ($this->config->getValue(GenericGatewayConfigProvider::REQUIRE_SHOPPING_CART, $storeId)) {
            return $this->shoppingCartRefundRequestBuilder->build($buildSubject);
        }

        return $this->refundTransactionBuilder->build($buildSubject);
    }
}
