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

namespace MultiSafepay\ConnectCore\Api;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use MultiSafepay\ConnectCore\Api\Data\RedirectTokenInterface;

interface RedirectTokenRepositoryInterface
{
    /**
     * Create or update a redirect token. If the token does not exist, it will be created.
     * If it does exist, it will be updated with the new data.
     *
     * @throws CouldNotSaveException
     */
    public function save(RedirectTokenInterface $token): RedirectTokenInterface;

    /**
     * Get a token by its unique identifier.
     *
     * @throws NoSuchEntityException
     */
    public function getById(int $tokenId): RedirectTokenInterface;

    /**
     * Returns the "best" token for the order (latest non-expired).
     *
     * @throws NoSuchEntityException
     */
    public function getByOrderIncrementId(string $orderIncrementId): RedirectTokenInterface;

    /**
     * Create a new token for the order.
     *
     * @throws CouldNotSaveException
     */
    public function create(string $orderIncrementId, string $token): RedirectTokenInterface;

    /**
     * Load the newest matching row by the token string (not token_id).
     *
     * @throws NoSuchEntityException
     */
    public function getByToken(string $token): RedirectTokenInterface;

    /**
     * Convenience: return order_increment_id for a token string.
     *
     * @throws NoSuchEntityException
     */
    public function getOrderIncrementIdByToken(string $token): string;

    /**
     * Delete redirect tokens that are older than a specified number of days.
     */
    public function deleteOlderThanDays(int $days): int;
}
