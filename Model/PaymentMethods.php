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

use Magento\Framework\App\Area;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Model\MethodList;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Store\Model\App\Emulation;
use MultiSafepay\ConnectCore\Api\PaymentMethodsInterface;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Model\Ui\ConfigProviderPool;
use MultiSafepay\ConnectCore\Util\PaymentMethodUtil;

class PaymentMethods implements PaymentMethodsInterface
{
    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var ConfigProviderPool
     */
    private $configProviderPool;

    /**
     * @var PaymentMethodUtil
     */
    private $paymentMethodUtil;

    /**
     * @var MethodList
     */
    private $methodList;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Emulation
     */
    private $emulation;

    /**
     * PaymentMethods constructor.
     *
     * @param ConfigProviderPool $configProviderPool
     * @param CartRepositoryInterface $quoteRepository
     * @param PaymentMethodUtil $paymentMethodUtil
     * @param MethodList $methodList
     * @param Emulation $emulation
     * @param Logger $logger
     */
    public function __construct(
        ConfigProviderPool $configProviderPool,
        CartRepositoryInterface $quoteRepository,
        PaymentMethodUtil $paymentMethodUtil,
        MethodList $methodList,
        Emulation $emulation,
        Logger $logger
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->configProviderPool = $configProviderPool;
        $this->paymentMethodUtil = $paymentMethodUtil;
        $this->methodList = $methodList;
        $this->emulation = $emulation;
        $this->logger = $logger;
    }

    /**
     * @param int $cartId
     * @return array
     */
    public function getList(int $cartId): array
    {
        try {
            $quote = $this->quoteRepository->get($cartId);
            $storeId = $quote->getStoreId();
            $methods = $this->methodList->getAvailableMethods($quote);

            foreach ($methods as $key => $method) {
                $methodCode = $method->getCode();

                if (!$this->paymentMethodUtil->isMultisafepayPaymentByCode($methodCode)) {
                    continue;
                }

                if ($configProvider = $this->configProviderPool->getConfigProviderByCode($methodCode)) {
                    $this->emulation->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);
                    $config = $configProvider->getConfig();
                    $this->emulation->stopEnvironmentEmulation();

                    if (isset($config['payment'][$methodCode])) {
                        $paymentData = $config['payment'][$methodCode];
                        $paymentData['code'] = $methodCode;
                        $paymentData['title'] = $method->getTitle();
                        $methods[$key] = $paymentData;
                    }
                }
            }

            return $methods;
        } catch (NoSuchEntityException $noSuchEntityException) {
            $this->logger->logException($noSuchEntityException);
        }

        return [];
    }
}
