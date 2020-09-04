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

namespace MultiSafepay\ConnectCore\Gateway\Request\Builder;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\StoreManagerInterface;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\Description;
use MultiSafepay\Api\Transactions\RefundRequest;
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
     * RefundTransactionBuilder constructor.
     *
     * @param RefundRequest $refundRequest
     * @param Description $description
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        RefundRequest $refundRequest,
        Description $description,
        StoreManagerInterface $storeManager
    ) {
        $this->refundRequest = $refundRequest;
        $this->description = $description;
        $this->storeManager = $storeManager;
    }

    /**
     * @inheritDoc
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function build(array $buildSubject): array
    {
        $paymentDataObject = SubjectReader::readPayment($buildSubject);
        $amount = SubjectReader::readAmount($buildSubject) * 100;

        $payment = $paymentDataObject->getPayment();

        /** @var OrderInterface $order */
        $order = $payment->getOrder();
        $orderId = $order->getIncrementId();

        $description = $this->description->addDescription('Refund for order #' . $orderId);
        $money = new Money($amount, $this->getCurrencyFromOrder($order));

        $refund = $this->refundRequest->addMoney($money)
            ->addDescription($description);

        return [
            'payload' => $refund,
            'order_id' => $orderId
        ];
    }

    /**
     * @param OrderInterface $order
     * @return string
     * @throws NoSuchEntityException
     */
    private function getCurrencyFromOrder(OrderInterface $order): string
    {
        $currencyCode = (string)$order->getOrderCurrencyCode();
        if (!empty($currencyCode)) {
            return $currencyCode;
        }

        $currencyCode = (string)$order->getGlobalCurrencyCode();
        if (!empty($currencyCode)) {
            return $currencyCode;
        }

        return (string)$this->storeManager->getStore($order->getStoreId())->getCurrentCurrency()->getCode();
    }
}
