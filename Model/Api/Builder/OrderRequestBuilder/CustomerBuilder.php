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

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\Header;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\CustomerDetails;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\CustomerBuilder\AddressBuilder;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\CustomerBuilder\BrowserInfoBuilder;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\CustomerBuilder\IpAddressBuilder;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\CustomerBuilder\ReferenceBuilder;
use MultiSafepay\Exception\InvalidArgumentException;

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
    private $addressBuilder;

    /**
     * @var Header
     */
    private $httpHeader;

    /**
     * @var IpAddressBuilder
     */
    private $ipAddressBuilder;

    /**
     * @var BrowserInfoBuilder
     */
    private $browserInfoBuilder;

    /**
     * @var ReferenceBuilder
     */
    private $referenceBuilder;

    /**
     * Customer constructor.
     *
     * @param AddressBuilder $addressBuilder
     * @param BrowserInfoBuilder $browserInfoBuilder
     * @param CustomerDetails $customerDetails
     * @param Header $httpHeader
     * @param IpAddressBuilder $ipAddressBuilder
     * @param ResolverInterface $localeResolver
     * @param ReferenceBuilder $referenceBuilder
     */
    public function __construct(
        AddressBuilder $addressBuilder,
        BrowserInfoBuilder $browserInfoBuilder,
        CustomerDetails $customerDetails,
        Header $httpHeader,
        IpAddressBuilder $ipAddressBuilder,
        ResolverInterface $localeResolver,
        ReferenceBuilder $referenceBuilder
    ) {
        $this->addressBuilder = $addressBuilder;
        $this->browserInfoBuilder = $browserInfoBuilder;
        $this->customerDetails = $customerDetails;
        $this->httpHeader = $httpHeader;
        $this->localeResolver = $localeResolver;
        $this->ipAddressBuilder = $ipAddressBuilder;
        $this->referenceBuilder = $referenceBuilder;
    }

    /**
     * @param Order $order
     * @param Payment $payment
     * @param OrderRequest $orderRequest
     * @throws InvalidArgumentException
     * @throws NoSuchEntityException
     */
    public function build(Order $order, Payment $payment, OrderRequest $orderRequest): void
    {
        $billingAddress = $order->getBillingAddress();
        if ($billingAddress === null) {
            $msg = __('The transaction could not be created because the billing address is missing');
            throw new NoSuchEntityException($msg);
        }

        $customerAddress = $this->addressBuilder->build($order->getBillingAddress());

        $this->customerDetails->addLocale((string)$this->localeResolver->emulate($order->getStoreId()))
            ->addUserAgent($this->httpHeader->getHttpUserAgent())
            ->addFirstName($billingAddress->getFirstname())
            ->addLastName($billingAddress->getLastname())
            ->addAddress($customerAddress)
            ->addPhoneNumberAsString($billingAddress->getTelephone() ?? '')
            ->addEmailAddressAsString($billingAddress->getEmail())
            ->addCompanyName($billingAddress->getCompany() ?? '');

        $this->ipAddressBuilder->build($this->customerDetails, $order);
        $this->browserInfoBuilder->build($this->customerDetails, $payment);
        $this->referenceBuilder->build($this->customerDetails, $payment, (string)$order->getCustomerId());

        $orderRequest->addCustomer($this->customerDetails);
    }
}
