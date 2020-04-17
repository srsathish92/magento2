<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SendFriend\Test\Unit\Block\Plugin\Catalog\Product;

use Magento\Catalog\Block\Product\View as ProductBlockView;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\SendFriend\Block\Plugin\Catalog\Product\View;
use Magento\SendFriend\Model\SendFriend;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ViewTest extends TestCase
{
    /** @var View */
    protected $view;

    /** @var ObjectManagerHelper */
    protected $objectManagerHelper;

    /** @var SendFriend|MockObject */
    protected $sendfriendModelMock;

    /** @var ProductBlockView|MockObject */
    protected $productViewMock;

    protected function setUp(): void
    {
        $this->sendfriendModelMock = $this->createPartialMock(
            SendFriend::class,
            ['__wakeup', 'canEmailToFriend']
        );
        $this->productViewMock = $this->createMock(ProductBlockView::class);

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->view = $this->objectManagerHelper->getObject(
            View::class,
            [
                'sendfriend' => $this->sendfriendModelMock
            ]
        );
    }

    /**
     * @dataProvider afterCanEmailToFriendDataSet
     * @param bool $result
     * @param string $callSendfriend
     */
    public function testAfterCanEmailToFriend($result, $callSendfriend): void
    {
        $this->sendfriendModelMock->expects($this->$callSendfriend())->method('canEmailToFriend')
            ->will($this->returnValue(true));

        $this->assertTrue($this->view->afterCanEmailToFriend($this->productViewMock, $result));
    }

    /**
     * Dataprovide for testAfterCanEmailToFriend
     *
     * @return array
     */
    public function afterCanEmailToFriendDataSet(): array
    {
        return [
            [true, 'never'],
            [false, 'once']
        ];
    }
}
