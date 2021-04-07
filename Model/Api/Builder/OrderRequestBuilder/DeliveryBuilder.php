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

namespace MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order\Address;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\CustomerDetails;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\CustomerBuilder\AddressBuilder;
use MultiSafepay\ValueObject\Customer\EmailAddress;
use MultiSafepay\ValueObject\Customer\PhoneNumber;

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
     * @param OrderInterface $order
     * @param OrderPaymentInterface $payment
     * @param OrderRequest $orderRequest
     * @return void
     */
    public function build(OrderInterface $order, OrderPaymentInterface $payment, OrderRequest $orderRequest): void
    {
        /** @var Address $shippingAddress */
        $shippingAddress = $order->getShippingAddress();
        if ($shippingAddress !== null && $order->canShip()) {
            $address = $this->address->build($shippingAddress);

            $deliveryDetails = new CustomerDetails();
            $deliveryDetails->addFirstName($shippingAddress->getFirstname())
                    ->addLastName($shippingAddress->getLastname())
                    ->addAddress($address)
                    ->addPhoneNumber(new PhoneNumber($shippingAddress->getTelephone() ?? ''))
                    ->addEmailAddress(new EmailAddress($shippingAddress->getEmail()));

            $orderRequest->addDelivery($deliveryDetails);
        }
    }
}
