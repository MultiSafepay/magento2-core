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

namespace MultiSafepay\ConnectCore\Test\Integration\Gateway\Request;

use Exception;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use MultiSafepay\ConnectCore\Gateway\Request\Builder\RecurringTransactionBuilder;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;

class RecurringTransactionBuilderTest extends AbstractTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     *
     * @throws LocalizedException
     */
    public function testBuildRecurringTransaction(): void
    {
        $order = $this->getOrder();

        self::assertEquals($this->prepareRecurringTransactionBuilder($order), ['order' => $order]);
    }

    /**
     * @param OrderInterface $order
     * @return array
     * @throws LocalizedException
     */
    private function prepareRecurringTransactionBuilder(OrderInterface $order): array
    {
        $buildSubject = [
            'payment' => $this->getPaymentDataObject('direct', $order),
            'stateObject' => new DataObject(),
        ];

        return $this->getRecurringTransactionBuilder()->build($buildSubject);
    }

    /**
     * @return RecurringTransactionBuilder
     */
    private function getRecurringTransactionBuilder(): RecurringTransactionBuilder
    {
        return $this->getObjectManager()->get(RecurringTransactionBuilder::class);
    }
}
