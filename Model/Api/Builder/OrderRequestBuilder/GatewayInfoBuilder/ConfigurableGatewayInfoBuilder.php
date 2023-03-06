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

namespace MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\GatewayInfoBuilder;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Config\Config as PaymentConfig;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfo\Meta;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Model\Api\Validator\EmailAddressValidator;
use MultiSafepay\ConnectCore\Util\JsonHandler;

class ConfigurableGatewayInfoBuilder implements GatewayInfoBuilderInterface
{
    public const CHECKOUT_FIELDS = [
        'date_of_birth',
        'account_number',
        'email_address'
    ];

    /**
     * @var PaymentConfig
     */
    private $paymentConfig;

    /**
     * @var Meta
     */
    protected $meta;

    /**
     * @var JsonHandler
     */
    protected $jsonHandler;

    /**
     * @var EmailAddressValidator
     */
    private $emailAddressValidator;

    /**
     * GatewayInfo constructor.
     *
     * @param PaymentConfig $paymentConfig
     * @param Meta $meta
     * @param JsonHandler $jsonHandler
     * @param EmailAddressValidator $emailAddressValidator
     */
    public function __construct(
        PaymentConfig $paymentConfig,
        Meta $meta,
        JsonHandler $jsonHandler,
        EmailAddressValidator $emailAddressValidator
    ) {
        $this->paymentConfig = $paymentConfig;
        $this->jsonHandler = $jsonHandler;
        $this->meta = $meta;
        $this->emailAddressValidator = $emailAddressValidator;
    }

    /**
     * Build the gateway info for gateways with configurable checkout fields
     *
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

        if ($billingAddress->getTelephone() === null) {
            throw new LocalizedException(__('This payment gateway requires a valid telephone number'));
        }

        $additionalInformation = $payment->getAdditionalInformation();

        if (isset($additionalInformation['date_of_birth'])) {
            $this->meta->addBirthdayAsString($additionalInformation['date_of_birth']);
        }

        if (isset($additionalInformation['account_number'])) {
            $this->meta->addBankAccountAsString($additionalInformation['account_number']);
        }

        if (isset($additionalInformation['email_address'])) {
            $this->addEmailAddress($additionalInformation['email_address'], $order->getCustomerEmail());
        }

        $this->meta->addPhoneAsString($billingAddress->getTelephone());

        $this->paymentConfig->setMethodCode($payment->getMethod());
        $this->addCollectingFlowIds((string)$order->getCustomerGroupId());

        return $this->meta;
    }

    /**
     * Check if there are collecting flow ids and add them if needed
     *
     * @param string $customerGroupId
     */
    private function addCollectingFlowIds(string $customerGroupId): void
    {
        // Check for collecting flow ids
        if (!$this->paymentConfig->getValue(Config::USE_CUSTOMER_GROUP_COLLECTING_FLOWS)) {
            return;
        }

        $configuredCustomerGroups = $this->jsonHandler->readJSON(
            $this->paymentConfig->getValue(Config::CUSTOMER_GROUP_COLLECTING_FLOWS)
        );

        foreach ($configuredCustomerGroups as $configuredCustomerGroup) {
            if (!isset($configuredCustomerGroup['customer_group'], $configuredCustomerGroup['collection_flow_id'])) {
                continue;
            }

            if ($configuredCustomerGroup['customer_group'] === $customerGroupId) {
                $this->meta->addData(['collecting_flow' => $configuredCustomerGroup['collection_flow_id']]);
            }
        }
    }

    /**
     * Add e-mail address from checkout field, if not exists add customer e-mail address
     *
     * @param string $emailAddress
     * @param string $orderEmailAddress
     */
    private function addEmailAddress(string $emailAddress, string $orderEmailAddress): void
    {
        if ($this->emailAddressValidator->validate($emailAddress)) {
            $this->meta->addEmailAddressAsString($emailAddress);
            return;
        }

        $this->meta->addEmailAddressAsString($orderEmailAddress);
    }
}
