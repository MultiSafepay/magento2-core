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
 * Copyright © 2020 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 *
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder;

use Exception;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\Description;
use MultiSafepay\ConnectCore\Config\Config;

class DescriptionBuilder
{

    /**
     * @var Description
     */
    private $description;

    /**
     * @var Config
     */
    private $config;

    /**
     * DescriptionBuilder constructor.
     *
     * @param Config $config
     * @param Description $description
     */
    public function __construct(
        Config $config,
        Description $description
    ) {
        $this->description = $description;
        $this->config = $config;
    }

    /**
     * @param $orderId
     * @return Description
     * @throws Exception
     */
    public function build($orderId): Description
    {
        $customDescription = (string)$this->config->getValue('transaction_custom_description');

        if (empty($customDescription)) {
            return $this->description->addDescription('Payment for order #' . $orderId);
        }

        $filteredDescription = str_replace('{{order.increment_id}}', $orderId, $customDescription);

        return $this->description->addDescription($filteredDescription);
    }
}
