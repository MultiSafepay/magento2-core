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
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order\Payment;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Service\EmailSender;
use Magento\Framework\Exception\LocalizedException;

class RecurringTransactionBuilder implements BuilderInterface
{
    /**
     * @var EmailSender
     */
    private $emailSender;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * RecurringTransactionBuilder constructor.
     *
     * @param EmailSender $emailSender
     * @param Logger $logger
     */
    public function __construct(
        EmailSender $emailSender,
        Logger $logger
    ) {
        $this->emailSender = $emailSender;
        $this->logger = $logger;
    }

    /**
     * @param array $buildSubject
     * @return array
     * @throws Exception
     */
    public function build(array $buildSubject): array
    {
        $paymentDataObject = SubjectReader::readPayment($buildSubject);

        /** @var Payment $payment */
        $payment = $paymentDataObject->getPayment();
        $order = $payment->getOrder();

        try {
            if (!$this->emailSender->checkOrderConfirmationBeforeTransaction(
                $payment->getMethod() !== '' ? $payment->getMethod() : $payment->getMethodInstance()->getCode()
            )) {
                $order->setCanSendNewEmailFlag(false);
            }
        } catch (LocalizedException $localizedException) {
            $this->logger->logExceptionForOrder($order->getIncrementId(), $localizedException);
        }

        return [
            'order' => $order,
        ];
    }
}
