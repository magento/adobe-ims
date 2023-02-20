<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminAdobeIms\Plugin;

use Magento\AdminAdobeIms\Service\ImsConfig;
use Magento\Backend\Block\Widget\Form as WidgetForm;
use Magento\Framework\Data\Form as DataForm;

/**
 * Plugin Class used to change the user form field to readonly for IMS users
 */
class UpdateUserFormFieldsPlugin
{
    /**
     * @var ImsConfig
     */
    private ImsConfig $adminImsConfig;

    /**
     * @var array
     */
    private array $fieldsToReadonly = [
        'email',
        'username',
        'firstname',
        'lastname'
    ];

    /**
     * @param ImsConfig $adminImsConfig
     */
    public function __construct(ImsConfig $adminImsConfig)
    {
        $this->adminImsConfig = $adminImsConfig;
    }

    /**
     * Update fields to readonly if adobe ims is enabled
     *
     * @param WidgetForm $subject
     * @param DataForm $result
     * @return DataForm
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function afterGetForm(WidgetForm $subject, DataForm $result): DataForm
    {
        if ($this->adminImsConfig->enabled() === false) {
            return $result;
        }

        if ($result->getElement('base_fieldset')) {
            $disableLocaleField = false;
            foreach ($result->getElement('base_fieldset')->getElements() as $element) {
                if (in_array($element->getId(), $this->fieldsToReadonly, true) && $element->getValue()) {
                    $disableLocaleField = true;
                    $element->setData('readonly', true);
                }
                if ($element->getId() === 'interface_locale' && $disableLocaleField) {
                    $element->setData('readonly', true);
                }
            }
        }
        return $result;
    }
}
