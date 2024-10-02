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

namespace MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address;
use Magento\Sales\Model\Order\Payment;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\CustomerDetails;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\CustomerBuilder\AddressBuilder;
use MultiSafepay\Exception\InvalidArgumentException;

class DeliveryBuilder implements OrderRequestBuilderInterface
{
    /**
     * @var AddressBuilder
     */
    private $address;

    /**
     * Customer constructor.
     *
     * @param AddressBuilder $address
     */
    public function __construct(
        AddressBuilder $address
    ) {
        $this->address = $address;
    }

    /**
     * @param Order $order
     * @param Payment $payment
     * @param OrderRequest $orderRequest
     * @throws InvalidArgumentException
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function build(Order $order, Payment $payment, OrderRequest $orderRequest): void
    {
        /** @var Address $shippingAddress */
        $shippingAddress = $order->getShippingAddress();
        if ($shippingAddress !== null && $order->canShip()) {
            $address = $this->address->build($shippingAddress);

            $deliveryDetails = new CustomerDetails();
            $deliveryDetails->addFirstName($shippingAddress->getFirstname())
                    ->addLastName($shippingAddress->getLastname())
                    ->addAddress($address)
                    ->addPhoneNumberAsString($shippingAddress->getTelephone() ?? '')
                    ->addEmailAddressAsString($shippingAddress->getEmail());

            $orderRequest->addDelivery($deliveryDetails);
        }
    }
}
