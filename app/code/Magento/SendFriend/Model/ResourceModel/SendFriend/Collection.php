<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SendFriend\Model\ResourceModel\SendFriend;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\SendFriend\Model\ResourceModel\SendFriend as SendFriendResourceModel;
use Magento\SendFriend\Model\SendFriend as SendFriendModel;

/**
 * SendFriend log resource collection
 *
 * @api
 * @since 100.0.2
 */
class Collection extends AbstractCollection
{
    /**
     * Init resource collection
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            SendFriendModel::class,
            SendFriendResourceModel::class
        );
    }
}
