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

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Exception\CouldNotRefundException;
use Magento\Sales\Model\Order\Payment;
use Magento\Store\Model\Store;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\Description;
use MultiSafepay\Api\Transactions\RefundRequest;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Util\AmountUtil;
use MultiSafepay\ConnectCore\Util\CaptureUtil;
use MultiSafepay\ConnectCore\Util\CurrencyUtil;
use MultiSafepay\ValueObject\Money;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
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
     * @var Logger
     */
    private $logger;

    /**
     * RefundTransactionBuilder constructor.
     *
     * @param AmountUtil $amountUtil
     * @param RefundRequest $refundRequest
     * @param Config $config
     * @param CurrencyUtil $currencyUtil
     * @param Description $description
     * @param CaptureUtil $captureUtil
     * @param Logger $logger
     */
    public function __construct(
        AmountUtil $amountUtil,
        RefundRequest $refundRequest,
        Config $config,
        CurrencyUtil $currencyUtil,
        Description $description,
        CaptureUtil $captureUtil,
        Logger $logger
    ) {
        $this->refundRequest = $refundRequest;
        $this->description = $description;
        $this->config = $config;
        $this->currencyUtil = $currencyUtil;
        $this->amountUtil = $amountUtil;
        $this->captureUtil = $captureUtil;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws CouldNotRefundException
     * @throws Exception
     */
    public function build(array $buildSubject): array
    {
        $paymentDataObject = SubjectReader::readPayment($buildSubject);
        $amount = (float)SubjectReader::readAmount($buildSubject);

        /** @var Payment $payment */
        $payment = $paymentDataObject->getPayment();

        $order = $payment->getOrder();
        $orderId = $order->getIncrementId();

        if ($amount <= 0) {
            $message = __('Refunds with 0 amount can not be processed. Please set a different amount');
            $this->logger->logInfoForOrder($orderId, $message->render());

            throw new CouldNotRefundException($message);
        }

        $captureData = $payment->getParentTransactionId()
            ? $this->captureUtil->getCaptureDataByTransactionId($payment->getParentTransactionId(), $payment) : null;

        if (($this->captureUtil->isManualCaptureEnabled($payment) && $captureData) || $captureData) {
            if ($amount > $captureData['amount']) {
                $exceptionMessage =
                    __('Refund amount for manual captured invoice is not valid. Please set a different amount');
                $this->logger->logInfoForOrder($orderId, $exceptionMessage->render());

                throw new CouldNotRefundException($exceptionMessage);
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
}
