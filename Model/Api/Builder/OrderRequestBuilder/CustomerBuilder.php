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

use Magento\Customer\Model\Session;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\Header;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\CustomerDetails;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\CustomerBuilder\AddressBuilder;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\CustomerBuilder\IpAddressBuilder;
use MultiSafepay\ConnectCore\Util\RecurringDataUtil;

class CustomerBuilder implements OrderRequestBuilderInterface
{
    /**
     * @var ResolverInterface
     */
    private $localeResolver;

    /**
     * @var CustomerDetails
     */
    private $customerDetails;

    /**
     * @var AddressBuilder
     */
    private $address;

    /**
     * @var Header
     */
    private $httpHeader;

    /**
     * @var IpAddressBuilder
     */
    private $ipAddressBuilder;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var RecurringDataUtil
     */
    private $recurringDataUtil;

    /**
     * Customer constructor.
     *
     * @param AddressBuilder $address
     * @param CustomerDetails $customerDetails
     * @param Header $httpHeader
     * @param IpAddressBuilder $ipAddressBuilder
     * @param ResolverInterface $localeResolver
     * @param Session $customerSession
     * @param RecurringDataUtil $recurringDataUtil
     */
    public function __construct(
        AddressBuilder $address,
        CustomerDetails $customerDetails,
        Header $httpHeader,
        IpAddressBuilder $ipAddressBuilder,
        ResolverInterface $localeResolver,
        Session $customerSession,
        RecurringDataUtil $recurringDataUtil
    ) {
        $this->address = $address;
        $this->customerDetails = $customerDetails;
        $this->httpHeader = $httpHeader;
        $this->localeResolver = $localeResolver;
        $this->ipAddressBuilder = $ipAddressBuilder;
        $this->customerSession = $customerSession;
        $this->recurringDataUtil = $recurringDataUtil;
    }

    /**
     * @param OrderInterface $order
     * @param OrderPaymentInterface $payment
     * @param OrderRequest $orderRequest
     * @throws NoSuchEntityException
     */
    public function build(
        OrderInterface $order,
        OrderPaymentInterface $payment,
        OrderRequest $orderRequest
    ): void {
        $billingAddress = $order->getBillingAddress();
        if ($billingAddress === null) {
            $msg = __('The transaction could not be created because the billing address is missing');
            throw new NoSuchEntityException($msg);
        }

        $customerAddress = $this->address->build($order->getBillingAddress());

        $this->customerDetails->addLocale((string)$this->localeResolver->emulate($order->getStoreId()))
            ->addFirstName($billingAddress->getFirstname())
            ->addLastName($billingAddress->getLastname())
            ->addAddress($customerAddress)
            ->addPhoneNumberAsString($billingAddress->getTelephone() ?? '')
            ->addEmailAddressAsString($billingAddress->getEmail())
            ->addUserAgent($this->httpHeader->getHttpUserAgent());

        $this->ipAddressBuilder->build($this->customerDetails, $order);

        if ($this->customerSession->isLoggedIn() &&
            $this->recurringDataUtil->shouldAddRecurringData($payment->getAdditionalInformation())
        ) {
            $this->customerDetails->addReference((string) $order->getCustomerId());
        }

        $orderRequest->addCustomer($this->customerDetails);
    }
}
