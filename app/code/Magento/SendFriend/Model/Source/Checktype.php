<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SendFriend\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\SendFriend\Model\ConfigInterface;

/**
 * Send to a Friend Limit sending by Source
 */
class Checktype implements OptionSourceInterface
{
    /**
     * Retrieve Check Type Option array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => ConfigInterface::CHECK_IP, 'label' => __('IP Address')],
            ['value' => ConfigInterface::CHECK_COOKIE, 'label' => __('Cookie (unsafe)')]
        ];
    }
}
