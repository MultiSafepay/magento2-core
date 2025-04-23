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

namespace MultiSafepay\ConnectCore\Logger;

use Exception;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Logger\Handler\Base;
use MultiSafepay\ConnectCore\Config\Config;

class Handler extends Base
{
    /**
     * File name
     * @var string
     */
    protected $fileName = '/var/log/multisafepay.log';

    /**
     * @var Config
     */
    private $config;

    /**
     * Handler constructor.
     *
     * @param Config $config
     * @param DriverInterface $filesystem
     * @param null $filePath
     * @param null $fileName
     * @throws Exception
     */
    public function __construct(
        Config $config,
        DriverInterface $filesystem,
        $filePath = null,
        $fileName = null
    ) {
        $this->config = $config;
        parent::__construct($filesystem, $filePath, $fileName);
    }

    /**
     * @param $record
     * @return bool
     */
    public function isHandling($record): bool
    {
        if ($this->config->isDebug()) {
            return true;
        }

        if ($record instanceof \Monolog\LogRecord) {
            return $record->toArray()['level'] >= \Monolog\Level::Warning;
        }

        return $record['level'] >= \Monolog\Logger::WARNING;
    }
}
