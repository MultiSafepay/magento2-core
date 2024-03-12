<?php

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\CustomerBuilder;

use Magento\Customer\Model\Session;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\CustomerDetails;
use MultiSafepay\ConnectCore\Util\RecurringDataUtil;

class ReferenceBuilder
{
    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var RecurringDataUtil
     */
    private $recurringDataUtil;

    /**
     * @param Session $customerSession
     * @param RecurringDataUtil $recurringDataUtil
     */
    public function __construct(
        Session $customerSession,
        RecurringDataUtil $recurringDataUtil
    ) {
        $this->customerSession = $customerSession;
        $this->recurringDataUtil = $recurringDataUtil;
    }

    /**
     * Add the customer reference to the customer details
     *
     * @param CustomerDetails $customerDetails
     * @param OrderPaymentInterface $payment
     * @param string $reference
     * @return void
     */
    public function build(CustomerDetails $customerDetails, OrderPaymentInterface $payment, string $reference): void
    {
        if ($this->customerSession->isLoggedIn() &&
            $this->recurringDataUtil->shouldAddRecurringData($payment->getAdditionalInformation())
        ) {
            $customerDetails->addReference($reference);
        }
    }
}
