<?php
/**
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is provided with Magento in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * Copyright © 2021 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 *
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Logger;

use Exception;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Logger\Handler\Base;
use MultiSafepay\ConnectCore\Config\Config;

class DebugHandler extends Base
{
    /**
     * File name
     * @var string
     */
    protected $fileName = '/var/log/multisafepay-debug.log';

    /**
     * @var int
     */
    protected $level = Logger::DEBUG;

    /**
     * @var Config
     */
    private $config;

    /**
     * DebugHandler constructor.
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
        parent::__construct($filesystem, $filePath, $fileName);
        $this->config = $config;
    }

    /**
     * @param array $record
     * @return bool
     */
    public function isHandling(array $record)
    {
        if ($this->config->isDebug()) {
            return true;
        }

        return false;
    }
}
