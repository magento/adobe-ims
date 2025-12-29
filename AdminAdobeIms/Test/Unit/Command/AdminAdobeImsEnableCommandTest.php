<?php
/**
 * Copyright 2022 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\AdminAdobeIms\Test\Unit\Command;

use Exception;
use Magento\AdminAdobeIms\Console\Command\AdminAdobeImsEnableCommand;
use Magento\AdminAdobeIms\Model\ImsConnection;
use Magento\AdminAdobeIms\Service\UpdateTokensService;
use Magento\AdminAdobeIms\Service\ImsCommandOptionService;
use Magento\AdminAdobeIms\Service\ImsConfig;
use Magento\Framework\App\Cache\Type\Config;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\TestFramework\Unit\Helper\MockCreationTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Helper\DebugFormatterHelper;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AdminAdobeImsEnableCommandTest extends TestCase
{
    use MockCreationTrait;

    /**
     * @var ImsConfig
     */
    private $adminImsConfigMock;

    /**
     * @var ImsConnection
     */
    private $adminImsConnectionMock;

    /**
     * @var ImsCommandOptionService
     */
    private $imsCommandOptionService;

    /**
     * @var TypeListInterface
     */
    private $typeListInterface;

    /**
     * @var UpdateTokensService
     */
    private $updateTokensService;

    /**
     * @var QuestionHelper
     */
    private $questionHelperMock;

    /**
     * @var AdminAdobeImsEnableCommand
     */
    private $enableCommand;

    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManagerHelper($this);

        $this->adminImsConfigMock = $this->createMock(ImsConfig::class);
        $this->adminImsConnectionMock = $this->createMock(ImsConnection::class);
        $this->imsCommandOptionService = $this->createMock(ImsCommandOptionService::class);
        $this->typeListInterface = $this->createMock(TypeListInterface::class);
        $this->updateTokensService = $this->createMock(UpdateTokensService::class);

        $this->questionHelperMock = $this->getMockBuilder(QuestionHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->enableCommand = $objectManagerHelper->getObject(
            AdminAdobeImsEnableCommand::class,
            [
                'adminImsConfig' => $this->adminImsConfigMock,
                'adminImsConnection' => $this->adminImsConnectionMock,
                'imsCommandOptionService' => $this->imsCommandOptionService,
                'cacheTypeList' => $this->typeListInterface,
                'updateTokensService' => $this->updateTokensService
            ]
        );
    }

    /**
     * Test AdminAdobeIms Command calls cache clear and return correct message
     *
     * @param bool $testAuthMode
     * @param string $enableMethodCallExpection
     * @param string $cleanMethodCallExpection
     * @param string $outputMessage
     * @param bool $isTwoFactorAuthEnabled
     * @return void
     * @throws Exception
     */
    #[DataProvider('cliCommandProvider')]
    public function testAdminAdobeImsModuleEnableWillClearCacheWhenSuccessful(
        bool $testAuthMode,
        string $enableMethodCallExpection,
        string $cleanMethodCallExpection,
        string $outputMessage,
        bool $isTwoFactorAuthEnabled
    ): void {
        $enableMatcher = $this->createInvocationMatcher($enableMethodCallExpection);
        $cleanMatcher = $this->createInvocationMatcher($cleanMethodCallExpection);
        $inputMock = $this->createMock(InputInterface::class);

        $outputMock = $this->createMock(OutputInterface::class);

        $this->questionHelperMock->method('ask')->willReturn('ORGId');

        $this->imsCommandOptionService->method('getOrganizationId')->willReturn('orgId');
        $this->imsCommandOptionService->method('getClientId')->willReturn('clientId');
        $this->imsCommandOptionService->method('getClientSecret')->willReturn('clientSecret');
        $this->imsCommandOptionService->method('isTwoFactorAuthEnabled')->willReturn($isTwoFactorAuthEnabled);

        $this->adminImsConnectionMock->method('testAuth')
            ->willReturn($testAuthMode);

        $this->adminImsConfigMock
            ->expects($enableMatcher)
            ->method('enableModule');

        $this->typeListInterface
            ->expects($cleanMatcher)
            ->method('cleanType')
            ->with(Config::TYPE_IDENTIFIER);

        $this->updateTokensService
            ->expects($this->createInvocationMatcher($cleanMethodCallExpection))
            ->method('execute');

        $outputMock->expects($this->once())
            ->method('writeln')
            ->with($outputMessage);

        $this->enableCommand->setHelperSet($this->getHelperSet());
        $this->enableCommand->run($inputMock, $outputMock);
    }

    /**
     * DataProvider for CLI Command
     *
     * @return array[]
     */
    public static function cliCommandProvider(): array
    {
        return [
            [
                true,
                'once',
                'once',
                'Admin Adobe IMS integration is enabled',
                true
            ],
            [
                false,
                'never',
                'never',
                '<error>The Client ID, Client Secret, Organization ID and 2FA are required ' .
                'when enabling the Admin Adobe IMS Module</error>',
                true
            ],
            [
                true,
                'never',
                'never',
                '<error>The Client ID, Client Secret, Organization ID and 2FA are required ' .
                'when enabling the Admin Adobe IMS Module</error>',
                false
            ],
            [
                false,
                'never',
                'never',
                '<error>The Client ID, Client Secret, Organization ID and 2FA are required ' .
                'when enabling the Admin Adobe IMS Module</error>',
                false
            ]
        ];
    }

    /**
     * Create a new HelperSet
     *
     * @return HelperSet
     */
    private function getHelperSet(): HelperSet
    {
        return new HelperSet([
            new FormatterHelper(),
            new DebugFormatterHelper(),
            new ProcessHelper(),
            'question' => $this->questionHelperMock,
        ]);
    }
}
