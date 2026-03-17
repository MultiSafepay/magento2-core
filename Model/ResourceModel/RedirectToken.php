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

namespace MultiSafepay\ConnectCore\Model\ResourceModel;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class RedirectToken extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('multisafepay_redirect_token', 'token_id');
    }

    /**
     * Return order_increment_id for a unique token.
     * Returns null when token is not found.
     *
     * @param string $token
     *
     * @return string|null
     * @throws LocalizedException
     */
    public function getOrderIncrementIdByToken(string $token): ?string
    {
        $connection = $this->getConnection();

        $select = $connection->select()
            ->from($this->getMainTable(), ['order_increment_id'])
            ->where('token = ?', $token)
            ->where('expired = ?', 0)
            ->limit(1);

        $value = $connection->fetchOne($select);

        if (!$value) {
            return null;
        }

        return $value;
    }

    /**
     * Expire active tokens for a given order increment ID.
     * This is used to prevent reuse of the same token after an order has been placed.
     *
     * @param string $orderIncrementId
     * @return void
     * @throws LocalizedException
     */
    public function expireActiveByOrderIncrementId(string $orderIncrementId): void
    {
        $this->getConnection()->update(
            $this->getMainTable(),
            ['expired' => 1],
            ['order_increment_id = ?' => $orderIncrementId, 'expired = ?' => 0]
        );
    }

    /**
     * Delete redirect tokens that are older than a specified number of days.
     *
     * @param int $days
     * @return int
     * @throws LocalizedException
     */
    public function deleteOlderThanDays(int $days): int
    {
        $days = max(1, $days);

        $connection = $this->getConnection();
        $table = $this->getMainTable();
        $where = sprintf('created_at < (UTC_TIMESTAMP() - INTERVAL %d DAY)', $days);

        return $connection->delete($table, $where);
    }
}
