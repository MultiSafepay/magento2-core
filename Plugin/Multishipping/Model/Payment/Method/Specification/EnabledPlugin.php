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

namespace MultiSafepay\ConnectCore\Plugin\Multishipping\Model\Payment\Method\Specification;

use Magento\Multishipping\Model\Payment\Method\Specification\Enabled;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\GenericGatewayConfigProvider;

class EnabledPlugin
{
    /**
     * @var GenericGatewayConfigProvider
     */
    private $genericGatewayConfigProvider;

    /**
     * EnabledPlugin constructor.
     *
     * @param GenericGatewayConfigProvider $genericGatewayConfigProvider
     */
    public function __construct(
        GenericGatewayConfigProvider $genericGatewayConfigProvider
    ) {
        $this->genericGatewayConfigProvider = $genericGatewayConfigProvider;
    }

    /**
     * @param Enabled $subject
     * @param string $paymentMethod
     * @return string[]
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeIsSatisfiedBy(Enabled $subject, string $paymentMethod): array
    {
        if ($this->genericGatewayConfigProvider->isMultisafepayGenericMethod($paymentMethod)) {
            $paymentMethod = GenericGatewayConfigProvider::CODE;
        }

        return [$paymentMethod];
    }
}
