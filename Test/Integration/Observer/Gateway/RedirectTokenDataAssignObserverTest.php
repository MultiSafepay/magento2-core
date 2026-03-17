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

namespace MultiSafepay\ConnectCore\Test\Integration\Observer\Gateway;

use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\PaymentInterface as QuotePaymentInterface;
use Magento\Quote\Model\Quote\Payment;
use MultiSafepay\ConnectCore\Observer\Gateway\RedirectTokenDataAssignObserver;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Util\RedirectTokenUtil;
use Random\RandomException;

/**
 * @magentoDbIsolation enabled
 */
class RedirectTokenDataAssignObserverTest extends AbstractTestCase
{
    /**
     * @var RedirectTokenDataAssignObserver
     */
    private $redirectDataAssignObserver;

    protected function setUp(): void
    {
        $this->redirectDataAssignObserver = $this->getObjectManager()->create(RedirectTokenDataAssignObserver::class);
    }

    /**
     * Test that when the payment method is not a MultiSafepay method,
     * the observer does not set any additional information on the payment.
     *
     * @throws LocalizedException
     * @throws RandomException
     */
    public function testDoNothingWhenPaymentMethodIsNotMultiSafepay(): void
    {
        $payment = $this->getObjectManager()->create(Payment::class);
        $payment->setMethod('checkmo');

        $token = bin2hex(random_bytes(16));
        $observer = $this->buildObserver($payment, [
            RedirectTokenDataAssignObserver::REDIRECT_TOKEN_PARAM_NAME => $token,
        ]);

        $this->redirectDataAssignObserver->execute($observer);

        self::assertNull($payment->getAdditionalInformation(RedirectTokenUtil::REDIRECT_TOKEN_KEY));
    }

    /**
     * Test that when the redirect token is missing from the additional data
     * the observer does not set any additional information on the payment.
     *
     * @throws LocalizedException
     */
    public function testDoNothingWhenTokenIsMissing(): void
    {
        $payment = $this->getObjectManager()->create(Payment::class);
        $payment->setMethod('multisafepay_ideal');

        $observer = $this->buildObserver($payment, []);

        $this->redirectDataAssignObserver->execute($observer);

        self::assertNull($payment->getAdditionalInformation(RedirectTokenUtil::REDIRECT_TOKEN_KEY));
    }

    /**
     * Test that when the redirect token is provided in the additional data,
     * it is stored in the payment's additional information under the correct key.
     *
     * @throws RandomException
     * @throws LocalizedException
     */
    public function testStoreTokenInPaymentAdditionalInformationWhenMethodIsMultiSafepay(): void
    {
        $payment = $this->getObjectManager()->create(Payment::class);
        $payment->setMethod('multisafepay_ideal');

        $token = bin2hex(random_bytes(16));

        $observer = $this->buildObserver($payment, [
            RedirectTokenDataAssignObserver::REDIRECT_TOKEN_PARAM_NAME => $token,
        ]);

        $this->redirectDataAssignObserver->execute($observer);

        self::assertSame(
            $token,
            $payment->getAdditionalInformation(RedirectTokenUtil::REDIRECT_TOKEN_KEY)
        );
    }

    /**
     * Test that the observer accepts payment methods that start with "multisafepay"
     *
     * @throws LocalizedException
     * @throws RandomException
     */
    public function testAcceptMethodsStartingWithMultisafepayWithoutUnderscore(): void
    {
        $payment = $this->getObjectManager()->create(Payment::class);
        $payment->setMethod('multisafepay');

        $token = bin2hex(random_bytes(16));

        $observer = $this->buildObserver($payment, [
            RedirectTokenDataAssignObserver::REDIRECT_TOKEN_PARAM_NAME => $token,
        ]);

        $this->redirectDataAssignObserver->execute($observer);

        self::assertSame(
            $token,
            $payment->getAdditionalInformation(RedirectTokenUtil::REDIRECT_TOKEN_KEY)
        );
    }

    /**
     * Builds an observer instance with the given payment and additional data.
     *
     * @param Payment $payment
     * @param array $additionalData
     * @return Observer
     */
    private function buildObserver(Payment $payment, array $additionalData): Observer
    {
        $inner = new DataObject();
        $inner->setData(QuotePaymentInterface::KEY_ADDITIONAL_DATA, $additionalData);

        $outer = new DataObject([
            'data' => $inner,
        ]);

        $outer->setData(QuotePaymentInterface::KEY_ADDITIONAL_DATA, $additionalData);

        $event = new Event([
            'data' => $outer,
            'payment_model' => $payment,
        ]);

        return new Observer(['event' => $event]);
    }
}
