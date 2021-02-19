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

namespace MultiSafepay\ConnectCore\Model\Ui;

use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterfaceFactory;
use Magento\Vault\Model\Ui\TokenUiComponentProviderInterface;
use MultiSafepay\ConnectCore\Util\JsonHandler;
use MultiSafepay\ConnectCore\Util\VaultUtil;

class VaultTokenUiComponentProvider implements TokenUiComponentProviderInterface
{
    public const NAME_PATH = 'MultiSafepay_ConnectFrontend/js/view/payment/gateway/method-renderer/vault';

    /**
     * @var TokenUiComponentInterfaceFactory
     */
    private $componentFactory;

    /**
     * @var VaultUtil
     */
    private $vaultUtil;

    /**
     * @var JsonHandler
     */
    private $jsonHandler;

    /**
     * @param TokenUiComponentInterfaceFactory $componentFactory
     * @param JsonHandler $jsonHandler
     * @param VaultUtil $vaultUtil
     */
    public function __construct(
        TokenUiComponentInterfaceFactory $componentFactory,
        JsonHandler $jsonHandler,
        VaultUtil $vaultUtil
    ) {
        $this->componentFactory = $componentFactory;
        $this->vaultUtil = $vaultUtil;
        $this->jsonHandler = $jsonHandler;
    }

    /**
     * Get UI component for token
     *
     * @param PaymentTokenInterface $paymentToken
     * @return TokenUiComponentInterface
     */
    public function getComponentForToken(PaymentTokenInterface $paymentToken): TokenUiComponentInterface
    {
        $details = $this->jsonHandler->readJSON($paymentToken->getTokenDetails());
        $details['icon'] = isset($details['type']) ? $this->vaultUtil->getIcon($details['type']) : [];
        return $this->componentFactory->create(
            [
                'config' => [
                    'code' => $this->getVaultCode(),
                    TokenUiComponentProviderInterface::COMPONENT_DETAILS => $details,
                    TokenUiComponentProviderInterface::COMPONENT_PUBLIC_HASH => $paymentToken->getPublicHash()
                ],
                'name' => static::NAME_PATH
            ]
        );
    }

    /**
     * @return string
     */
    protected function getVaultCode(): string
    {
        return '';
    }
}
