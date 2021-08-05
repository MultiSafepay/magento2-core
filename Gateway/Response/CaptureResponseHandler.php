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

namespace MultiSafepay\ConnectCore\Gateway\Response;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;

class CaptureResponseHandler implements HandlerInterface
{
    public const MULTISAFEPAY_CAPTURE_DATA_FIELD_NAME = "multisafepay_capture_data";

    /**
     * @param array $handlingSubject
     * @param array $response
     * @return $this
     * @throws LocalizedException
     */
    public function handle(array $handlingSubject, array $response): CaptureResponseHandler
    {
        $paymentDataObject = SubjectReader::readPayment($handlingSubject);
        $amount = (float)SubjectReader::readAmount($handlingSubject);
        /** @var OrderPaymentInterface $payment */
        $payment = $paymentDataObject->getPayment();

        if (!isset($response['transaction_id'], $response['order_id'])) {
            throw new LocalizedException(__('Response API data is not valid.'));
        }

        $payment->setTransactionId($response['transaction_id']);
        $payment->setAdditionalInformation(
            self::MULTISAFEPAY_CAPTURE_DATA_FIELD_NAME,
            array_merge(
                (array)$payment->getAdditionalInformation(self::MULTISAFEPAY_CAPTURE_DATA_FIELD_NAME),
                [$this->prepareCaptureDataFromResponse($response, $amount)]
            )
        );

        return $this;
    }

    /**
     * @param array $response
     * @param float $amount
     * @return array[]
     */
    private function prepareCaptureDataFromResponse(array $response, float $amount): array
    {
        return [
            'transaction_id' => $response['transaction_id'],
            'order_id' => $response['order_id'],
            'amount' => $amount,
        ];
    }
}
