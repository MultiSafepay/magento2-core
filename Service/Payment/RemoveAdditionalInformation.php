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

namespace MultiSafepay\ConnectCore\Service\Payment;

use Exception;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Model\Order;
use MultiSafepay\ConnectCore\Logger\Logger;

class RemoveAdditionalInformation
{
    private const ADDITIONAL_INFO_KEYS = [
        'account_number',
        'account_holder_iban',
        'account_holder_bic',
    ];

    /**
     * @var OrderPaymentRepositoryInterface
     */
    private $orderPaymentRepository;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * PaymentService constructor.
     *
     * @param OrderPaymentRepositoryInterface $orderPaymentRepository
     * @param Logger $logger
     */
    public function __construct(
        OrderPaymentRepositoryInterface $orderPaymentRepository,
        Logger $logger
    ) {
        $this->orderPaymentRepository = $orderPaymentRepository;
        $this->logger = $logger;
    }

    /**
     * Remove sensitive additional information when needed
     *
     * @param Order $order
     * @throws Exception
     */
    public function execute(Order $order): void
    {
        if ($order->getPayment()) {
            $additionalInformation = $order->getPayment()->getAdditionalInformation();

            if (!$additionalInformation) {
                return;
            }

            if (!$this->needToRemoveAdditionalInformation($additionalInformation)) {
                return;
            }

            try {
                $orderPayment = $this->orderPaymentRepository->get($order->getPayment()->getEntityId());
                $orderPayment->setAdditionalInformation(
                    array_filter(
                        $orderPayment->getAdditionalInformation(),
                        static function ($value) {
                            return !in_array($value, self::ADDITIONAL_INFO_KEYS);
                        },
                        ARRAY_FILTER_USE_KEY
                    )
                );
                $this->orderPaymentRepository->save($orderPayment);
            } catch (InputException $inputException) {
                $this->logger->logInfoForOrder($order->getRealOrderId(), $inputException->getMessage());
            } catch (NoSuchEntityException $noSuchEntityException) {
                $this->logger->logInfoForOrder($order->getRealOrderId(), $noSuchEntityException->getMessage());
            }
        }
    }

    /**
     * Check if additional information needs to be removed
     *
     * @param array $additionalInformation
     * @return bool
     */
    private function needToRemoveAdditionalInformation(array $additionalInformation): bool
    {
        foreach (self::ADDITIONAL_INFO_KEYS as $key) {
            if (array_key_exists($key, $additionalInformation)) {
                return true;
            }
        }

        return false;
    }
}
