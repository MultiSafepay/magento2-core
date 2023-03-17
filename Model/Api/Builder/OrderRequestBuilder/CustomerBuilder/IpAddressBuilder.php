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

namespace MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\CustomerBuilder;

use Magento\Sales\Api\Data\OrderInterface;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\CustomerDetails;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Util\IpAddressUtil;
use MultiSafepay\Exception\InvalidArgumentException;

class IpAddressBuilder
{
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var IpAddressUtil
     */
    private $ipAddressUtil;

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
     * Build the IP Address
     *
     * @param CustomerDetails $customerDetails
     * @param OrderInterface $order
     */
    public function build(CustomerDetails $customerDetails, OrderInterface $order): void
    {
        $orderId = $order->getIncrementId();

        if ($order->getRemoteIp() !== null) {
            $filteredIp = $this->ipAddressUtil->validateIpAddress($order->getRemoteIp());

            try {
                $customerDetails->addIpAddressAsString($filteredIp);
            } catch (InvalidArgumentException $invalidArgumentException) {
                $this->logger->logInvalidIpAddress($orderId, $invalidArgumentException);
            }
        }

        if ($order->getXForwardedFor() !== null) {
            $filteredForwardedIp = $this->ipAddressUtil->validateIpAddress($order->getXForwardedFor());

            try {
                $customerDetails->addForwardedIpAsString($filteredForwardedIp);
            } catch (InvalidArgumentException $invalidArgumentException) {
                $this->logger->logInvalidIpAddress($orderId, $invalidArgumentException);
            }
        }
    }
}
