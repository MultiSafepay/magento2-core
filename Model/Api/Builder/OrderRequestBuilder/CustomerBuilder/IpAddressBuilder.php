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

namespace MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\CustomerBuilder;

use MultiSafepay\Api\Transactions\OrderRequest\Arguments\CustomerDetails;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Util\IpAddressUtil;
use MultiSafepay\Exception\InvalidArgumentException;
use MultiSafepay\ValueObject\IpAddress;

class IpAddressBuilder
{
    /**
     * @var IpAddressUtil
     */
    private $ipAddressUtil;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * IpAddressBuilder constructor.
     *
     * @param IpAddressUtil $ipAddressUtil
     * @param Logger $logger
     */
    public function __construct(
        IpAddressUtil $ipAddressUtil,
        Logger $logger
    ) {
        $this->ipAddressUtil = $ipAddressUtil;
        $this->logger = $logger;
    }

    /**
     * @param CustomerDetails $customerDetails
     * @param string $ipAddress
     * @param string $orderId
     */
    public function build(CustomerDetails $customerDetails, string $ipAddress, string $orderId): void
    {
        $filteredIp = $this->ipAddressUtil->validateIpAddress($ipAddress);
        try {
            $customerDetails->addIpAddress(new IpAddress($filteredIp));
        } catch (InvalidArgumentException $invalidArgumentException) {
            $this->logger->logInvalidIpAddress($orderId, $invalidArgumentException);
        }
    }

    /**
     * @param CustomerDetails $customerDetails
     * @param string $ipAddress
     * @param string $orderId
     */
    public function buildForwardedIp(CustomerDetails $customerDetails, string $ipAddress, string $orderId): void
    {
        $filteredIp = $this->ipAddressUtil->validateIpAddress($ipAddress);
        try {
            $customerDetails->addForwardedIp(new IpAddress($filteredIp));
        } catch (InvalidArgumentException $invalidArgumentException) {
            $this->logger->logInvalidIpAddress($orderId, $invalidArgumentException);
        }
    }
}
