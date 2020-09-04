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

namespace MultiSafepay\ConnectCore\Logger;

use Monolog\Logger as CoreLogger;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\Exception\InvalidApiKeyException;

class Logger extends CoreLogger
{
    /**
     * @param string $orderId
     * @param ApiException $apiException
     * @return void
     */
    public function logPaymentLinkError(string $orderId, ApiException $apiException): void
    {
        $this->error(
            '(Order ID: ' . $orderId . ') MultiSafepay error when trying to retrieve the payment link. Error: ' .
            $apiException->getCode() . ' ' . $apiException->getMessage()
        );

        $this->debug($apiException->getDetails());
    }

    /**
     * @param string $orderId
     * @param ApiException $apiException
     * @return void
     */
    public function logGetRequestApiException(string $orderId, ApiException $apiException): void
    {
        $this->error(
            '(Order ID: ' . $orderId . ') MultiSafepay error when trying to retrieve the transaction. Error: ' .
            $apiException->getCode() . ' ' . $apiException->getMessage()
        );

        $this->debug($apiException->getDetails());
    }

    /**
     * @param string $orderId
     * @param ApiException $apiException
     * @return void
     */
    public function logGetIssuersApiException(string $orderId, ApiException $apiException): void
    {
        $this->error(
            '(Order ID: ' . $orderId . ') MultiSafepay error when trying to retrieve the iDEAL issuers. Error: ' .
            $apiException->getCode() . ' ' . $apiException->getMessage()
        );

        $this->debug($apiException->getDetails());
    }

    /**
     * @param string $orderId
     * @param ApiException $apiException
     * @return void
     */
    public function logUpdateRequestApiException(string $orderId, ApiException $apiException): void
    {
        $this->error(
            '(Order ID: ' . $orderId . ') MultiSafepay error when trying to update the transaction. Error: ' .
            $apiException->getCode() . ' ' . $apiException->getMessage()
        );

        $this->debug($apiException->getDetails());
    }

    /**
     * @param InvalidApiKeyException $invalidApiKeyException
     * @return void
     */
    public function logInvalidApiKeyException(InvalidApiKeyException $invalidApiKeyException): void
    {
        $this->error('The MultiSafepay API key is invalid. ' . $invalidApiKeyException->getMessage());
    }

    /**
     * @param $orderId
     * @param $paymentUrl
     * @return void
     */
    public function logPaymentRedirectInfo($orderId, $paymentUrl): void
    {
        $this->info('(Order ID: ' . $orderId . ') User redirected to the following page: ' . $paymentUrl);
    }

    /**
     * @param $orderId
     */
    public function logPaymentSuccessInfo($orderId): void
    {
        $this->info('(Order ID: ' . $orderId . ') User redirected to the success page.');
    }

    /**
     * @param $orderId
     */
    public function logMissingSecureToken($orderId): void
    {
        $this->error('(Order ID: ' . $orderId . ') secureToken missing from request parameters.');
    }

    /**
     * @param $orderId
     */
    public function logInvalidSecureToken($orderId): void
    {
        $this->error('(Order ID: ' . $orderId . ') Invalid secureToken provided in request parameters.');
    }
}
