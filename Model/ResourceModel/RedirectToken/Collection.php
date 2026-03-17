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

namespace MultiSafepay\ConnectCore\Model\ResourceModel\RedirectToken;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use MultiSafepay\ConnectCore\Model\RedirectToken as Model;
use MultiSafepay\ConnectCore\Model\ResourceModel\RedirectToken as ResourceModel;

class Collection extends AbstractCollection
{
    protected function _construct(): void
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}
