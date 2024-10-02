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
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\BankTransferConfigProvider;
use MultiSafepay\ConnectCore\Service\EmailSender;
use MultiSafepay\ConnectCore\Util\OrderStatusUtil;

class RedirectTransactionBuilder implements BuilderInterface
{
    private const ORDER_STATE = 'state';
    private const ORDER_STATUS = 'status';

    /**
     * @var OrderStatusUtil
     */
    private $orderStatusUtil;

    /**
     * @var State
     */
    private $state;

    /**
     * @var EmailSender
     */
    private $emailSender;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * RedirectTransactionBuilder constructor.
     *
     * @param EmailSender $emailSender
     * @param OrderStatusUtil $orderStatusUtil
     * @param State $state
     * @param Logger $logger
     */
    public function __construct(
        EmailSender $emailSender,
        OrderStatusUtil $orderStatusUtil,
        State $state,
        Logger $logger
    ) {
        $this->state = $state;
        $this->emailSender = $emailSender;
        $this->orderStatusUtil = $orderStatusUtil;
        $this->logger = $logger;
    }

    /**
     * @param array $buildSubject
     * @return array
     * @throws LocalizedException
     * @throws Exception
     */
    public function build(array $buildSubject): array
    {
        $stateObject = $buildSubject['stateObject'];
        $paymentDataObject = SubjectReader::readPayment($buildSubject);

        /** @var Payment $payment */
        $payment = $paymentDataObject->getPayment();
        $order = $payment->getOrder();

        try {
            $areaCode = $this->state->getAreaCode();
            $paymentMethod = $payment->getMethod() !== '' ? $payment->getMethod() : $payment->getMethodInstance()
                ->getCode();
            $orderStateAndStatus = $this->getOrderStateAndStatus($order, $paymentMethod, $areaCode);
            $stateObject->setState($orderStateAndStatus[self::ORDER_STATE]);
            $stateObject->setStatus($orderStateAndStatus[self::ORDER_STATUS]);

            // Early return on backend order
            if ($areaCode === Area::AREA_ADMINHTML) {
                return [];
            }

            // If not backend order, check when order confirmation e-mail needs to be sent
            if (!$this->emailSender->checkOrderConfirmationBeforeTransaction($paymentMethod)) {
                $stateObject->setIsNotified(false);
                $order->setCanSendNewEmailFlag(false);
            }
        } catch (LocalizedException $localizedException) {
            $this->logger->logExceptionForOrder($order->getIncrementId(), $localizedException);

            throw new LocalizedException(__('Something went wrong. Please, check the MultiSafepay logs.'));
        }

        return [];
    }

    /**
     * @param OrderInterface $order
     * @param string $paymentMethod
     * @param string $areaCode
     * @return array
     */
    private function getOrderStateAndStatus(OrderInterface $order, string $paymentMethod, string $areaCode): array
    {
        if ($paymentMethod === BankTransferConfigProvider::CODE || $areaCode === Area::AREA_ADMINHTML) {
            return [
                self::ORDER_STATE => Order::STATE_NEW,
                self::ORDER_STATUS => $this->orderStatusUtil->getPendingStatus($order)
            ];
        }

        return [
            self::ORDER_STATE => Order::STATE_PENDING_PAYMENT,
            self::ORDER_STATUS => $this->orderStatusUtil->getPendingPaymentStatus($order)
        ];
    }
}
