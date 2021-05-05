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
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Exception\CouldNotRefundException;
use Magento\Store\Model\Store;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\Description;
use MultiSafepay\Api\Transactions\RefundRequest;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Gateway\Response\CaptureResponseHandler;
use MultiSafepay\ConnectCore\Util\AmountUtil;
use MultiSafepay\ConnectCore\Util\CaptureUtil;
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
     * @var Config
     */
    private $config;

    /**
     * @var CurrencyUtil
     */
    private $currencyUtil;

    /**
     * @var AmountUtil
     */
    private $amountUtil;

    /**
     * @var CaptureUtil
     */
    private $captureUtil;

    /**
     * RefundTransactionBuilder constructor.
     *
     * @param AmountUtil $amountUtil
     * @param RefundRequest $refundRequest
     * @param Config $config
     * @param CurrencyUtil $currencyUtil
     * @param Description $description
     * @param CaptureUtil $captureUtil
     */
    public function __construct(
        AmountUtil $amountUtil,
        RefundRequest $refundRequest,
        Config $config,
        CurrencyUtil $currencyUtil,
        Description $description,
        CaptureUtil $captureUtil
    ) {
        $this->refundRequest = $refundRequest;
        $this->description = $description;
        $this->config = $config;
        $this->currencyUtil = $currencyUtil;
        $this->amountUtil = $amountUtil;
        $this->captureUtil = $captureUtil;
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

        if ($amount <= 0) {
            throw new CouldNotRefundException(
                __('Refunds with 0 amount can not be processed. Please set a different amount')
            );
        }

        $payment = $paymentDataObject->getPayment();

        /** @var OrderInterface $order */
        $order = $payment->getOrder();
        $orderId = $order->getIncrementId();

        if ($this->captureUtil->isCaptureManualPayment($payment)) {
            if (!$captureData = $this->getCaptureDataByTransactionId($payment->getParentTransactionId(), $payment)) {
                throw new CouldNotRefundException(__('Can\'t find manual capture data'));
            }

            if ($amount > $captureData['amount']) {
                throw new CouldNotRefundException(
                    __('Refund amount is not valid. Please set a different amount')
                );
            }

            $orderId = $captureData['order_id'];
        }

        $description = $this->description->addDescription($this->config->getRefundDescription($orderId));
        $money = new Money(
            $this->amountUtil->getAmount($amount, $order) * 100,
            $this->currencyUtil->getCurrencyCode($order)
        );

        $refund = $this->refundRequest->addMoney($money)
            ->addDescription($description);

        return [
            'payload' => $refund,
            'order_id' => $orderId,
            Store::STORE_ID => (int)$order->getStoreId(),
        ];
    }

    /**
     * @param string $transactionId
     * @param InfoInterface $payment
     * @return array|null
     */
    private function getCaptureDataByTransactionId(string $transactionId, InfoInterface $payment): ?array
    {
        $captureData = $payment->getAdditionalInformation(CaptureResponseHandler::MULTISAFEPAY_CAPTURE_DATA_FIELD_NAME);

        foreach ($captureData as $captureDataItem) {
            if (isset($captureDataItem['transaction_id'])
                && $transactionId === (string)$captureDataItem['transaction_id']
            ) {
                return $captureDataItem;
            }
        }

        return null;
    }
}
