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

use Magento\Framework\Exception\LocalizedException;
use MultiSafepay\ConnectCore\Model\Ui\GenericConfigProvider;
use Psr\Http\Client\ClientExceptionInterface;

class IdealConfigProvider extends GenericConfigProvider
{
    public const CODE = 'multisafepay_ideal';

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
        $issuers = [];

        if ($multiSafepaySdk = $this->getSdk()) {
            $issuerListing = $multiSafepaySdk->getIssuerManager()->getIssuersByGatewayCode('IDEAL');

            foreach ($issuerListing as $issuer) {
                $issuers[] = [
                    'code' => $issuer->getCode(),
                    'description' => $issuer->getDescription()
                ];
            }
        }

        return $issuers;
    }
}
