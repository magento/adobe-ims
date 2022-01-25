<?php
declare(strict_types=1);
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\AdobeIms\Test\Unit\Model;

use Magento\AdobeIms\Model\LogIn;
use Magento\AdobeIms\Model\OAuth\TokenResponse;
use Magento\AdobeImsApi\Api\Data\TokenResponseInterfaceFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ScopeResolverInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Stdlib\DateTime\Intl\DateFormatterFactory;
use Magento\Framework\Stdlib\DateTime\Timezone;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use PHPUnit\Framework\TestCase;
use Magento\AdobeImsApi\Api\Data\TokenResponseInterface;
use Magento\AdobeImsApi\Api\Data\UserProfileInterface;
use Magento\AdobeImsApi\Api\Data\UserProfileInterfaceFactory;
use Magento\AdobeImsApi\Api\UserProfileRepositoryInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime as StdlibDateTime;
use Magento\AdobeImsApi\Api\GetImageInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class LoginTest extends TestCase
{
    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->userProfileRepository = $this->createMock(UserProfileRepositoryInterface::class);
        $this->encryptor = $this->createMock(EncryptorInterface::class);
        $this->userProfileFactory = $this->createMock(UserProfileInterfaceFactory::class);
        $this->getUserImage = $this->createMock(GetImageInterface::class);
        $this->scopeType = 'default';
        $this->defaultTimezonePath = 'general/locale/timezone';
        $this->scopeResolver = $this->getMockBuilder(ScopeResolverInterface::class)
            ->getMock();
        $this->localeResolver = $this->getMockBuilder(ResolverInterface::class)
            ->getMock();
        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->getMock();

        $this->dateTime = $objectManager->getObject(
            DateTime::class,
            ['localeDate' => $this->getTimezone()]
        );

        $this->tokenResponseFactoryMock = $this->createMock(TokenResponseInterfaceFactory::class);
        $this->model = new LogIn(
            $this->userProfileRepository,
            $this->userProfileFactory,
            $this->getUserImage,
            $this->encryptor,
            $this->dateTime
        );
    }

    /**
     * Test Login.
     *
     * @param int $userId
     * @param array $responseData
     * @dataProvider responseDataProvider
     */
    public function testExecute(
        int $userId,
        array $responseData
    ): void {
        $userProfileMock = $this->createMock(UserProfileInterface::class);
        $this->userProfileRepository->expects($this->once())->method('save')
            ->with($userProfileMock)->willReturnSelf();
        $this->userProfileRepository->expects($this->exactly(1))
            ->method('getByUserId')
            ->willReturn($userProfileMock);
        $this->getUserImage->expects($this->once())
            ->method('execute')
            ->with($responseData['access_token'])
            ->willReturn('adobe_user_image');
        $this->encryptor->expects($this->any())
            ->method('encrypt')
            ->with($responseData['access_token'])
            ->willReturn($responseData['access_token']);
        $tokenResponse = $this->createMock(TokenResponseInterface::class);
        $tokenResponse->expects($this->any())
            ->method('getAccessToken')
            ->willReturn($responseData['access_token']);
        $tokenResponse->expects($this->once())
            ->method('getRefreshToken')
            ->willReturn($responseData['refresh_token']);
        $tokenResponse->expects($this->once())
            ->method('getName')
            ->willReturn($responseData['name']);
        $tokenResponse->expects($this->once())
            ->method('getEmail')
            ->willReturn($responseData['email']);
        $tokenResponse->expects($this->once())
            ->method('getExpiresIn')
            ->willReturn($responseData['expires_in']);
        $this->scopeConfig->expects($this->atLeastOnce())
            ->method('getValue')
            ->with($this->defaultTimezonePath, $this->scopeType, null)
            ->willReturn('America/Chicago');
        $this->localeResolver->expects($this->atLeastOnce())
            ->method('getLocale')
            ->willReturn('en_US');

        $this->model->execute($userId, $tokenResponse);
    }

    /**
     * Data provider for response.
     *
     * @return array
     */
    public function responseDataProvider(): array
    {
        return
            [
                [
                    'userId' => 10,
                    'tokenResponse' => [
                        'name' => 'Test User',
                        'email' => 'user@test.com',
                        'access_token' => 'kladjflakdjf3423rfzddsf',
                        'refresh_token' => 'kladjflakdjf3423rfzddsf',
                        'expires_in' => 1642259230998
                    ]
                ]
            ];
    }

    /**
     * @return Timezone
     */
    private function getTimezone()
    {
        return new Timezone(
            $this->scopeResolver,
            $this->localeResolver,
            $this->createMock(StdlibDateTime::class),
            $this->scopeConfig,
            $this->scopeType,
            $this->defaultTimezonePath,
            new DateFormatterFactory()
        );
    }
}
