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

namespace MultiSafepay\ConnectCore\Util;

use InvalidArgumentException;
use Magento\Framework\Serialize\Serializer\Json;
use MultiSafepay\ConnectCore\Logger\Logger;

class JsonHandler
{
    /**
     * @var Json
     */
    private $serializer;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * JsonHandler constructor.
     *
     * @param Json   $serializer
     * @param Logger $logger
     */
    public function __construct(
        Json $serializer,
        Logger $logger
    ) {
        $this->serializer = $serializer;
        $this->logger     = $logger;
    }

    /**
     * @param $json
     *
     * @return array
     */
    public function readJSON($json): array
    {
        try {
            $jsonDetails = (array)$this->serializer->unserialize($json);
        } catch (InvalidArgumentException $invalidArgumentException) {
            $this->logger->logJsonHandlerException($invalidArgumentException);
            $jsonDetails = [];
        }

        return $jsonDetails;
    }

    /**
     * Convert array into JSON
     *
     * @param array $details
     *
     * @return string
     */
    public function convertToJSON(array $details): string
    {
        try {
            $json = $this->serializer->serialize($details);
        } catch (InvalidArgumentException $invalidArgumentException) {
            $this->logger->logJsonHandlerException($invalidArgumentException);
            $json = '{}';
        }

        return $json;
    }
}
