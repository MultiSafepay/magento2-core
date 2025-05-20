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

namespace MultiSafepay\ConnectCore\Plugin\Sales\Model\Order;

use Exception;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\CustomerManagement;
use MultiSafepay\Api\Transactions\Transaction;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Service\Process\SetOrderProcessingStatus;
use MultiSafepay\ConnectCore\Util\PaymentMethodUtil;
use MultiSafepay\ConnectCore\Util\TransactionUtil;

class CustomerManagementPlugin
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var PaymentMethodUtil
     */
    private $paymentMethodUtil;

    /**
     * @var TransactionUtil
     */
    private $transactionUtil;

    /**
     * @var SetOrderProcessingStatus
     */
    private $setOrderProcessingStatus;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param PaymentMethodUtil $paymentMethodUtil
     * @param TransactionUtil $transactionUtil
     * @param SetOrderProcessingStatus $setOrderProcessingStatus
     * @param Logger $logger
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        PaymentMethodUtil $paymentMethodUtil,
        TransactionUtil $transactionUtil,
        SetOrderProcessingStatus $setOrderProcessingStatus,
        Logger $logger
    ) {
        $this->orderRepository = $orderRepository;
        $this->paymentMethodUtil = $paymentMethodUtil;
        $this->transactionUtil = $transactionUtil;
        $this->setOrderProcessingStatus = $setOrderProcessingStatus;
        $this->logger = $logger;
    }

    /**
     * Whenever a customer has been created after creating the order, sometimes this can interfere with our
     * Notification process and cause the order to be set back to pending payment.
     * We need to check if this is the case and set the order to the processing state and status
     *
     * @param CustomerManagement $subject
     * @param $result
     * @param $orderId
     * @return void
     * @throws LocalizedException
     * @throws Exception
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterCreate(CustomerManagement $subject, $result, $orderId)
    {
        try {
            /** @var Order $order */
            $order = $this->orderRepository->get((int)$orderId);
        } catch (NoSuchEntityException | InputException $exception) {
            $this->logger->logInfoForOrder(
                'unknown',
                'Tried to search order with id: ' . $orderId . ' but it could not be found, skipping action'
            );

            return $result;
        }

        if (!$this->paymentMethodUtil->isMultisafepayOrder($order)) {
            return $result;
        }

        $this->logger->logInfoForOrder(
            $order->getIncrementId(),
            'New customer has been created, checking if order needs to be set to the processing state.'
        );

        if (in_array($order->getState(), [Order::STATE_PROCESSING, Order::STATE_COMPLETE], true)) {
            $this->logger->logInfoForOrder(
                $order->getIncrementId(),
                'Order already in processing or complete state, state not changed'
            );

            return $result;
        }

        $transaction = $this->transactionUtil->getTransaction($order);

        if (!$transaction) {
            return $result;
        }

        // We want to make sure that the order is only set to processing if the transaction is completed
        if ($transaction->getStatus() !== Transaction::COMPLETED) {
            return $result;
        }

        $order->setState(Order::STATE_PROCESSING);

        $this->logger->logInfoForNotification(
            $order->getIncrementId(),
            'Order state has been changed to: ' . Order::STATE_PROCESSING,
            $transaction->getData()
        );

        $this->setOrderProcessingStatus->execute($order, $transaction->getData());
        $this->orderRepository->save($order);

        $this->logger->logInfoForOrder(
            $order->getIncrementId(),
            'Order has been saved by the CustomerManagementPlugin.'
        );

        return $result;
    }
}
