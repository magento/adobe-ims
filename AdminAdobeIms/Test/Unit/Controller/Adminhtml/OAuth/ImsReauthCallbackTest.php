<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminAdobeIms\Test\Unit\Controller\Adminhtml\OAuth;

use Magento\AdminAdobeIms\Controller\Adminhtml\OAuth\ImsReauthCallback;
use Magento\AdminAdobeIms\Logger\AdminAdobeImsLogger;
use Magento\AdminAdobeIms\Model\Authorization\AdobeImsAdminTokenUserService;
use Magento\AdminAdobeIms\Service\ImsConfig;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Message\Manager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for \Magento\AdminAdobeIms\Controller\Adminhtml\OAuth\ImsReauthCallback controller.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ImsReauthCallbackTest extends TestCase
{
    /**
     * @var RequestInterface|mixed|MockObject
     */
    private mixed $request;

    /**
     * @var Raw|mixed|MockObject
     */
    private mixed $resultRaw;

    /**
     * @var ResultFactory|mixed|MockObject
     */
    private mixed $resultFactory;

    /**
     * @var Context|mixed|MockObject
     */
    private mixed $context;

    /**
     * @var AdminAdobeImsLogger|mixed|MockObject
     */
    private mixed $loggerMock;

    /**
     * @var AdobeImsAdminTokenUserService|mixed|MockObject
     */
    private mixed $serviceMock;

    /**
     * @var ImsReauthCallback
     */
    private ImsReauthCallback $controller;

    /**
     * @var ImsConfig|mixed|MockObject
     */
    private mixed $imsConfigMock;

    /**
     * @var Manager|mixed|MockObject
     */
    private mixed $messagesMock;

    /**
     * @var Validator|mixed|MockObject
     */
    private mixed $validatorMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->request = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getParam', 'setParam'])
            ->getMock();
        $this->resultRaw = $this->createMock(Raw::class);
        $this->resultFactory = $this->createMock(ResultFactory::class);
        $this->context = $this->createMock(Context::class);
        $this->imsConfigMock = $this->createMock(ImsConfig::class);
        $this->loggerMock = $this->createMock(AdminAdobeImsLogger::class);
        $this->serviceMock = $this->createMock(AdobeImsAdminTokenUserService::class);
        $this->messagesMock = $this->getMockBuilder(Manager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addErrorMessage'])
            ->getMockForAbstractClass();
    }

    /**
     * Validate state key exists in callback
     * @return void
     */
    public function testExecuteStateKeyExistsInCallBack(): void
    {
        $this->setMockData();
        $content = 'auth[code=success;message=Authorization was successful]';
        $this->resultRaw->expects($this->once())->method('setContents')->with($content)->willReturnSelf();
        $this->request->expects($this->any())->method('getParam')
            ->with('state')
            ->willReturn('abc');

        $this->validatorMock->expects($this->once())->method('validate')
            ->with($this->request)
            ->willReturn(true);

        $this->assertSame($this->resultRaw, $this->controller->execute());
    }

    /**
     * Validate state key not exists in callback
     * @return void
     */
    public function testExecuteStateKeyNotExistsInCallBack(): void
    {
        $this->setMockData();
        $content = 'auth[code=error;message=Invalid state returned from IMS]';
        $this->resultRaw->expects($this->once())->method('setContents')->with($content)->willReturnSelf();
        $this->validatorMock->expects($this->once())->method('validate')
            ->with($this->request)
            ->willReturn(false);

        $this->assertSame($this->resultRaw, $this->controller->execute());
    }

    /**
     * Set mock objects data
     * @return void
     */
    private function setMockData(): void
    {
        $this->request->expects($this->any())->method('setParam')
            ->with('form_key')
            ->willReturnSelf();
        $this->request->expects($this->any())->method('getParam')
            ->with('state')
            ->willReturn(null);
        $this->resultFactory->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_RAW)
            ->willReturn($this->resultRaw);
        $this->validatorMock = $this->getMockBuilder(Validator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->context->expects($this->once())->method('getRequest')->willReturn($this->request);
        $this->context->expects($this->once())->method('getResultFactory')->willReturn($this->resultFactory);
        $this->context->expects($this->any())->method('getMessageManager')->willReturn($this->messagesMock);
        $this->context->expects($this->any())->method('getFormKeyValidator')->willReturn($this->validatorMock);
        $this->serviceMock->expects($this->any())->method('processLoginRequest')
            ->with(true);
        $this->imsConfigMock->expects($this->once())->method('enabled')
            ->willReturn(true);
        $this->controller = new ImsReauthCallback(
            $this->context,
            $this->imsConfigMock,
            $this->serviceMock,
            $this->loggerMock
        );
    }
}
