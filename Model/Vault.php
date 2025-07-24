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
 * Copyright © 2020 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 *
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Model;

use DateTime;
use Exception;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\Data\OrderPaymentExtension;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Sales\Model\Order\Payment;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Magento\Vault\Model\PaymentToken;
use MultiSafepay\ConnectCore\Api\RecurringDetailsInterface;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\AmexConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\AmexRecurringConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\BancontactConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\BancontactRecurringConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\CreditCardConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\CreditCardRecurringConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\DirectDebitConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\DirectDebitRecurringConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\IdealConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\IdealRecurringConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\MaestroConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\MaestroRecurringConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\MastercardConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\MastercardRecurringConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\VisaConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\VisaRecurringConfigProvider;
use MultiSafepay\ConnectCore\Util\JsonHandler;
use MultiSafepay\ConnectCore\Util\VaultUtil;
use MultiSafepay\ConnectCore\Api\PaymentTokenInterface as MultiSafepayPaymentTokenInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Vault
{
    public const VAULT_GATEWAYS = [
        CreditCardConfigProvider::CODE => CreditCardRecurringConfigProvider::CODE,
        VisaConfigProvider::CODE => VisaRecurringConfigProvider::CODE,
        MastercardConfigProvider::CODE => MastercardRecurringConfigProvider::CODE,
        AmexConfigProvider::CODE => AmexRecurringConfigProvider::CODE,
        IdealConfigProvider::CODE => IdealRecurringConfigProvider::CODE,
        DirectDebitConfigProvider::CODE => DirectDebitRecurringConfigProvider::CODE,
        MaestroConfigProvider::CODE => MaestroRecurringConfigProvider::CODE,
        BancontactConfigProvider::CODE => BancontactRecurringConfigProvider::CODE
    ];

    /**
     * @var PaymentTokenManagementInterface
     */
    private $paymentTokenManagement;

    /**
     * @var PaymentTokenFactoryInterface
     */
    private $paymentTokenFactory;

    /**
     * @var PaymentTokenRepositoryInterface
     */
    private $paymentTokenRepository;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @var OrderPaymentExtensionInterfaceFactory
     */
    private $paymentExtensionFactory;

    /**
     * @var VaultUtil
     */
    private $vaultUtil;

    /**
     * @var JsonHandler
     */
    private $jsonHandler;

    /**
     * @var array
     */
    private $types;

    /**
     * Vault constructor
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     *
     * @param EncryptorInterface $encryptor
     * @param JsonHandler $jsonHandler
     * @param OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory
     * @param DirectDebitConfigProvider $directDebitConfigProvider
     * @param IdealConfigProvider $idealConfigProvider
     * @param PaymentTokenFactoryInterface $paymentTokenFactory
     * @param PaymentTokenManagementInterface $paymentTokenManagement
     * @param PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param VaultUtil $vaultUtil
     */
    public function __construct(
        EncryptorInterface $encryptor,
        JsonHandler $jsonHandler,
        OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory,
        DirectDebitConfigProvider $directDebitConfigProvider,
        IdealConfigProvider $idealConfigProvider,
        PaymentTokenFactoryInterface $paymentTokenFactory,
        PaymentTokenManagementInterface $paymentTokenManagement,
        PaymentTokenRepositoryInterface $paymentTokenRepository,
        VaultUtil $vaultUtil
    ) {
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->encryptor = $encryptor;
        $this->paymentExtensionFactory = $paymentExtensionFactory;
        $this->vaultUtil = $vaultUtil;
        $this->jsonHandler = $jsonHandler;
        $this->types = [
            $idealConfigProvider->getGatewayCode(),
            $directDebitConfigProvider->getGatewayCode(),
        ];
    }

    /**
     * Initialize the Vault payment token process
     *
     * @param Payment $payment
     * @param array $paymentDetails
     * @return bool
     * @throws Exception
     */
    public function initialize(Payment $payment, array $paymentDetails): bool
    {
        $recurringDetails = $this->getRecurringDetailsFromPaymentDetails($paymentDetails);

        if (!$this->validateRecurringDetails($recurringDetails)
            || !$this->vaultUtil->validateVaultTokenEnabler($payment->getAdditionalInformation())
        ) {
            return false;
        }

        $code = $this->getVaultGatewayCode($payment->getMethod());
        if ($code === null) {
            return false;
        }

        $order = $payment->getOrder();
        $paymentToken = $this->getVaultPaymentToken((int)$order->getCustomerId(), $code, $recurringDetails);

        if ($paymentToken !== null) {
            $extensionAttributes = $this->getExtensionAttributes($payment);
            $extensionAttributes->setVaultPaymentToken($paymentToken);

            return true;
        }

        return false;
    }

    /**
     * @param int $customerId
     * @param string $code
     * @param array $recurringDetails
     * @return PaymentTokenInterface
     * @throws Exception
     */
    public function getVaultPaymentToken(int $customerId, string $code, array $recurringDetails): PaymentTokenInterface
    {
        $recurringId = $recurringDetails[RecurringDetailsInterface::RECURRING_ID];

        $paymentToken = $this->paymentTokenManagement->getByGatewayToken(
            $recurringId,
            $code,
            $customerId
        );

        $expirationDate = $this->formatExpirationDate(
            (string)$recurringDetails[RecurringDetailsInterface::EXPIRATION_DATE],
            (string)$recurringDetails[RecurringDetailsInterface::TYPE]
        );

        if ($paymentToken === null) {
            $publicHash = $this->encryptor->getHash($customerId . $recurringId);

            // Try to retrieve already existing payment token. Only create new token if there is not already a token.
            $paymentToken = $this->paymentTokenManagement->getByPublicHash($publicHash, $customerId) ??
                            $this->paymentTokenFactory->create(PaymentTokenFactoryInterface::TOKEN_TYPE_CREDIT_CARD);

            $paymentToken->setGatewayToken($recurringId)
                ->setCustomerId($customerId)
                ->setPaymentMethodCode($code)
                ->setPublicHash($publicHash);
        }

        $paymentToken->setExpiresAt($expirationDate->format('Y-m-d H:i:s'));

        $paymentToken->setTokenDetails($this->jsonHandler->convertToJSON([
            MultiSafepayPaymentTokenInterface::TYPE => $recurringDetails[RecurringDetailsInterface::TYPE],
            MultiSafepayPaymentTokenInterface::MASKED_CC => $recurringDetails[RecurringDetailsInterface::CARD_LAST4],
            MultiSafepayPaymentTokenInterface::EXPIRATION_DATE => $expirationDate->format('m/Y'),
        ]));

        $paymentToken->setIsActive(true);
        $paymentToken->setIsVisible(true);

        $this->paymentTokenRepository->save($paymentToken);

        return $paymentToken;
    }

    /**
     * Get the recurring details from payment details
     *
     * @param array $paymentDetails
     * @return array
     */
    private function getRecurringDetailsFromPaymentDetails(array $paymentDetails): array
    {
        $transactionType = $paymentDetails['type'] ?? '';

        $isTransactionTypeVault = in_array($transactionType, $this->types, true);

        return [
            RecurringDetailsInterface::RECURRING_ID => $paymentDetails['recurring_id'] ?? '',
            RecurringDetailsInterface::TYPE => $transactionType,
            RecurringDetailsInterface::EXPIRATION_DATE => $isTransactionTypeVault ? ''
                : ($paymentDetails['card_expiry_date'] ?? ''),
            RecurringDetailsInterface::CARD_LAST4 => $isTransactionTypeVault
                ? ('xx' . substr($paymentDetails['account_iban'] ?? '', -2))
                : ($paymentDetails['last4'] ?? ''),
        ];
    }

    /**
     * @param array $recurringDetails
     * @return bool
     */
    private function validateRecurringDetails(array $recurringDetails): bool
    {
        $arrayFields = [
            RecurringDetailsInterface::TYPE,
            RecurringDetailsInterface::RECURRING_ID,
        ];

        if (empty($recurringDetails)) {
            return false;
        }

        foreach ($arrayFields as $field) {
            if (empty($recurringDetails[$field])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $expirationDate
     * @param string $type
     * @return DateTime
     * @throws Exception
     */
    private function formatExpirationDate(string $expirationDate, string $type): DateTime
    {
        $date = substr($expirationDate, 2, 4);

        return in_array($type, $this->types, true)
            ? (new DateTime(sprintf("%s-%02d-01 00:00:00", date('y') + 5, $date)))
            : (new DateTime(sprintf("%s-%02d-01 00:00:00", substr($expirationDate, 0, 2), $date)));
    }

    /**
     * @param string $code
     * @return string|null
     */
    private function getVaultGatewayCode(string $code): ?string
    {
        return self::VAULT_GATEWAYS[$code] ?? null;
    }

    /**
     * @param Payment $payment
     * @return OrderPaymentExtension
     */
    public function getExtensionAttributes(Payment $payment): OrderPaymentExtension
    {
        if ($extensionAttributes = $payment->getExtensionAttributes()) {
            $extensionAttributes = $this->paymentExtensionFactory->create();
            $payment->setExtensionAttributes($extensionAttributes);
        }

        return $extensionAttributes;
    }

    /**
     * Disable the payment token if it does not exist anymore at MultiSafepay
     *
     * @param array $apiTokens
     * @param string $customerReference
     */
    public function removePaymentTokensByList(array $apiTokens, string $customerReference): void
    {
        $tokenRepository = $this->paymentTokenManagement->getListByCustomerId($customerReference);

        $tokenList = [];
        $apiTokenList = [];

        /** @var PaymentToken $token */
        foreach ($tokenRepository as $token) {
            if (in_array($token->getPaymentMethodCode(), self::VAULT_GATEWAYS, true)) {
                $tokenList[] = $token->getGatewayToken();
            }
        }

        foreach ($apiTokens as $apiToken) {
            $apiTokenList[] = $apiToken->getToken();
        }

        $tokensToRemove = array_diff($tokenList, $apiTokenList);

        foreach ($tokensToRemove as $tokenToRemove) {
            /** @var PaymentToken $token */
            foreach ($tokenRepository as $token) {
                if ($token->getGatewayToken() === $tokenToRemove) {
                    $this->paymentTokenRepository->delete($token);
                }
            }
        }
    }
}
