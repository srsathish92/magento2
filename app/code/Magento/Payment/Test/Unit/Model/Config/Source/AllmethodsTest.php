<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Payment\Test\Unit\Model\Config\Source;

use Magento\Payment\Helper\Data;
use Magento\Payment\Model\Config\Source\Allmethods;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AllmethodsTest extends TestCase
{
    /**
     * Payment data
     *
     * @var Data|MockObject
     */
    protected $_paymentDataMock;

    /**
     * @var Allmethods
     */
    protected $_model;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->_paymentDataMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getPaymentMethodList'])
            ->getMock();

        $this->_model = new Allmethods($this->_paymentDataMock);
    }

    /**
     * Test toOptionArray()
     *
     * @return void
     */
    public function testToOptionArray(): void
    {
        $expectedArray = [
            'payment_code' => [
                'value' => 'payment_code',
                'label' => 'Payment Label'
            ]
        ];
        $this->_paymentDataMock->expects($this->once())
            ->method('getPaymentMethodList')
            ->with(true, true, false)
            ->willReturn($expectedArray);
        $this->assertEquals($expectedArray, $this->_model->toOptionArray());
    }
}
