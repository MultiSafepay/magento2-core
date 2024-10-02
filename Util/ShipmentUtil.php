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

namespace MultiSafepay\ConnectCore\Util;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Model\Order;
use Zend_Db_Select_Exception;

class ShipmentUtil
{
    /**
     * @param OrderInterface $order
     * @param ShipmentInterface $shipment
     * @return array
     */
    public function getShipmentApiRequestData(OrderInterface $order, ShipmentInterface $shipment): array
    {
        return [
            "tracktrace_code" => $this->getTrackingNumber($shipment),
            "carrier" => $order->getShippingDescription() ?? '',
            "ship_date" => $shipment->getCreatedAt() ?? '',
            "reason" => 'Shipped',
            "status" => 'shipped'
        ];
    }

    /**
     * @param ShipmentInterface $shipment
     * @return string
     */
    public function getTrackingNumber(ShipmentInterface $shipment): string
    {
        if (!($tracks = $shipment->getTracks())) {
            return '';
        }

        return is_array($tracks) ? (string)reset($tracks)->getTrackNumber() : '';
    }

    /**
     * @param Order $order
     * @return bool
     * @throws Zend_Db_Select_Exception
     */
    public function isOrderShippedPartially(Order $order): bool
    {
        return !($this->isOrderShipped($order)
                 && $order->getShipmentsCollection()
                 && (int)$order->getShipmentsCollection()->getSize() === 1);
    }

    /**
     * @param Order $order
     * @return bool
     */
    public function isOrderShipped(Order $order): bool
    {
        foreach ($order->getAllItems() as $item) {
            if ($item->getQtyToShip() > 0 && !$item->getIsVirtual() && !$item->getLockedDoShip()) {
                return false;
            }
        }

        return true;
    }
}
