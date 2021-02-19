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

namespace MultiSafepay\ConnectCore\Gateway\Http\Client;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Model\Api\Initializer\OrderRequestInitializer;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\Exception\InvalidApiKeyException;
use Psr\Http\Client\ClientExceptionInterface;

class RecurringClient implements ClientInterface
{
    /**
     * @var OrderRequestInitializer
     */
    private $orderRequestInitializer;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * RecurringClient constructor.
     *
     * @param Logger $logger
     * @param OrderRequestInitializer $orderRequestInitializer
     */
    public function __construct(
        Logger $logger,
        OrderRequestInitializer $orderRequestInitializer
    ) {
        $this->logger = $logger;
        $this->orderRequestInitializer = $orderRequestInitializer;
    }

    /**
     * @param TransferInterface $transferObject
     * @return array|void
     * @throws LocalizedException
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $order = $transferObject->getBody()['order'];
        $orderId = $order->getId();

        $msg = __('Something went wrong with the order. Please try again later.');
        try {
            $transaction = $this->orderRequestInitializer->initialize($order);
        } catch (InvalidApiKeyException $invalidApiKeyException) {
            $this->logger->logInvalidApiKeyException($invalidApiKeyException);
            throw new LocalizedException($msg);
        } catch (ApiException $apiException) {
            $this->logger->logPaymentLinkError($orderId, $apiException);
            throw new LocalizedException($msg);
        } catch (ClientExceptionInterface $clientException) {
            $this->logger->logClientException($orderId, $clientException);
            throw new LocalizedException($msg);
        }

        return $transaction->getData();
    }
}
