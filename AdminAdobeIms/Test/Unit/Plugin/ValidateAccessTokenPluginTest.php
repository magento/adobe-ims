<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminAdobeIms\Test\Unit\Plugin;

use Magento\AdminAdobeIms\Logger\AdminAdobeImsLogger;
use Magento\AdminAdobeIms\Model\Auth;
use Magento\AdminAdobeIms\Plugin\ValidateAccessTokenPlugin;
use Magento\AdobeImsApi\Api\IsTokenValidInterface;
use Magento\Backend\Model\Auth\Session;
use PHPUnit\Framework\TestCase;

class ValidateAccessTokenPluginTest extends TestCase
{
    /**
     * @var ValidateAccessTokenPlugin
     */
    private $plugin;

    /**
     * @var IsTokenValidInterface
     */
    private $isTokenValid;

    /**
     * @var AdminAdobeImsLogger
     */
    private $logger;

    /**
     * @var Session
     */
    protected $adminSession;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->isTokenValid = $this->createMock(IsTokenValidInterface::class);
        $this->logger = $this->createMock(AdminAdobeImsLogger::class);
        $this->adminSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAdobeAccessToken'])
            ->getMock();

        $this->plugin = new ValidateAccessTokenPlugin(
            $this->isTokenValid,
            $this->logger
        );
    }

    /**
     * Test plugin session logout when access token is expired
     *
     * @return void
     * @param array $responseData
     * @dataProvider responseDataProvider
     */
    public function testPluginSessionLogoutWhenAccessTokenIsExpired($responseData): void
    {
        $subject = $this->createMock(Auth::class);

        $this->adminSession->expects($this->any())
            ->method('getAdobeAccessToken')
            ->willReturn('test');

        $subject->method('getAuthStorage')
            ->willReturn($this->adminSession);

        $this->isTokenValid
            ->expects($this->once())
            ->method('validateToken')
            ->willReturn($responseData);

        $this->assertEquals($responseData, $this->plugin->afterIsLoggedIn($subject, true));
    }

    /**
     * @return array
     */
    public function responseDataProvider(): array
    {
        return [
            [true],
            [false]
        ];
    }
}
