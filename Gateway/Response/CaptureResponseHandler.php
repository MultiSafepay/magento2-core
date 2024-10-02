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

namespace MultiSafepay\ConnectCore\Gateway\Response;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Payment;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Util\CaptureUtil;

class CaptureResponseHandler implements HandlerInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * CancelResponseHandler constructor.
     *
     * @param Logger $logger
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param array $handlingSubject
     * @param array $response
     * @return $this
     * @throws LocalizedException
     * @throws Exception
     */
    public function handle(array $handlingSubject, array $response): CaptureResponseHandler
    {
        $paymentDataObject = SubjectReader::readPayment($handlingSubject);
        $amount = (float)SubjectReader::readAmount($handlingSubject);

        /** @var Payment $payment */
        $payment = $paymentDataObject->getPayment();

        if (!$response || !isset($response['transaction_id'], $response['order_id'])) {
            $exceptionMessage = __('Capture response API data is not valid.');
            $this->logger->logInfoForOrder((string)$response['order_id'] ?? 'unknown', $exceptionMessage->render());

            throw new LocalizedException($exceptionMessage);
        }

        $payment->setTransactionId($response['transaction_id']);
        $payment->setAdditionalInformation(
            CaptureUtil::MULTISAFEPAY_CAPTURE_DATA_FIELD_NAME,
            array_merge(
                (array)$payment->getAdditionalInformation(CaptureUtil::MULTISAFEPAY_CAPTURE_DATA_FIELD_NAME),
                [$this->prepareCaptureDataFromResponse($response, $amount)]
            )
        );

        $this->logger->logInfoForOrder(
            (string)$response['order_id'] ?? 'unknown',
            'Amount ' . $amount . ' was captured. Transaction ID: ' . $response['transaction_id']
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
