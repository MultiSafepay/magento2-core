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

namespace MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\SecondChance;

class SecondChanceBuilder implements OrderRequestBuilderInterface
{
    /**
     * @var State
     */
    private $state;

    /**
     * @var SecondChance
     */
    private $secondChance;

    /**
     * SecondChanceBuilder constructor.
     *
     * @param SecondChance $secondChance
     * @param State $state
     */
    public function __construct(
        SecondChance $secondChance,
        State $state
    ) {
        $this->state = $state;
        $this->secondChance = $secondChance;
    }

    /**
     * @param OrderInterface $order
     * @param OrderPaymentInterface $payment
     * @param OrderRequest $orderRequest
     * @throws LocalizedException
     */
    public function build(
        OrderInterface $order,
        OrderPaymentInterface $payment,
        OrderRequest $orderRequest
    ): void {
        if ($this->state->getAreaCode() === Area::AREA_ADMINHTML) {
            $orderRequest->addSecondChance($this->secondChance->addSendEmail(false));
        }
    }
}
