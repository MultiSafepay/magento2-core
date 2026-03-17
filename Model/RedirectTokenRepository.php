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

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use MultiSafepay\ConnectCore\Api\Data\RedirectTokenInterface;
use MultiSafepay\ConnectCore\Api\RedirectTokenRepositoryInterface;
use MultiSafepay\ConnectCore\Model\ResourceModel\RedirectToken as RedirectTokenResource;
use MultiSafepay\ConnectCore\Model\ResourceModel\RedirectToken\CollectionFactory;

class RedirectTokenRepository implements RedirectTokenRepositoryInterface
{
    /**
     * @var RedirectTokenResource
     */
    private $resource;

    /**
     * @var RedirectTokenFactory
     */
    private $factory;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @param RedirectTokenResource $resource
     * @param RedirectTokenFactory $factory
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        RedirectTokenResource $resource,
        RedirectTokenFactory $factory,
        CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->factory = $factory;
        $this->resource = $resource;
    }

    /**
     * Save a redirect token.
     *
     * @param RedirectTokenInterface $token
     *
     * @return RedirectTokenInterface
     * @throws CouldNotSaveException
     */
    public function save(RedirectTokenInterface $token): RedirectTokenInterface
    {
        try {
            /** @var RedirectToken $token */
            $this->resource->save($token);
        } catch (\Throwable $e) {
            throw new CouldNotSaveException(__('Could not save redirect token: ' . $e->getMessage()), $e);
        }

        return $token;
    }

    /**
     * Get a redirect token by its ID.
     *
     * @param int $tokenId
     *
     * @return RedirectTokenInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $tokenId): RedirectTokenInterface
    {
        $model = $this->factory->create();
        $this->resource->load($model, $tokenId);

        if (!$model->getId()) {
            throw new NoSuchEntityException(__('Redirect token with id "%1" does not exist.', $tokenId));
        }

        return $model;
    }

    /**
     * Get the most recent active redirect token for a given order ID.
     *
     * @param string $orderIncrementId
     *
     * @return RedirectTokenInterface
     * @throws NoSuchEntityException
     */
    public function getByOrderIncrementId(string $orderIncrementId): RedirectTokenInterface
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('order_increment_id', $orderIncrementId);
        $collection->addFieldToFilter('expired', 0);
        $collection->addOrder('token_id', 'DESC');
        $collection->setPageSize(1)->setCurPage(1);

        /** @var RedirectToken $item */
        $item = $collection->getFirstItem();

        if (!$item->getId()) {
            throw new NoSuchEntityException(
                __('No active redirect token found for order_increment_id "%1".', $orderIncrementId)
            );
        }

        return $item;
    }

    /**
     * Create a new redirect token for a given order ID and token string
     *
     * @param string $orderIncrementId
     * @param string $token
     *
     * @return RedirectTokenInterface
     * @throws CouldNotSaveException
     * @throws LocalizedException
     */
    public function create(string $orderIncrementId, string $token): RedirectTokenInterface
    {
        $this->resource->expireActiveByOrderIncrementId($orderIncrementId);

        $model = $this->factory->create();
        $model->setOrderIncrementId($orderIncrementId);
        $model->setToken($token);
        $model->setExpired(false);

        return $this->save($model);
    }

    /**
     * Get the most recent redirect token matching the given token string.
     *
     * @param string $token
     *
     * @return RedirectTokenInterface
     * @throws NoSuchEntityException
     */
    public function getByToken(string $token): RedirectTokenInterface
    {
        $collection = $this->collectionFactory->create();

        $collection->addFieldToFilter('token', $token);
        $collection->addOrder('token_id', 'DESC');
        $collection->setPageSize(1)->setCurPage(1);

        /** @var RedirectToken $item */
        $item = $collection->getFirstItem();

        if (!$item->getId()) {
            throw new NoSuchEntityException(__('Redirect token "%1" does not exist.', $token));
        }

        return $item;
    }

    /**
     * Get the order ID associated with a given redirect token string.
     * If the token does not exist, an exception will be thrown.
     *
     * @param string $token
     *
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getOrderIncrementIdByToken(string $token): string
    {
        $orderIncrementId = $this->resource->getOrderIncrementIdByToken($token);

        if ($orderIncrementId === null) {
            throw new NoSuchEntityException(__('Redirect token "%1" does not exist.', $token));
        }

        return (string)$orderIncrementId;
    }

    /**
     * Delete redirect tokens that are older than a specified number of days.
     *
     * @param int $days
     * @return int Number of rows deleted
     * @throws LocalizedException
     */
    public function deleteOlderThanDays(int $days): int
    {
        return $this->resource->deleteOlderThanDays($days);
    }
}
