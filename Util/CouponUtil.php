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

namespace MultiSafepay\ConnectCore\Util;

use Exception;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\SalesRule\Api\CouponRepositoryInterface;
use Magento\SalesRule\Model\Coupon;
use Magento\SalesRule\Model\CouponFactory;
use Magento\SalesRule\Model\ResourceModel\Coupon\Usage;
use Magento\SalesRule\Model\ResourceModel\Rule\Customer as CustomerRuleResourceModel;
use Magento\SalesRule\Model\Rule\Customer;
use Magento\SalesRule\Model\Rule\CustomerFactory;
use MultiSafepay\ConnectCore\Logger\Logger;

class CouponUtil
{
    /**
     * @var CouponInterface
     */
    private $couponFactory;

    /**
     * @var CouponRepositoryInterface
     */
    private $couponRepository;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Usage
     */
    private $couponUsage;

    /**
     * @var CustomerRuleResourceModel
     */
    private $customerRuleResourceModel;

    /**
     * @var CustomerFactory
     */
    private $customerRuleFactory;

    /**
     * @param CouponFactory $couponFactory
     * @param CouponRepositoryInterface $couponRepository
     * @param CustomerRuleResourceModel $customerRuleResourceModel
     * @param CustomerFactory $customerRuleFactory
     * @param Logger $logger
     * @param Usage $couponUsage
     */
    public function __construct(
        CouponFactory $couponFactory,
        CouponRepositoryInterface $couponRepository,
        CustomerRuleResourceModel $customerRuleResourceModel,
        CustomerFactory $customerRuleFactory,
        Logger $logger,
        Usage $couponUsage
    ) {
        $this->couponFactory = $couponFactory;
        $this->couponRepository = $couponRepository;
        $this->customerRuleResourceModel = $customerRuleResourceModel;
        $this->customerRuleFactory = $customerRuleFactory;
        $this->logger = $logger;
        $this->couponUsage = $couponUsage;
    }

    /**
     * Restore an earlier used coupon
     *
     * @param OrderInterface $order
     * @return void
     */
    public function restoreCoupon(OrderInterface $order)
    {
        if ($order->canCancel() && $order->getCouponCode()) {
            try {
                /** @var Coupon $coupon */
                $coupon = $this->couponFactory->create();
                $coupon->loadByCode($order->getCouponCode());
                $coupon->setTimesUsed($coupon->getTimesUsed() - 1);
                $this->couponRepository->save($coupon);

                if ($order->getCustomerId()) {
                    $this->couponUsage->updateCustomerCouponTimesUsed(
                        $order->getCustomerId(),
                        $coupon->getCouponId(),
                        false
                    );

                    /** @var Customer $customerRule */
                    $customerRule = $this->customerRuleFactory->create()->loadByCustomerRule(
                        $order->getCustomerId(),
                        $coupon->getRuleId()
                    );

                    $customerRule->setTimesUsed($customerRule->getTimesUsed() - 1);
                    $this->customerRuleResourceModel->save($customerRule);
                }
            } catch (InputException | LocalizedException | NoSuchEntityException | Exception $exception) {
                $this->logger->logExceptionForOrder(
                    $order->getIncrementId(),
                    $exception
                );
            }
        }
    }
}
