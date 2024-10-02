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

namespace MultiSafepay\ConnectCore\Service\Shipment;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Exception\CouldNotInvoiceException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Service\EmailSender;
use MultiSafepay\ConnectCore\Service\Invoice\CreateInvoiceAfterShipment;
use MultiSafepay\ConnectCore\Util\InvoiceUtil;
use MultiSafepay\ConnectCore\Util\ShipmentUtil;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProcessManualCaptureShipment
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ShipmentUtil
     */
    private $shipmentUtil;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var CreateInvoiceAfterShipment
     */
    private $createInvoiceAfterShipment;

    /**
     * @var EmailSender
     */
    private $emailSender;

    /**
     * @var InvoiceUtil
     */
    private $invoiceUtil;

    /**
     * @var AddShippingToTransaction
     */
    private $addShippingToTransaction;

    /**
     * ProcessManualCaptureShipment constructor.
     *
     * @param Logger $logger
     * @param ShipmentUtil $shipmentUtil
     * @param ManagerInterface $messageManager
     * @param OrderRepositoryInterface $orderRepository
     * @param CreateInvoiceAfterShipment $createInvoiceAfterShipment
     * @param EmailSender $emailSender
     * @param InvoiceUtil $invoiceUtil
     * @param AddShippingToTransaction $addShippingToTransaction
     */
    public function __construct(
        Logger $logger,
        ShipmentUtil $shipmentUtil,
        ManagerInterface $messageManager,
        OrderRepositoryInterface $orderRepository,
        CreateInvoiceAfterShipment $createInvoiceAfterShipment,
        EmailSender $emailSender,
        InvoiceUtil $invoiceUtil,
        AddShippingToTransaction $addShippingToTransaction
    ) {
        $this->logger = $logger;
        $this->shipmentUtil = $shipmentUtil;
        $this->messageManager = $messageManager;
        $this->orderRepository = $orderRepository;
        $this->createInvoiceAfterShipment = $createInvoiceAfterShipment;
        $this->emailSender = $emailSender;
        $this->invoiceUtil = $invoiceUtil;
        $this->addShippingToTransaction = $addShippingToTransaction;
    }

    /**
     * @param ShipmentInterface $shipment
     * @param Order $order
     * @param Payment $payment
     * @throws CouldNotInvoiceException
     * @throws Exception
     */
    public function execute(ShipmentInterface $shipment, Order $order, Payment $payment): void
    {
        $orderIncrementId = $order->getIncrementId();

        try {
            if ($this->createInvoiceAfterShipment->execute($order, $shipment, $payment)) {
                $this->orderRepository->save($order);

                if ($lastCreatedInvoice = $this->invoiceUtil->getLastCreatedInvoiceByOrderId($order->getId())) {
                    $this->sendInvoiceEmail($payment, $lastCreatedInvoice);
                    $successMessage = __(
                        'The manual capture invoice %1 was created at MultiSafepay',
                        $orderIncrementId . '_' . $lastCreatedInvoice->getIncrementId()
                    );
                    $this->logger->logInfoForOrder($orderIncrementId, $successMessage->render());
                    $this->messageManager->addSuccessMessage($successMessage);
                }
            }
        } catch (Exception $exception) {
            $this->logger->logExceptionForOrder($orderIncrementId, $exception);
            $this->messageManager->addErrorMessage(
                __('The manual capture invoice could not be created at MultiSafepay, please check the logs.')
            );

            throw new CouldNotInvoiceException(__($exception->getMessage()));
        }

        $this->addShippingToTransaction->execute($shipment, $order);
    }

    /**
     * @param OrderPaymentInterface $payment
     * @param Invoice $invoice
     * @throws LocalizedException
     * @throws Exception
     */
    public function sendInvoiceEmail(OrderPaymentInterface $payment, Invoice $invoice): void
    {
        $orderIncrementId = $invoice->getOrder()->getIncrementId();

        try {
            if ($this->emailSender->sendInvoiceEmail($payment, $invoice)) {
                $this->logger->logInfoForOrder(
                    $orderIncrementId,
                    __('Email for invoice %1 was sent.', $invoice->getIncrementId())->render()
                );
            }
        } catch (MailException $mailException) {
            $this->logger->logExceptionForOrder($orderIncrementId, $mailException, Logger::INFO);
        }
    }
}
