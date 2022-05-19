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

namespace MultiSafepay\ConnectCore\Model\Api\Builder;

use MultiSafepay\ConnectCore\Factory\SdkFactory;
use Psr\Http\Client\ClientExceptionInterface;

class TokenRequestBuilder
{
    /**
     * @var SdkFactory
     */
    private $sdkFactory;

    /**
     * TokenRequestBuilder constructor.
     *
     * @param SdkFactory $sdkFactory
     */
    public function __construct(
        SdkFactory $sdkFactory
    ) {
        $this->sdkFactory = $sdkFactory;
    }

    /**
     * Get all the payment tokens from a specific customer
     *
     * @param string $customerReference
     * @param int $storeId
     * @return array
     * @throws ClientExceptionInterface
     */
    public function getTokensByCustomerReference(string $customerReference, int $storeId): array
    {
        return $this->sdkFactory->create($storeId)->getTokenManager()->getList($customerReference);
    }

    /**
     * Delete a specific token based on the recurring id and customer reference
     *
     * @param string $customerReference
     * @param string $token
     * @param int $storeId
     * @return bool
     * @throws ClientExceptionInterface
     */
    public function deleteToken(string $customerReference, string $token, int $storeId): bool
    {
        return $this->sdkFactory->create($storeId)->getTokenManager()->delete($token, $customerReference);
    }
}
