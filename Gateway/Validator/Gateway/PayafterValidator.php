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

use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use MultiSafepay\ConnectCore\Gateway\Validator\Gateway\FieldValidator\GatewayFieldValidatorPool;
use MultiSafepay\ConnectCore\Model\Api\Validator\AddressValidator;

class PayafterValidator extends BaseGatewayValidator
{
    public const AVAILABLE_VALIDATORS = [
        'date_of_birth',
        'bank_account_number',
    ];

    /**
     * @var AddressValidator
     */
    private $addressValidator;

    /**
     * PayafterValidator constructor.
     *
     * @param ResultInterfaceFactory $resultFactory
     * @param GatewayFieldValidatorPool $gatewayFieldValidatorPool
     * @param AddressValidator $addressValidator
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory,
        GatewayFieldValidatorPool $gatewayFieldValidatorPool,
        AddressValidator $addressValidator
    ) {
        $this->addressValidator = $addressValidator;
        parent::__construct(
            $resultFactory,
            $gatewayFieldValidatorPool
        );
    }

    /**
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject): ResultInterface
    {
        $payment = $validationSubject['payment'] ?? null;

        if (!$payment) {
            return $this->createResult(false, [__('Can\'t get a payment information')]);
        }

        if (($quote = $payment->getQuote()) === null) {
            $quote = $payment->getOrder();
        }

        if (!$this->addressValidator->validate($quote)) {
            $msg = __('This gateway does not allow a different billing and shipping address');

            return $this->createResult(false, [$msg]);
        }

        return parent::validate($validationSubject);
    }
}
