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

namespace MultiSafepay\ConnectCore\Model\Method;

use Magento\Framework\App\Config\Initial;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Payment\Gateway\Command\CommandManagerInterface;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\Command\ResultInterface;
use Magento\Payment\Gateway\Config\Config;
use Magento\Payment\Gateway\Config\ValueHandlerPoolInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Payment\Gateway\Validator\CountryValidatorFactory;
use Magento\Payment\Gateway\Validator\ValidatorPoolInterface;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\Adapter;
use Magento\Quote\Api\Data\CartInterface;
use MultiSafepay\ConnectCore\Gateway\Validator\CurrencyValidatorFactory;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GenericAdapter extends Adapter
{
    /**
     * @var CommandPoolInterface
     */
    private $commandPool;

    /**
     * @var string
     */
    private $code;

    /**
     * @var string
     */
    private $baseMethodCode = '';

    /**
     * @var array
     */
    private $baseGenericConfig = [];

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @var PaymentDataObjectFactory
     */
    private $paymentDataObjectFactory;

    /**
     * @var CommandManagerInterface
     */
    private $commandExecutor;

    /**
     * @var Config
     */
    private $paymentConfig;

    /**
     * @var CountryValidatorFactory
     */
    private $countryValidatorFactory;

    /**
     * @var CurrencyValidatorFactory
     */
    private $currencyValidatorFactory;

    /**
     * @var Initial
     */
    private $initialConfig;

    /**
     * GenericAdapter constructor.
     *
     * @param ManagerInterface $eventManager
     * @param ValueHandlerPoolInterface $valueHandlerPool
     * @param PaymentDataObjectFactory $paymentDataObjectFactory
     * @param Config $paymentConfig
     * @param CountryValidatorFactory $countryValidatorFactory
     * @param CurrencyValidatorFactory $currencyValidatorFactory
     * @param Initial $initialConfig
     * @param string $code
     * @param string $formBlockType
     * @param string $infoBlockType
     * @param CommandPoolInterface|null $commandPool
     * @param ValidatorPoolInterface|null $validatorPool
     * @param CommandManagerInterface|null $commandExecutor
     * @param LoggerInterface|null $logger
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ManagerInterface $eventManager,
        ValueHandlerPoolInterface $valueHandlerPool,
        PaymentDataObjectFactory $paymentDataObjectFactory,
        Config $paymentConfig,
        CountryValidatorFactory $countryValidatorFactory,
        CurrencyValidatorFactory $currencyValidatorFactory,
        Initial $initialConfig,
        $code,
        $formBlockType,
        $infoBlockType,
        CommandPoolInterface $commandPool = null,
        ValidatorPoolInterface $validatorPool = null,
        CommandManagerInterface $commandExecutor = null,
        LoggerInterface $logger = null
    ) {
        $this->countryValidatorFactory = $countryValidatorFactory;
        $this->currencyValidatorFactory = $currencyValidatorFactory;
        $this->paymentConfig = $paymentConfig;
        $this->initialConfig = $initialConfig;
        $this->commandPool = $commandPool;
        $this->code = $code;
        $this->eventManager = $eventManager;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
        $this->commandExecutor = $commandExecutor;
        $this->paymentConfig = $paymentConfig;

        parent::__construct(
            $eventManager,
            $valueHandlerPool,
            $paymentDataObjectFactory,
            $code,
            $formBlockType,
            $infoBlockType,
            $commandPool,
            $validatorPool,
            $commandExecutor,
            $logger
        );
    }

    /**
     * @param string $code
     * @param string $baseCode
     * @return $this
     */
    public function initGeneric(string $code, string $baseCode): GenericAdapter
    {
        $this->setCode($code)->setBaseMethodCode($baseCode);
        $this->baseGenericConfig = $this->getBaseGenericConfig();

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function canAuthorize(): bool
    {
        return $this->canPerformCommand('authorize');
    }

    /**
     * @inheritdoc
     */
    public function canCapture(): bool
    {
        return $this->canPerformCommand('capture');
    }

    /**
     * @inheritdoc
     */
    public function canCapturePartial(): bool
    {
        return $this->canPerformCommand('capture_partial');
    }

    /**
     * @inheritdoc
     */
    public function canCaptureOnce(): bool
    {
        return $this->canPerformCommand('capture_once');
    }

    /**
     * @inheritdoc
     */
    public function canRefund(): bool
    {
        return $this->canPerformCommand('refund');
    }

    /**
     * @inheritdoc
     */
    public function canRefundPartialPerInvoice(): bool
    {
        return $this->canPerformCommand('refund_partial_per_invoice');
    }

    /**
     * @inheritdoc
     */
    public function canVoid(): bool
    {
        return $this->canPerformCommand('void');
    }

    /**
     * @inheritdoc
     */
    public function canUseInternal(): bool
    {
        return (bool)$this->getConfiguredValue('can_use_internal');
    }

    /**
     * @inheritdoc
     */
    public function canUseCheckout(): bool
    {
        return (bool)$this->getConfiguredValue('can_use_checkout');
    }

    /**
     * @inheritdoc
     */
    public function canEdit(): bool
    {
        return (bool)$this->getConfiguredValue('can_edit');
    }

    /**
     * @inheritdoc
     */
    public function canFetchTransactionInfo(): bool
    {
        return $this->canPerformCommand('fetch_transaction_info');
    }

    /**
     * @inheritdoc
     */
    public function canReviewPayment(): bool
    {
        return $this->canPerformCommand('review_payment');
    }

    /**
     * @inheritdoc
     */
    public function isGateway(): bool
    {
        return (bool)$this->getConfiguredValue('is_gateway');
    }

    /**
     * @inheritdoc
     */
    public function isOffline(): bool
    {
        return (bool)$this->getConfiguredValue('is_offline');
    }

    /**
     * @inheritdoc
     */
    public function isInitializeNeeded(): bool
    {
        return (bool)(int)$this->getConfiguredValue('can_initialize');
    }

    /**
     * @inheritdoc
     */
    public function isAvailable(CartInterface $quote = null)
    {
        if (!$this->isActive($quote ? $quote->getStoreId() : null)) {
            return false;
        }

        $checkResult = new DataObject();
        $checkResult->setData('is_available', true);

        $this->eventManager->dispatch(
            'payment_method_is_active',
            [
                'result' => $checkResult,
                'method_instance' => $this,
                'quote' => $quote,
            ]
        );

        return $checkResult->getData('is_available');
    }

    /**
     * @inheritdoc
     */
    public function isActive($storeId = null): bool
    {
        return (bool)$this->getConfiguredValue('active', $storeId);
    }

    /**
     * @param string $country
     * @return bool
     */
    public function canUseForCountry($country): bool
    {
        return $this->validateByInstance(
            $this->countryValidatorFactory,
            ['country' => $country, 'storeId' => $this->getStore()]
        );
    }

    /**
     * @param string $currencyCode
     * @return bool
     */
    public function canUseForCurrency($currencyCode): bool
    {
        return $this->validateByInstance(
            $this->currencyValidatorFactory,
            ['currency' => $currencyCode, 'storeId' => $this->getStore()]
        );
    }

    /**
     *
     * @param string $commandCode
     * @return bool
     */
    public function canPerformCommand($commandCode): bool
    {
        return (bool)$this->getConfiguredValue('can_' . $commandCode);
    }

    /**
     * @param string $field
     * @param int|null $storeId
     * @return bool|mixed|null
     */
    private function getConfiguredValue(string $field, $storeId = null)
    {
        $this->setPaymentConfigCode($this->code);
        $storeId = $storeId ?: $this->getStore();
        $configData = $this->paymentConfig->getValue($field, $storeId);

        if (!$configData) {
            $configData = $this->baseGenericConfig[$field] ?? null;
        }

        return $configData;
    }

    /**
     *
     * @return array
     */
    public function getBaseGenericConfig(): array
    {
        return $this->initialConfig
                   ->getData('default')[Data::XML_PATH_PAYMENT_METHODS][$this->baseMethodCode];
    }

    /**
     * @param string $field
     * @param null $storeId
     * @return bool|mixed|null
     */
    public function getConfigData($field, $storeId = null)
    {
        return $this->getConfiguredValue($field, $storeId);
    }

    /**
     * @inheritdoc
     */
    public function fetchTransactionInfo(InfoInterface $payment, $transactionId)
    {
        return $this->executeCommand(
            'fetch_transaction_information',
            ['payment' => $payment, 'transactionId' => $transactionId]
        );
    }

    /**
     * @inheritdoc
     */
    public function order(InfoInterface $payment, $amount)
    {
        $this->executeCommand(
            'order',
            ['payment' => $payment, 'amount' => $amount]
        );

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function authorize(InfoInterface $payment, $amount)
    {
        $this->executeCommand(
            'authorize',
            ['payment' => $payment, 'amount' => $amount]
        );

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function capture(InfoInterface $payment, $amount)
    {
        $this->executeCommand(
            'capture',
            ['payment' => $payment, 'amount' => $amount]
        );

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function refund(InfoInterface $payment, $amount)
    {
        $this->executeCommand(
            'refund',
            ['payment' => $payment, 'amount' => $amount]
        );

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function cancel(InfoInterface $payment)
    {
        $this->executeCommand('cancel', ['payment' => $payment]);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function void(InfoInterface $payment)
    {
        $this->executeCommand('void', ['payment' => $payment]);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function acceptPayment(InfoInterface $payment)
    {
        $this->executeCommand('accept_payment', ['payment' => $payment]);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function denyPayment(InfoInterface $payment)
    {
        $this->executeCommand('deny_payment', ['payment' => $payment]);

        return $this;
    }

    /**
     * @param $commandCode
     * @param array $arguments
     * @return \Magento\Payment\Gateway\Command\ResultInterface|null
     * @throws \Magento\Framework\Exception\NotFoundException
     * @throws \Magento\Payment\Gateway\Command\CommandException
     */
    private function executeCommand($commandCode, array $arguments = [])
    {
        if (!$this->canPerformCommand($commandCode)) {
            return null;
        }

        /** @var InfoInterface|null $payment */
        $payment = null;
        if (isset($arguments['payment']) && $arguments['payment'] instanceof InfoInterface) {
            $payment = $arguments['payment'];
            $arguments['payment'] = $this->paymentDataObjectFactory->create($arguments['payment']);
        }

        if ($this->commandExecutor !== null) {
            return $this->commandExecutor->executeByCode($commandCode, $payment, $arguments);
        }

        if ($this->commandPool === null) {
            throw new \DomainException("The command pool isn't configured for use.");
        }

        $command = $this->commandPool->get($commandCode);

        return $command->execute($arguments);
    }

    /**
     * @inheritdoc
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     * @return $this
     */
    public function setCode(string $code): GenericAdapter
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @param string $code
     * @return $this
     */
    public function setBaseMethodCode(string $code): GenericAdapter
    {
        $this->baseMethodCode = $code;

        return $this;
    }

    /**
     * @param string $code
     * @return Config
     */
    public function setPaymentConfigCode(string $code): Config
    {
        $this->paymentConfig->setMethodCode($code);

        return $this->paymentConfig;
    }

    /**
     * @inheritdoc
     */
    public function getTitle(): string
    {
        return (string)$this->getConfiguredValue('title');
    }

    /**
     * @inheritdoc
     * @since 100.4.0
     */
    public function canSale(): bool
    {
        return $this->canPerformCommand('sale');
    }

    /**
     * @inheritdoc
     */
    public function sale(InfoInterface $payment, float $amount): ?ResultInterface
    {
        $this->executeCommand(
            'sale',
            ['payment' => $payment, 'amount' => $amount]
        );
    }

    /**
     * @param $validator
     * @param array $validatorParams
     * @return bool
     */
    private function validateByInstance($validator, array $validatorParams): bool
    {
        try {
            $validator = $validator->create(
                ['config' => $this->setPaymentConfigCode($this->getCode())]
            );
        } catch (\Exception $e) {
            return true;
        }

        $result = $validator->validate($validatorParams);

        return $result->isValid();
    }
}
