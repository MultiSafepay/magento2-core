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

namespace MultiSafepay\ConnectCore\Service\Invoice;

use Exception;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
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
     * @param OrderInterface $order
     * @param ShipmentInterface $shipment
     * @param OrderPaymentInterface $payment
     * @return bool
     * @throws Exception
     */
    public function execute(
        OrderInterface $order,
        ShipmentInterface $shipment,
        OrderPaymentInterface $payment
    ): bool {
        if (($invoiceData = $this->getInvoiceItemsQtyDataFromShipment($shipment)) && $order->canInvoice()) {
            $payment->setShipment($shipment);

            if (!$payment->canCapture() || !$payment->canCapturePartial()) {
                throw new LocalizedException(__("Invoice can't be captured"));
            }

            $invoice = $this->createInvoiceByInvoiceData->execute($order, $payment, $invoiceData);
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
     * @param ShipmentInterface $shipment
     * @return array
     */
    private function getInvoiceItemsQtyDataFromShipment(ShipmentInterface $shipment): array
    {
        $invoiceData = [];

        foreach ($shipment->getItems() as $item) {
            $orderItemId = (int)$item->getOrderItemId();
            $shippedQty = (float)$item->getQty();
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
