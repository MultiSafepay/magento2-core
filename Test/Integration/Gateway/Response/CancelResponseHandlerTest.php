<?php

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Test\Integration\Gateway\Response;

use Exception;
use MultiSafepay\ConnectCore\Gateway\Response\CancelResponseHandler;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Test\Integration\Gateway\AbstractGatewayTestCase;

class CancelResponseHandlerTest extends AbstractGatewayTestCase
{
    private $logger;
    private $cancelResponseHandler;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);
        $this->cancelResponseHandler = new CancelResponseHandler($this->logger);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testHandle(): void
    {
        $response = ['order_id' => '12345'];
        $this->logger->expects($this->once())
            ->method('logInfoForOrder')
            ->with($this->equalTo('12345'), $this->equalTo('Order canceled by CancelResponseHandler'));

        $this->cancelResponseHandler->handle([], $response);
    }
}
