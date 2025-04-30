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

namespace MultiSafepay\ConnectCore\Util;

use Exception;
use MultiSafepay\Api\Tokens\Token;
use MultiSafepay\ConnectCore\Factory\SdkFactory;
use MultiSafepay\Exception\ApiException;
use Psr\Http\Client\ClientExceptionInterface;

class RecurringTokensUtil
{
    /**
     * @var SdkFactory
     */
    private $sdkFactory;

    /**
     * @param SdkFactory $sdkFactory
     */
    public function __construct(
        SdkFactory $sdkFactory
    ) {
        $this->sdkFactory = $sdkFactory;
    }

    /**
     * Get a list of customer recurring tokens, returns no tokens if customer can not be found
     *
     * @param string $customerId
     * @param array $paymentConfig
     * @param int|null $storeId
     * @return array
     */
    public function getListByGatewayCode(string $customerId, array $paymentConfig, ?int $storeId = null): array
    {
        try {
            $sdk = $this->sdkFactory->create($storeId);

            return $sdk->getTokenManager()->getListByGatewayCodeAsArray(
                $customerId,
                $paymentConfig['gateway_code']
            );
        } catch (ApiException | ClientExceptionInterface | Exception $exception) {
            return [];
        }
    }
}
