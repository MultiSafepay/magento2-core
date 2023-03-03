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

namespace MultiSafepay\ConnectCore\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use MultiSafepay\ConnectCore\Api\MultisafepayOrderInterface;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Util\OrderUtil;
use MultiSafepay\ConnectCore\Util\PaymentMethodUtil;

class MultisafepayOrder implements MultisafepayOrderInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var SecureToken
     */
    private $secureToken;

    /**
     * @var OrderUtil
     */
    private $orderUtil;

    /**
     * @var PaymentMethodUtil
     */
    private $paymentMethodUtil;

    /**
     * MultisafepayOrder constructor.
     *
     * @param SecureToken $secureToken
     * @param OrderUtil $orderUtil
     * @param PaymentMethodUtil $paymentMethodUtil
     * @param Logger $logger
     */
    public function __construct(
        SecureToken $secureToken,
        OrderUtil $orderUtil,
        PaymentMethodUtil $paymentMethodUtil,
        Logger $logger
    ) {
        $this->secureToken = $secureToken;
        $this->orderUtil = $orderUtil;
        $this->paymentMethodUtil = $paymentMethodUtil;
        $this->logger = $logger;
    }

    /**
     * @param string $orderId
     * @param string $secureToken
     * @return OrderInterface|string
     */
    public function getOrder(string $orderId, string $secureToken)
    {
        if (!$this->secureToken->validate($orderId, $secureToken)) {
            return '';
        }

        try {
            $order = $this->orderUtil->getOrderByIncrementId($orderId);

            return $this->paymentMethodUtil->isMultisafepayOrder($order) ? $order : '';
        } catch (NoSuchEntityException $noSuchEntityException) {
            $this->logger->logException($noSuchEntityException);

            return 'Unable to load order';
        }
    }
}
