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

namespace MultiSafepay\ConnectCore\Test\Integration\Cron;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use MultiSafepay\ConnectCore\Cron\RedirectTokenCleanup;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use Random\RandomException;
use Zend_Db_Expr;

/**
 * @magentoDbIsolation enabled
 */
class RedirectTokenCleanupTest extends AbstractTestCase
{
    /**
     * Test that the RedirectTokenCleanup cron job deletes tokens older than 7 days and keeps newer tokens.
     *
     * @return void
     * @throws RandomException
     */
    public function testExecuteDeletesTokensOlderThanSevenDays(): void
    {
        $resource = $this->getObjectManager()->get(ResourceConnection::class);
        $connection = $resource->getConnection();
        $table = $resource->getTableName('multisafepay_redirect_token');

        $oldToken = $this->insertTokenRow($connection, $table, '100000901');
        $newToken = $this->insertTokenRow($connection, $table, '100000902');

        $connection->update(
            $table,
            ['created_at' => new Zend_Db_Expr('UTC_TIMESTAMP() - INTERVAL 8 DAY')],
            ['token = ?' => $oldToken]
        );

        $connection->update(
            $table,
            ['created_at' => new Zend_Db_Expr('UTC_TIMESTAMP() - INTERVAL 1 DAY')],
            ['token = ?' => $newToken]
        );

        /** @var RedirectTokenCleanup $cron */
        $cron = $this->getObjectManager()->get(RedirectTokenCleanup::class);
        $cron->execute();

        $oldExists = (int)$connection->fetchOne(
            $connection->select()->from($table, ['cnt' => 'COUNT(*)'])->where('token = ?', $oldToken)
        );
        $newExists = (int)$connection->fetchOne(
            $connection->select()->from($table, ['cnt' => 'COUNT(*)'])->where('token = ?', $newToken)
        );

        self::assertSame(0, $oldExists, 'Old token should have been deleted by cron');
        self::assertSame(1, $newExists, 'Recent token should not be deleted by cron');
    }

    /**
     * Insert a token row for testing purposes.
     *
     * @param AdapterInterface $connection
     * @param string $table
     * @param string $orderIncrementId
     * @return string
     * @throws RandomException
     */
    private function insertTokenRow(AdapterInterface $connection, string $table, string $orderIncrementId): string
    {
        $token = substr('it_' . bin2hex(random_bytes(16)), 0, 32);

        $connection->insert($table, [
            'order_increment_id' => $orderIncrementId,
            'token' => $token,
            'expired' => 0
        ]);

        return $token;
    }
}
