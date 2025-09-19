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

namespace MultiSafepay\ConnectCore\Test\Integration\Payment\Command;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\Command\GatewayCommand;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ConverterException;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;

class InitializeExampleTest extends AbstractTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @throws LocalizedException
     * @throws CommandException
     * @throws ClientException
     * @throws ConverterException
     */
    public function testCommandExecution()
    {
        $this->getAreaStateObject()->setAreaCode(Area::AREA_FRONTEND);

        /** @var GatewayCommand $command */
        $initializeCommand = $this->getObjectManager()->get('MultiSafepayInitializeCommand');
        $this->assertInstanceOf(GatewayCommand::class, $initializeCommand);

        /** @var PaymentDataObjectFactoryInterface $paymentDataObjectFactory */
        $paymentDataObjectFactory = $this->getObjectManager()->get(PaymentDataObjectFactoryInterface::class);
        $paymentDataObject = $paymentDataObjectFactory->create($this->getOrder()->getPayment());

        $initializeCommand->execute(
            [
                'amount' => 42,
                'payment' => $paymentDataObject,
                'stateObject' => new DataObject()
            ]
        );
    }

    /**
     * @return State
     */
    private function getAreaStateObject(): State
    {
        return $this->getObjectManager()->get(State::class);
    }
}
