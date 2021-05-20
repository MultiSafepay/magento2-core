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

namespace MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\GatewayInfoBuilder;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfo\Meta;
use MultiSafepay\ValueObject\Customer\EmailAddress;
use MultiSafepay\ValueObject\Customer\PhoneNumber;
use MultiSafepay\ValueObject\Date;
use MultiSafepay\ValueObject\Gender;

class GenderAndDateOfBirthGatewayInfoBuilder implements GatewayInfoBuilderInterface
{
    /**
     * @var Meta
     */
    private $meta;

    /**
     * GatewayInfo constructor.
     *
     * @param Meta $meta
     */
    public function __construct(
        Meta $meta
    ) {
        $this->meta = $meta;
    }

    /**
     * @param OrderInterface $order
     * @param OrderPaymentInterface $payment
     * @return Meta
     * @throws LocalizedException
     */
    public function build(OrderInterface $order, OrderPaymentInterface $payment): Meta
    {
        $billingAddress = $order->getBillingAddress();

        if ($billingAddress === null) {
            throw new LocalizedException(
                __('The transaction could not be created because the billing address is missing')
            );
        }

        $additionalInformation = $payment->getAdditionalInformation();

        if (empty($additionalInformation['phone_number'])) {
            throw new LocalizedException(__('This payment gateway requires a valid telephone number'));
        }

        return $this->meta->addGender(new Gender($additionalInformation['gender']))
            ->addBirthday(new Date($additionalInformation['date_of_birth']))
            ->addEmailAddress(new EmailAddress($order->getCustomerEmail()))
            ->addPhone(new PhoneNumber($additionalInformation['phone_number']));
    }
}
