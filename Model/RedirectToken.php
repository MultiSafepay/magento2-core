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

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use MultiSafepay\ConnectCore\Api\Data\RedirectTokenInterface;

class RedirectToken extends AbstractModel implements RedirectTokenInterface
{
    /**
     * @throws LocalizedException
     */
    protected function _construct(): void
    {
        $this->_init(ResourceModel\RedirectToken::class);
    }

    /**
     * Get the unique identifier of the redirect token.
     *
     * @return int|null
     */
    public function getTokenId(): ?int
    {
        $value = $this->getData(self::TOKEN_ID);

        return $value === null ? null : (int)$value;
    }

    /**
     * Get the order increment ID associated with the redirect token.
     *
     * @return string
     */
    public function getOrderIncrementId(): string
    {
        return (string)$this->getData(self::ORDER_INCREMENT_ID);
    }

    /**
     * Set the order increment ID associated with the redirect token.
     *
     * @param string $orderIncrementId
     *
     * @return RedirectTokenInterface
     */
    public function setOrderIncrementId(string $orderIncrementId): RedirectTokenInterface
    {
        return $this->setData(self::ORDER_INCREMENT_ID, $orderIncrementId);
    }

    /**
     * Get the redirect token value.
     *
     * @return string
     */
    public function getToken(): string
    {
        return (string)$this->getData(self::TOKEN);
    }

    /**
     * Set the redirect token value.
     *
     * @param string $token
     *
     * @return RedirectTokenInterface
     */
    public function setToken(string $token): RedirectTokenInterface
    {
        return $this->setData(self::TOKEN, $token);
    }

    /**
     * Get the timestamp when the redirect token was created.
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string
    {
        $value = $this->getData(self::CREATED_AT);

        return $value === null ? null : (string)$value;
    }

    /**
     * Check if the redirect token has expired.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return (bool)$this->getData(self::EXPIRED);
    }

    /**
     * Set the expired status of the redirect token.
     *
     * @param bool $expired
     *
     * @return RedirectTokenInterface
     */
    public function setExpired(bool $expired): RedirectTokenInterface
    {
        return $this->setData(self::EXPIRED, (int)$expired);
    }
}
