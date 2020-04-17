<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SendFriend\Test\Unit\Model;

use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SendFriend\Model\SendFriend;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\SendFriend\Model\ConfigInterface;

/**
 * Test SendFriend
 */
class SendFriendTest extends TestCase
{
    /**
     * @var SendFriend
     */
    protected $model;

    /**
     * @var MockObject|CookieManagerInterface
     */
    protected $cookieManagerMock;

    /**
     * @var ConfigInterface|MockObject
     */
    protected $sendFriendConfigMock;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->sendFriendConfigMock = $this->getMockBuilder(ConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cookieManagerMock = $this->createMock(CookieManagerInterface::class);

        $this->model = $objectManager->getObject(
            SendFriend::class,
            [
                'sendFriendConfig' => $this->sendFriendConfigMock,
                'cookieManager' => $this->cookieManagerMock,
            ]
        );
    }

    public function testGetSentCountWithCheckCookie(): void
    {
        $cookieName = 'testCookieName';
        $this->sendFriendConfigMock->expects($this->once())->method('getLimitBy')->with()->will(
            $this->returnValue(ConfigInterface::CHECK_COOKIE)
        );
        $this->sendFriendConfigMock->expects($this->once())->method('getCookieName')->with()->will(
            $this->returnValue($cookieName)
        );

        $this->cookieManagerMock->expects($this->once())->method('getCookie')->with($cookieName);
        $this->assertEquals(0, $this->model->getSentCount());
    }

    public function testSentCountByCookies(): void
    {
        $cookieName = 'testCookieName';
        $this->sendFriendConfigMock->expects($this->once())->method('getCookieName')->with()->will(
            $this->returnValue($cookieName)
        );

        $this->cookieManagerMock->expects($this->once())->method('getCookie')->with($cookieName);
        $this->cookieManagerMock->expects($this->once())->method('setSensitiveCookie');
        $sendFriendClass = new \ReflectionClass(SendFriend::class);
        $method = $sendFriendClass->getMethod('_sentCountByCookies');
        $method->setAccessible(true);
        $method->invokeArgs($this->model, [true]);
    }
}
