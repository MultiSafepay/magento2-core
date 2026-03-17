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

namespace MultiSafepay\ConnectCore\Api\Data;

interface RedirectTokenInterface
{
    public const TOKEN_ID  = 'token_id';
    public const ORDER_INCREMENT_ID  = 'order_increment_id';
    public const TOKEN     = 'token';
    public const CREATED_AT = 'created_at';
    public const EXPIRED   = 'expired';

    /**
     * Get the unique identifier of the redirect token.
     *
     * @return int|null
     */
    public function getTokenId(): ?int;

    /**
     * Get the order increment ID associated with the redirect token.
     *
     * @return string
     */
    public function getOrderIncrementId(): string;

    /**
     * Set the order increment ID associated with the redirect token.
     *
     * @param string $orderIncrementId
     *
     * @return self
     */
    public function setOrderIncrementId(string $orderIncrementId): self;

    /**
     * Get the redirect token value.
     *
     * @return string
     */
    public function getToken(): string;

    /**
     * Set the redirect token value.
     *
     * @param string $token
     *
     * @return self
     */
    public function setToken(string $token): self;

    /**
     * Get the timestamp when the redirect token was created.
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * Check if the redirect token has expired.
     *
     * @return bool
     */
    public function isExpired(): bool;

    /**
     * Set the expired status of the redirect token.
     *
     * @param bool $expired
     *
     * @return self
     */
    public function setExpired(bool $expired): self;
}
