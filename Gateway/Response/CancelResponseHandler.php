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

namespace MultiSafepay\ConnectCore\Gateway\Response;

use Magento\Payment\Gateway\Response\HandlerInterface;
use MultiSafepay\ConnectCore\Logger\Logger;

class CancelResponseHandler implements HandlerInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * CancelResponseHandler constructor.
     *
     * @param Logger $logger
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param array $handlingSubject
     * @param array|null $response
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function handle(array $handlingSubject, ?array $response): void
    {
        if ($response && isset($response['success'])) {
            $this->logger->logInfoForOrder(
                $response['order_id'] ?: '',
                'Reservation for MultiSafepay order was canceled.'
            );
        }
    }
}
