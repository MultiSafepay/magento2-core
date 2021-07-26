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

namespace MultiSafepay\ConnectCore\Logger;

use Exception;
use Magento\Framework\Exception\FileSystemException;
use Monolog\Logger as CoreLogger;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\Exception\InvalidApiKeyException;
use MultiSafepay\Exception\InvalidArgumentException;
use Psr\Http\Client\ClientExceptionInterface;

class Logger extends CoreLogger
{
    public const LOGGER_INFO_TYPE = 'info';

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
     * @param Exception $exception
     * @param int $logLevel
     */
    public function logExceptionForOrder(string $orderId, Exception $exception, int $logLevel = self::DEBUG): void
    {
        $this->addRecord(
            $logLevel,
            sprintf(
                '(Order ID: %1$s) %2$s: %6$s (code: %3$d, line: %4$d, file: %5$s)',
                $orderId,
                static::getLevelName($logLevel),
                $exception->getCode(),
                $exception->getLine(),
                $exception->getFile(),
                $exception->getMessage()
            )
        );
    }

    /**
     * @param string $orderId
     * @param string $message
     * @param int $logLevel
     */
    public function logInfoForOrder(string $orderId, string $message, int $logLevel = self::INFO): void
    {
        $this->addRecord(
            $logLevel,
            sprintf(
                '(Order ID: %1$s): %2$s',
                $orderId,
                $message
            )
        );
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
        $this->debug('(Order ID: ' . $orderId . ') User redirected to the following page: ' . $paymentUrl);
    }

    /**
     * @param $orderId
     */
    public function logPaymentSuccessInfo($orderId): void
    {
        $this->debug('(Order ID: ' . $orderId . ') User redirected to the success page.');
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

    /**
     * @param string $orderId
     * @param InvalidArgumentException $invalidArgumentException
     */
    public function logInvalidIpAddress(string $orderId, InvalidArgumentException $invalidArgumentException): void
    {
        $this->error('(Order ID: ' . $orderId . ') ' . $invalidArgumentException->getMessage());
    }

    /**
     * @param string $orderId
     */
    public function logMissingPaymentToken(string $orderId): void
    {
        $this->error('(Order ID: ' . $orderId . ')
        Payment token not found when trying to create recurring transaction');
    }

    /**
     * @param string $orderId
     * @param Exception $exception
     */
    public function logOrderRequestBuilderException(string $orderId, Exception $exception): void
    {
        $this->error('(Order ID: ' . $orderId . ') Failed to create Order Request: '
                     . $exception->getMessage());
    }

    /**
     * @param string $orderId
     * @param ClientExceptionInterface $clientException
     */
    public function logClientException(string $orderId, ClientExceptionInterface $clientException): void
    {
        $this->error('(Order ID: ' . $orderId . ') Client exception when trying to place transaction: '
                     . $clientException->getMessage());
    }

    /**
     * @param string $path
     * @param Exception $exception
     */
    public function logMissingVaultIcon(string $path, Exception $exception): void
    {
        $this->error('Icon with path: ' . $path . ' can not be loaded. '
                     . $exception->getMessage());
    }

    /**
     * @param \InvalidArgumentException $invalidArgumentException
     */
    public function logJsonHandlerException(\InvalidArgumentException $invalidArgumentException): void
    {
        $this->error('Could not convert Json data: ' . $invalidArgumentException->getMessage());
    }

    /**
     * @param FileSystemException $fileSystemException
     */
    public function logFileSystemException(FileSystemException $fileSystemException): void
    {
        $this->error(sprintf(
            'FileSystemException occured. message: %1$d, code: %2$d, line: %3$d, file: %4$d)',
            $fileSystemException->getMessage(),
            $fileSystemException->getCode(),
            $fileSystemException->getLine(),
            $fileSystemException->getFile()
        ));
    }

    /**
     * @param Exception $exception
     */
    public function logPaymentRequestGetCustomerDataException(Exception $exception): void
    {
        $this->debug(
            sprintf(
                '(Get Payment Request API data error): %1$s (code: %2$d, line: %3$d, file: %4$s)',
                $exception->getMessage(),
                $exception->getCode(),
                $exception->getLine(),
                $exception->getFile()
            )
        );
    }
}
