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

namespace MultiSafepay\ConnectCore\Gateway\Validator\Gateway;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use MultiSafepay\ConnectCore\Gateway\Validator\Gateway\FieldValidator\EmptyFieldValidator;
use MultiSafepay\ConnectCore\Gateway\Validator\Gateway\FieldValidator\GatewayFieldValidatorPool;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\TransactionTypeBuilder;

class BaseGatewayValidator extends AbstractValidator
{
    public const AVAILABLE_VALIDATORS = [];
    public const EMPTY_VALIDATOR_FIELDS = [];

    /**
     * @var GatewayFieldValidatorPool
     */
    private $gatewayFieldValidatorPool;

    /**
     * BaseGatewayValidator constructor.
     *
     * @param ResultInterfaceFactory $resultFactory
     * @param GatewayFieldValidatorPool $gatewayFieldValidatorPool
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory,
        GatewayFieldValidatorPool $gatewayFieldValidatorPool
    ) {
        $this->gatewayFieldValidatorPool = $gatewayFieldValidatorPool;
        parent::__construct($resultFactory);
    }

    /**
     * @inheritDoc
     */
    public function validate(array $validationSubject): ResultInterface
    {
        $payment = $validationSubject['payment'] ?? null;

        if (!$payment) {
            return $this->createResult(false, [__('Can\'t get the payment information')]);
        }

        // If transaction type is not set to 'direct' then do not validate additional fields
        if ($payment->getMethodInstance()->getConfigData('transaction_type') !==
            TransactionTypeBuilder::TRANSACTION_TYPE_DIRECT_VALUE
        ) {
            return $this->createResult(true);
        }

        if ($paymentAdditionalInformation = $payment->getAdditionalInformation()) {
            $preparedAdditionalData = $this->prepareDataForValidation($paymentAdditionalInformation);

            foreach ($this->getAvailableValidators() as $validatorCode) {
                $validator = $this->gatewayFieldValidatorPool->getValidatorByCode($validatorCode);

                if ($validator && !$validator->validate($preparedAdditionalData)) {
                    return $this->createResult(false, [$validator->getValidationMessage()]);
                }
            }
        }

        return $this->createResult(true);
    }

    /**
     * @param array $paymentAdditionalInformation
     * @return array
     */
    private function prepareDataForValidation(array $paymentAdditionalInformation): array
    {
        $emptyValidatorFields = static::EMPTY_VALIDATOR_FIELDS;

        return $emptyValidatorFields ? array_merge(
            $paymentAdditionalInformation,
            [EmptyFieldValidator::EMPTY_VALIDATOR_FIELDS_KEY_NAME => $emptyValidatorFields]
        ) : $paymentAdditionalInformation;
    }

    /**
     * @return array
     */
    private function getAvailableValidators(): array
    {
        return static::AVAILABLE_VALIDATORS;
    }
}
