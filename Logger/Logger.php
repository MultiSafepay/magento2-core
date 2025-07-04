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
     * Log the error if it occurred when trying to load the payment component
     *
     * @param string $paymentMethod
     * @param string $gatewayCode
     * @param string $errorMessage
     * @param string $paymentComponentData
     */
    public function logPaymentComponentError(
        string $paymentMethod,
        string $gatewayCode,
        string $errorMessage,
        string $paymentComponentData
    ): void {
        $this->debug(
            sprintf(
                "An error occurred when trying to load the payment component: \n" .
                "(Payment method: %1\$s\n" .
                "Gateway code: %2\$s\n" .
                "Error message: %3\$s\n" .
                "Payment component data: %4\$s",
                $paymentMethod,
                $gatewayCode,
                $errorMessage,
                $paymentComponentData
            )
        );
    }

    /**
     * Log Apple Pay Merchant Session Exception
     *
     * @param Exception $exception
     * @throws Exception
     */
    public function logApplePayGetMerchantSessionException(Exception $exception): void
    {
        if (method_exists($exception, 'getContextValue')
            && $exception->getContextValue('raw_response_body') !== null) {
            $response = $exception->getContextValue('raw_response_body');
        }

        $this->debug(
            sprintf(
                '(Apple Pay Merchant Session error): %1$s (code: %2$d, line: %3$d, file: %4$s, response: %5$s)',
                $exception->getMessage(),
                $exception->getCode(),
                $exception->getLine(),
                $exception->getFile(),
                $response ?? ''
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
    public function logPOSTNotification(string $headers, string $body): void
    {
        $this->debug(
            sprintf(
                '(POST notification) Headers: %1$s, Body: %2$s',
                $headers,
                $body
            )
        );
    }

    /**
     * Log the refund request
     *
     * @param string|null $orderId
     * @param string $refundRequest
     */
    public function logRefundRequest(?string $orderId, string $refundRequest): void
    {
        $this->debug(
            sprintf(
                '(Order ID %1$s): Refund Request: %2$s',
                $orderId ?? 'unknown',
                $refundRequest
            )
        );
    }

    /**
     * Log an exception for the notification
     *
     * @param string $orderId
     * @param array $transaction
     * @param Exception $exception
     * @param int $logLevel
     * @return void
     * @throws Exception
     */
    public function logNotificationException(
        string $orderId,
        array $transaction,
        Exception $exception,
        int $logLevel = self::DEBUG
    ): void {
        $this->addRecord(
            $logLevel,
            sprintf(
                '(Order ID: %1$s, PSP ID: %8$s, Status: %7$s) %2$s: %6$s (code: %3$d, line: %4$d, file: %5$s)',
                $orderId,
                static::getLevelName($logLevel),
                $exception->getCode(),
                $exception->getLine(),
                $exception->getFile(),
                $exception->getMessage(),
                $transaction['status'] ?? 'unknown',
                $transaction['transaction_id'] ?? 'unknown'
            )
        );
    }

    /**
     * Log info for notification
     *
     * @param string|null $orderId
     * @param string $message
     * @param array $transaction
     * @param int $logLevel
     * @throws Exception
     */
    public function logInfoForNotification(
        ?string $orderId,
        string $message,
        array $transaction,
        int $logLevel = self::INFO
    ): void {
        $this->addRecord(
            $logLevel,
            sprintf(
                '(Order ID: %1$s, PSP ID: %3$s, Status: %4$s): %2$s',
                $orderId ?? 'unknown',
                $message,
                $transaction['transaction_id'] ?? 'unknown',
                $transaction['status'] ?? 'unknown'
            )
        );
    }

    /**
     * Log exceptions related to the retrieval and processing of the API Token
     *
     * @param Exception $exception
     */
    public function logExceptionForApiToken(Exception $exception): void
    {
        $this->debug(
            sprintf(
                '(Exception when trying to retrieve API token): %1$s (code: %2$d, line: %3$d, file: %4$s)',
                $exception->getMessage(),
                $exception->getCode(),
                $exception->getLine(),
                $exception->getFile()
            )
        );
    }
}
