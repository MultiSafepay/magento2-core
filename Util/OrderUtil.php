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

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class OrderUtil
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * OrderUtil constructor.
     *
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @param string $incrementId
     * @return OrderInterface
     * @throws NoSuchEntityException
     */
    public function getOrderByIncrementId(string $incrementId): OrderInterface
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter(
            'main_table.' . OrderInterface::INCREMENT_ID,
            $incrementId
        )->create();

        if (!($orderItems = $this->orderRepository->getList($searchCriteria)->getItems())) {
            throw new NoSuchEntityException(__('Requested order doesn\'t exist'));
        }

        return reset($orderItems);
    }
}
