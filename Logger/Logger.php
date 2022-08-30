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
 * Copyright © 2022 MultiSafepay, Inc. All rights reserved.
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
     * @param string|null $orderId
     * @param ApiException $apiException
     * @return void
     */
    public function logPaymentLinkError(?string $orderId, ApiException $apiException): void
    {
        $this->error(
            sprintf(
                '(Order ID: %1$s) MultiSafepay error when trying to retrieve the payment link. Error: %2$s',
                $orderId ?? 'unknown',
                $apiException->getCode() . ' ' . $apiException->getMessage()
            )
        );

        $this->debug($apiException->getDetails());
    }

    /**
     * @param string|null $orderId
     * @param Exception $exception
     * @param int $logLevel
     */
    public function logExceptionForOrder(?string $orderId, Exception $exception, int $logLevel = self::DEBUG): void
    {
        $this->addRecord(
            $logLevel,
            sprintf(
                '(Order ID: %1$s) %2$s: %6$s (code: %3$d, line: %4$d, file: %5$s)',
                $orderId ?? 'unknown',
                static::getLevelName($logLevel),
                $exception->getCode(),
                $exception->getLine(),
                $exception->getFile(),
                $exception->getMessage()
            )
        );
    }

    /**
     * @param string|null $orderId
     * @param string $message
     * @param int $logLevel
     */
    public function logInfoForOrder(?string $orderId, string $message, int $logLevel = self::INFO): void
    {
        $this->addRecord(
            $logLevel,
            sprintf(
                '(Order ID: %1$s): %2$s',
                $orderId ?? 'unknown',
                $message
            )
        );
    }

    /**
     * @param string|null $orderId
     * @param ApiException $apiException
     * @return void
     */
    public function logGetRequestApiException(?string $orderId, ApiException $apiException): void
    {
        $this->error(
            sprintf(
                '(Order ID: %1$s) MultiSafepay error when trying to retrieve the transaction. Error: %2$s',
                $orderId ?? 'unknown',
                $apiException->getCode() . ' ' . $apiException->getMessage()
            )
        );

        $this->debug($apiException->getDetails());
    }

    /**
     * @param string|null $orderId
     * @param ApiException $apiException
     * @return void
     */
    public function logUpdateRequestApiException(?string $orderId, ApiException $apiException): void
    {
        $this->error(
            sprintf(
                '(Order ID: %1$s) MultiSafepay error when trying to update the transaction. Error: %2$s',
                $orderId ?? 'unknown',
                $apiException->getCode() . ' ' . $apiException->getMessage()
            )
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
     * @param string|null $orderId
     * @param $paymentUrl
     * @return void
     */
    public function logPaymentRedirectInfo(?string $orderId, $paymentUrl): void
    {
        $this->debug(
            '(Order ID: ' . ($orderId ?? 'unknown') . ') User redirected to the following page: ' . $paymentUrl
        );
    }

    /**
     * @param string|null $orderId
     */
    public function logPaymentSuccessInfo(?string $orderId): void
    {
        $this->debug('(Order ID: ' . ($orderId ?? 'unknown') . ') User redirected to the success page.');
    }

    /**
     * @param string|null $orderId
     */
    public function logMissingSecureToken(?string $orderId): void
    {
        $this->error(
            '(Order ID: ' . ($orderId ?? 'unknown') . ') secureToken missing from request parameters.'
        );
    }

    /**
     * @param string|null $orderId
     */
    public function logInvalidSecureToken(?string $orderId): void
    {
        $this->error(
            '(Order ID: ' . ($orderId ?? 'unknown') . ') Invalid secureToken provided in request parameters.'
        );
    }

    /**
     * @param string|null $orderId
     * @param InvalidArgumentException $invalidArgumentException
     */
    public function logInvalidIpAddress(?string $orderId, InvalidArgumentException $invalidArgumentException): void
    {
        $this->error('(Order ID: ' . ($orderId ?? 'unknown') . ') ' . $invalidArgumentException->getMessage());
    }

    /**
     * @param string|null $orderId
     */
    public function logMissingPaymentToken(?string $orderId): void
    {
        $this->error('(Order ID: ' . ($orderId ?? 'unknown') . ')
        Payment token not found when trying to create recurring transaction');
    }

    /**
     * @param string|null $orderId
     * @param Exception $exception
     */
    public function logOrderRequestBuilderException(?string $orderId, Exception $exception): void
    {
        $this->error('(Order ID: ' . ($orderId ?? 'unknown') . ') Failed to create Order Request: '
                     . $exception->getMessage());
    }

    /**
     * @param string|null $orderId
     * @param ClientExceptionInterface $clientException
     */
    public function logClientException(?string $orderId, ClientExceptionInterface $clientException): void
    {
        $this->error(
            sprintf(
                '(Order ID: %1$s): Client exception when trying to place transaction: %2$s',
                $orderId ?? 'unknown',
                $clientException->getMessage()
            )
        );
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
    public function logException(Exception $exception): void
    {
        $this->debug(
            sprintf(
                '(Something went wrong): %1$s (code: %2$d, line: %3$d, file: %4$s)',
                $exception->getMessage(),
                $exception->getCode(),
                $exception->getLine(),
                $exception->getFile()
            )
        );
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

    /**
     * @param Exception $exception
     */
    public function logApplePayGetMerchantSessionException(Exception $exception): void
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

    /**
     * Log the failed POST notification body and headers
     *
     * @param string $headers
     * @param string $body
     * @throws Exception
     */
    public function logFailedPOSTNotification(string $headers, string $body): void
    {
        $this->debug(
            sprintf(
                '(Failed POST notification) Headers: %1$s, Body: %2$s',
                $headers,
                $body
            )
        );
    }
}
