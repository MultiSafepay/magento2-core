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

namespace MultiSafepay\ConnectCore\Service\Invoice;

use Exception;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Exception\CouldNotInvoiceException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Shipment;
use MultiSafepay\ConnectCore\Logger\Logger;

class CreateInvoiceAfterShipment
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var OrderItemRepositoryInterface
     */
    private $orderItemRepository;

    /**
     * @var CreateInvoiceByInvoiceData
     */
    private $createInvoiceByInvoiceData;

    /**
     * CreateInvoiceAfterShipment constructor.
     *
     * @param Logger $logger
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param CreateInvoiceByInvoiceData $createInvoiceByInvoiceData
     */
    public function __construct(
        Logger $logger,
        OrderItemRepositoryInterface $orderItemRepository,
        CreateInvoiceByInvoiceData $createInvoiceByInvoiceData
    ) {
        $this->logger = $logger;
        $this->orderItemRepository = $orderItemRepository;
        $this->createInvoiceByInvoiceData = $createInvoiceByInvoiceData;
    }

    /**
     * @param Order $order
     * @param Shipment $shipment
     * @param Payment $payment
     * @return bool
     * @throws CouldNotInvoiceException
     * @throws InputException
     * @throws LocalizedException
     * @throws Exception
     */
    public function execute(Order $order, Shipment $shipment, Payment $payment): bool
    {
        if (($invoiceData = $this->getInvoiceItemsQtyDataFromShipment($shipment)) && $order->canInvoice()) {
            $payment->setShipment($shipment);

            if (!$payment->canCapture() || !$payment->canCapturePartial()) {
                $message = __("Invoice can't be captured manually");
                $this->logger->logInfoForOrder($order->getIncrementId(), $message->render());

                throw new CouldNotInvoiceException($message);
            }

            if (!($invoice = $this->createInvoiceByInvoiceData->execute($order, $payment, $invoiceData))) {
                return false;
            }

            $invoiceId = $invoice->getIncrementId();
            $this->logger->logInfoForOrder(
                $order->getIncrementId(),
                __('Manual capture invoice %1 was created.', $invoiceId)->render()
            );

            return true;
        }

        return false;
    }

    /**
     * @param Shipment $shipment
     * @return array
     */
    private function getInvoiceItemsQtyDataFromShipment(Shipment $shipment): array
    {
        $invoiceData = [];

        foreach ($shipment->getItems() as $item) {
            $orderItemId = (int)$item->getOrderItemId();
            $shippedQty = (float)$item->getQty();

            /** @var Item $orderItem */
            $orderItem = $this->orderItemRepository->get($orderItemId);

            $orderQtyToInvoice = $orderItem->getQtyToInvoice();
            $canInvoiceQty = ($shippedQty <= $orderQtyToInvoice) ? $shippedQty : $orderQtyToInvoice;

            if ($canInvoiceQty) {
                $invoiceData[$orderItemId] = $item->getQty();
            }
        }

        return $invoiceData;
    }
}
