<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminAdobeIms\Test\Unit\Plugin;

use Magento\AdminAdobeIms\Plugin\UserSavePlugin;
use Magento\AdminAdobeIms\Service\ImsConfig;
use Magento\User\Model\User;
use Magento\User\Model\UserFactory;
use PHPUnit\Framework\TestCase;

/**
 * Test class for UserSavePlugin
 */
class UserSavePluginTest extends TestCase
{
    /**
     * @var \Magento\User\Model\ResourceModel\User|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private mixed $userResourceMock;

    /**
     * @var UserFactory|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private mixed $userFactoryMock;

    /**
     * @var User|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private mixed $userMock;

    /**
     * @var ImsConfig|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private mixed $adminImsConfigMock;

    /**
     * @var UserSavePlugin
     */
    private UserSavePlugin $plugin;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->adminImsConfigMock = $this->createMock(ImsConfig::class);
        $this->userFactoryMock = $this->createPartialMock(UserFactory::class, ['create']);
        $this->userMock = $this->createMock(User::class);
        $this->userResourceMock = $this->getMockBuilder(\Magento\User\Model\ResourceModel\User::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->plugin = new UserSavePlugin(
            $this->adminImsConfigMock,
            $this->userFactoryMock,
            $this->userResourceMock
        );
    }

    /**
     * Update readonly fields data to original state for ims user test.
     * @return void
     */
    public function testImsUserReadonlyFieldDataPersists(): void
    {
        $this->adminImsConfigMock
            ->expects($this->once())
            ->method('enabled')
            ->willReturn(true);
        $savedUser = $this->createMock(User::class);
        $this->userMock->expects($this->any())->method('getId')
            ->willReturn('1');
        $this->userMock->method('hasDataChanges')
            ->willReturn(true);
        $savedUser->method('getUserName')
            ->willReturn('user1');
        $savedUser->expects($this->once())->method('getEmail')
            ->willReturn('user@test.com');
        $savedUser->expects($this->once())->method('getFirstName')
            ->willReturn('firstname');
        $savedUser->expects($this->once())->method('getLastName')
            ->willReturn('lastname');
        $savedUser->expects($this->once())->method('getInterfaceLocale')
            ->willReturn('en_US');
        $this->userResourceMock->expects($this->once())->method('load')
            ->with($savedUser, '1')
            ->willReturnSelf();
        $this->userFactoryMock->expects($this->once())->method('create')
            ->willReturn($savedUser);

        $this->plugin->beforeBeforeSave($this->userMock);
    }
}
