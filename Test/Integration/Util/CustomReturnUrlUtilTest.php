<?php declare(strict_types=1);

namespace MultiSafepay\Test\Integration\Util;

use Magento\Framework\Exception\LocalizedException;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Util\CustomReturnUrlUtil;

/**
 * Class CustomReturnUrlUtilTest
 *
 * @package MultiSafepay\Test\Integration\Util
 *
 * phpcs:ignoreFile
 */
class CustomReturnUrlUtilTest extends AbstractTestCase
{
    /**
     * @magentoDataFixture   Magento/Sales/_files/order_with_shipping_and_invoice.php
     * @magentoConfigFixture default_store multisafepay/advanced/use_custom_return_url 1
     * @magentoConfigFixture default_store multisafepay/advanced/custom_cancel_return_url https://test.com/cart?secureToken={{secure_token}}&paymentCode={{payment.code}}
     * @magentoConfigFixture default_store multisafepay/advanced/custom_success_return_url https://test.com/checkout/success?incrementId={{order.increment_id}}&transactionId={{payment.transaction_id}}&secureToken={{secure_token}}
     * @magentoConfigFixture default_store multisafepay/general/mode 1
     * @magentoConfigFixture default_store multisafepay/general/live_api_key ZnjRoWF5O64zP43sa8pL
     * @dataProvider         customReturnUrlDataProvider
     *
     * @param string $type
     * @param string $expected
     * @param array $transactionData
     * @throws LocalizedException
     */
    public function testGetCustomReturnUrlByType(string $type, string $expected, array $transactionData = [])
    {
        $order = $this->getOrder();
        $order->setQuoteId($this->getQuote('tableRate')->getId());
        $customReturnUrlUtil = $this->getObjectManager()->create(CustomReturnUrlUtil::class);

        self::assertEquals(
            $expected,
            (string)$customReturnUrlUtil->getCustomReturnUrlByType($order, $transactionData, $type)
        );
    }

    /**
     * @return array
     */
    public function customReturnUrlDataProvider(): array
    {
        return [
            [
                CustomReturnUrlUtil::CANCEL_URL_TYPE_NAME,
                'https://test.com/cart?secureToken=asdas213123hg1li2g3lgoygcoyzgcogyoygcyoygoud&paymentCode=checkmo',
                'transactionData' => [
                    'transactionid' => '1000100100',
                    'secureToken' => 'asdas213123hg1li2g3lgoygcoyzgcogyoygcyoygoud',
                ],
            ],
            [
                CustomReturnUrlUtil::SUCCESS_URL_TYPE_NAME,
                'https://test.com/checkout/success?incrementId=100000001&transactionId=1000100100&secureToken='
                . '7fd5536fc175b8e39bdc800d664ecc341b206eef32c595ba96d462bbbaa3e919',
                'transactionData' => [
                    'transactionid' => '1000100100',
                ],
            ],
        ];
    }
}
