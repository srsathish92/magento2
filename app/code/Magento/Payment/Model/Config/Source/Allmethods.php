<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Payment\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Payment\Helper\Data;

class Allmethods implements OptionSourceInterface
{
    /**
     * Payment data
     *
     * @var Data
     */
    protected $_paymentData;

    /**
     * @param Data $paymentData
     */
    public function __construct(Data $paymentData)
    {
        $this->_paymentData = $paymentData;
    }

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return $this->_paymentData->getPaymentMethodList(true, true, false);
    }
}
