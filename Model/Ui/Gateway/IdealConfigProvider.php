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

namespace MultiSafepay\ConnectCore\Model\Ui\Gateway;

use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Factory\SdkFactory;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\ConnectCore\Model\Ui\GenericConfigProvider;
use MultiSafepay\Exception\InvalidApiKeyException;
use Psr\Http\Client\ClientExceptionInterface;

class IdealConfigProvider extends GenericConfigProvider
{
    public const CODE = 'multisafepay_ideal';

    /**
     * @var SdkFactory
     */
    private $sdkFactory;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * IdealConfigProvider constructor.
     *
     * @param AssetRepository $assetRepository
     * @param Config $config
     * @param Session $checkoutSession
     * @param Logger $logger
     * @param SdkFactory $sdkFactory
     */
    public function __construct(
        AssetRepository $assetRepository,
        Config $config,
        Session $checkoutSession,
        Logger $logger,
        SdkFactory $sdkFactory
    ) {
        parent::__construct($assetRepository, $config);
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
        $this->sdkFactory = $sdkFactory;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     * @throws ClientExceptionInterface
     * @throws LocalizedException
     */
    public function getConfig(): array
    {
        return [
            'payment' => [
                $this->getCode() => [
                    'issuers' => $this->getIssuers(),
                    'image' => $this->getImage(),
                    'is_preselected' => $this->isPreselected()
                ]
            ]
        ];
    }

    /**
     * @return array
     * @throws ClientExceptionInterface
     */
    public function getIssuers(): array
    {
        try {
            $multiSafepaySdk = $this->sdkFactory->get();
        } catch (InvalidApiKeyException $invalidApiKeyException) {
            $this->logger->logInvalidApiKeyException($invalidApiKeyException);
            return [];
        } catch (ApiException $apiException) {
            $orderId = $this->checkoutSession->getLastRealOrder()->getIncrementId();
            $this->logger->logGetIssuersApiException($orderId, $apiException);
            return [];
        }

        $issuerListing = $multiSafepaySdk->getIssuerManager()->getIssuersByGatewayCode('IDEAL');

        $issuers = [];

        foreach ($issuerListing as $issuer) {
            $issuers[] = [
                'code' => $issuer->getCode(),
                'description' => $issuer->getDescription()
            ];
        }
        return $issuers;
    }
}
