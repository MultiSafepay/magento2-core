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
use MultiSafepay\ConnectCore\Api\RecurringDetailsInterface;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\AmexConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\AmexRecurringConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\CreditCardConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\CreditCardRecurringConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\MaestroConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\MastercardConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\MastercardRecurringConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\VisaConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\VisaRecurringConfigProvider;
use MultiSafepay\ConnectCore\Util\JsonHandler;
use MultiSafepay\ConnectCore\Util\VaultUtil;
use \MultiSafepay\ConnectCore\Api\PaymentTokenInterface as MultiSafepayPaymentTokenInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Vault
{
    public const VAULT_GATEWAYS = [
        CreditCardConfigProvider::CODE => CreditCardRecurringConfigProvider::CODE,
        VisaConfigProvider::CODE => VisaRecurringConfigProvider::CODE,
        MastercardConfigProvider::CODE => MastercardRecurringConfigProvider::CODE,
        AmexConfigProvider::CODE => AmexRecurringConfigProvider::CODE
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
     * @var MaestroConfigProvider
     */
    private $maestroConfigProvider;

    /**
     * VaultUtil constructor.
     *
     * @param EncryptorInterface $encryptor
     * @param JsonHandler $jsonHandler
     * @param OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory
     * @param MaestroConfigProvider $maestroConfigProvider
     * @param PaymentTokenFactoryInterface $paymentTokenFactory
     * @param PaymentTokenManagementInterface $paymentTokenManagement
     * @param PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param VaultUtil $vaultUtil
     */
    public function __construct(
        EncryptorInterface $encryptor,
        JsonHandler $jsonHandler,
        OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory,
        MaestroConfigProvider $maestroConfigProvider,
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
        $this->maestroConfigProvider = $maestroConfigProvider;
    }

    /**
     * @param Payment $payment
     * @param array $recurringDetails
     * @return bool
     * @throws Exception
     */
    public function initialize(Payment $payment, array $recurringDetails): bool
    {
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
            (string)$recurringDetails[RecurringDetailsInterface::EXPIRATION_DATE]
        );

        if ($paymentToken === null) {
            $paymentToken = $this->paymentTokenFactory->create(
                PaymentTokenFactoryInterface::TOKEN_TYPE_CREDIT_CARD
            );

            $publicHash = $this->encryptor->getHash($customerId . $recurringId);

            $paymentToken->setGatewayToken($recurringId)
                ->setCustomerId($customerId)
                ->setPaymentMethodCode($code)
                ->setPublicHash($publicHash);
        }

        $paymentToken->setExpiresAt($expirationDate->format('Y-m-d H:i:s'));

        $paymentToken->setTokenDetails($this->jsonHandler->convertToJSON([
            MultiSafepayPaymentTokenInterface::TYPE => $recurringDetails[RecurringDetailsInterface::TYPE],
            MultiSafepayPaymentTokenInterface::MASKED_CC => $recurringDetails[RecurringDetailsInterface::CARD_LAST4],
            MultiSafepayPaymentTokenInterface::EXPIRATION_DATE => $expirationDate->format('m/Y')
        ]));

        $paymentToken->setIsActive(true);
        $paymentToken->setIsVisible(true);

        $this->paymentTokenRepository->save($paymentToken);

        return $paymentToken;
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
            RecurringDetailsInterface::EXPIRATION_DATE,
            RecurringDetailsInterface::CARD_LAST4,
        ];

        if (empty($recurringDetails)) {
            return false;
        }

        $maestroGatewayCode = $this->maestroConfigProvider->getMaestroGatewayCode();

        foreach ($arrayFields as $field) {
            if (empty($recurringDetails[$field])) {
                return false;
            }

            if ($field === RecurringDetailsInterface::TYPE && $recurringDetails[$field] === $maestroGatewayCode) {
                return  false;
            }
        }

        return true;
    }

    /**
     * @param string $expirationDate
     * @return DateTime
     * @throws Exception
     */
    private function formatExpirationDate(string $expirationDate): DateTime
    {
        $year = substr($expirationDate, 0, 2);
        $date = substr($expirationDate, 2, 4);

        return new DateTime(sprintf("%s-%02d-01 00:00:00", $year, $date));
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
     * @param InfoInterface $payment
     * @return OrderPaymentExtension
     */
    public function getExtensionAttributes(InfoInterface $payment): OrderPaymentExtension
    {
        if ($extensionAttributes = $payment->getExtensionAttributes()) {
            $extensionAttributes = $this->paymentExtensionFactory->create();
            $payment->setExtensionAttributes($extensionAttributes);
        }

        return $extensionAttributes;
    }
}
