<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminAdobeIms\Test\Unit\Plugin;

use Magento\AdminAdobeIms\Plugin\UpdateUserFormFieldsPlugin;
use Magento\AdminAdobeIms\Service\ImsConfig;
use Magento\Backend\Block\Widget\Form as WidgetForm;
use Magento\Framework\Data\Form as DataForm;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test class for UpdateUserFormFieldsPlugin
 */
class UpdateUserFormFieldsPluginTest extends TestCase
{
    /**
     * @var ImsConfig|mixed|MockObject
     */
    private mixed $adminImsConfigMock;

    /**
     * @var UpdateUserFormFieldsPlugin
     */
    private UpdateUserFormFieldsPlugin $plugin;

    /**
     * @var WidgetForm|mixed|MockObject
     */
    private mixed $widgetFormMock;

    /**
     * @var DataForm|mixed|MockObject
     */
    private mixed $dataFormMock;

    /**
     * @var DataForm\Element\Fieldset|mixed|MockObject
     */
    private mixed $fiedsetMock;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->adminImsConfigMock = $this->createMock(ImsConfig::class);
        $this->widgetFormMock = $this->createMock(WidgetForm::class);
        $this->dataFormMock = $this->createMock(DataForm::class);
        $this->fiedsetMock = $this->createMock(DataForm\Element\Fieldset::class);

        $this->plugin = new UpdateUserFormFieldsPlugin(
            $this->adminImsConfigMock
        );
    }

    /**
     * Validate some fields are readonly for ims user.
     * @return void
     */
    public function testUserFormFieldReadonly(): void
    {
        $this->adminImsConfigMock
            ->expects($this->once())
            ->method('enabled')
            ->willReturn(true);
        $collectionMock = $this->createMock(DataForm\Element\Collection::class);

        $collectionMock->expects($this->any())->method('getIterator')->willReturn(
            new \ArrayIterator([
                $this->getElementField('text', 'email', 'imsuser@test.com'),
                $this->getElementField('text', 'username', 'imsuser'),
                $this->getElementField('text', 'firstname', 'imsuser'),
                $this->getElementField('text', 'lastname', 'test'),
                $this->getElementField('select', 'interface_locale', 'en_US')
            ])
        );
        $this->fiedsetMock->expects($this->any())->method('getElements')
            ->willReturn($collectionMock);
        $this->dataFormMock->expects($this->any())->method('getElement')
            ->with('base_fieldset')
            ->willReturn($this->fiedsetMock);
        $result = $this->plugin->afterGetForm($this->widgetFormMock, $this->dataFormMock);

        foreach ($result->getElement('base_fieldset')->getElements() as $element) {
            $this->assertTrue($element->getData('readonly'));
        }
    }

    /**
     * @param string $type
     * @param string $id
     * @param string $value
     * @return MockObject
     */
    private function getElementField(string $type, string $id, string $value): MockObject
    {
        $elementMock = $this->getMockBuilder(DataForm\Element\AbstractElement::class)
            ->disableOriginalConstructor()
            ->addMethods(['getValue'])
            ->onlyMethods(['getId', 'getType'])
            ->getMock();
        $elementMock->expects($this->any())->method('getId')->willReturn($id);
        $elementMock->expects($this->any())->method('getValue')->willReturn($value);
        $elementMock->expects($this->any())->method('getType')->willReturn($type);
        return $elementMock;
    }
}
