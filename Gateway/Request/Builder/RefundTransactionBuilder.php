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

namespace MultiSafepay\ConnectCore\Gateway\Request\Builder;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Exception\CouldNotRefundException;
use Magento\Store\Model\StoreManagerInterface;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\Description;
use MultiSafepay\Api\Transactions\RefundRequest;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Util\CurrencyUtil;
use MultiSafepay\ValueObject\Money;

class RefundTransactionBuilder implements BuilderInterface
{
    /**
     * @var RefundRequest
     */
    private $refundRequest;

    /**
     * @var Description
     */
    private $description;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var CurrencyUtil
     */
    private $currencyUtil;

    /**
     * RefundTransactionBuilder constructor.
     *
     * @param RefundRequest $refundRequest
     * @param Config $config
     * @param CurrencyUtil $currencyUtil
     * @param Description $description
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        RefundRequest $refundRequest,
        Config $config,
        CurrencyUtil $currencyUtil,
        Description $description,
        StoreManagerInterface $storeManager
    ) {
        $this->refundRequest = $refundRequest;
        $this->description = $description;
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->currencyUtil = $currencyUtil;
    }

    /**
     * @inheritDoc
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function build(array $buildSubject): array
    {
        $paymentDataObject = SubjectReader::readPayment($buildSubject);
        $amount = (float)SubjectReader::readAmount($buildSubject);

        $msg = 'Refunds with 0 amount can not be processed. Please set a different amount';
        if ($amount <= 0) {
            throw new CouldNotRefundException(__($msg));
        }

        $payment = $paymentDataObject->getPayment();

        /** @var OrderInterface $order */
        $order = $payment->getOrder();
        $orderId = $order->getIncrementId();

        $description = $this->description->addDescription($this->config->getRefundDescription($orderId));
        $money = new Money($this->getAmount($amount, $order) * 100, $this->currencyUtil->getCurrencyCode($order));

        $refund = $this->refundRequest->addMoney($money)
            ->addDescription($description);

        return [
            'payload' => $refund,
            'order_id' => $orderId,
            'store_id' => (int)$order->getStoreId()
        ];
    }

    /**
     * @param float $amount
     * @param OrderInterface $order
     * @return float
     */
    public function getAmount(float $amount, OrderInterface $order): float
    {
        if ($this->config->useBaseCurrency($order->getStoreId())) {
            return $amount;
        }

        return round($amount * $order->getBaseToOrderRate(), 2);
    }
}
