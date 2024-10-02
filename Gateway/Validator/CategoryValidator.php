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

namespace MultiSafepay\ConnectCore\Gateway\Validator;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Config\Config;
use Magento\Quote\Api\Data\CartInterface;
use MultiSafepay\ConnectCore\Model\Ui\Giftcard\EdenredGiftcardConfigProvider;

class CategoryValidator
{
    public const AVAILABLE_GATEWAYS = [
        EdenredGiftcardConfigProvider::CODE,
    ];

    /**
     * @var EdenredGiftcardConfigProvider
     */
    private $edenredGiftcardConfigProvider;

    /**
     * CategoryValidator constructor.
     *
     * @param EdenredGiftcardConfigProvider $edenredGiftcardConfigProvider
     */
    public function __construct(
        EdenredGiftcardConfigProvider $edenredGiftcardConfigProvider
    ) {
        $this->edenredGiftcardConfigProvider = $edenredGiftcardConfigProvider;
    }

    /**
     * @param CartInterface $quote
     * @param Config $config
     * @param string $methodCode
     * @return bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws NoSuchEntityException
     */
    public function validate(CartInterface $quote, Config $config, string $methodCode): bool
    {
        if (!in_array($methodCode, self::AVAILABLE_GATEWAYS, true)) {
            return false;
        }

        if ($methodCode === EdenredGiftcardConfigProvider::CODE) {
            return count($this->edenredGiftcardConfigProvider->getAvailableCouponsByQuote($quote)) === 0;
        }

        return false;
    }
}
