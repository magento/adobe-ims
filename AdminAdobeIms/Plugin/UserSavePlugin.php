<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminAdobeIms\Plugin;

use Exception;
use Magento\AdminAdobeIms\Service\ImsConfig;
use Magento\User\Model\ResourceModel\User as UserResourceModel;
use Magento\User\Model\User;
use Magento\User\Model\UserFactory;

class UserSavePlugin
{
    /**
     * @var ImsConfig
     */
    private ImsConfig $adminImsConfig;

    /**
     * @var UserFactory
     */
    private UserFactory $userFactory;

    /**
     * @var UserResourceModel
     */
    private UserResourceModel $userResource;

    /**
     * @param ImsConfig $adminImsConfig
     * @param UserFactory $userFactory
     * @param UserResourceModel $userResource
     */
    public function __construct(
        ImsConfig $adminImsConfig,
        UserFactory $userFactory,
        UserResourceModel $userResource
    ) {
        $this->adminImsConfig = $adminImsConfig;
        $this->userFactory = $userFactory;
        $this->userResource = $userResource;
    }

    /**
     * Generate a random password for new user when AdminAdobeIMS Module is enabled
     *
     * And revert firstname, lastname, username, email, and InterfaceLocale to original for saved user.
     * We create a random password for the user, because User Object needs to have a password
     * and this way we do not need to update the db_schema or add a lot of complex preferences
     *
     * @param User $subject
     * @return array
     * @throws Exception
     */
    public function beforeBeforeSave(User $subject): array
    {
        if ($this->adminImsConfig->enabled() !== true) {
            return [];
        }

        if (!$subject->getId()) {
            $subject->setPassword($this->generateRandomPassword());
        }
        $this->revertReadonlyFieldsData($subject);

        return [];
    }

    /**
     * Revert fields to original state because these fields are readonly for IMS User
     *
     * @param User $subject
     * @return void
     */
    private function revertReadonlyFieldsData(User $subject): void
    {
        if ($subject->hasDataChanges() && $subject->getId()) {
            $savedUser = $this->userFactory->create();
            $this->userResource->load($savedUser, $subject->getId());

            $subject->setUserName($savedUser->getUserName());
            $subject->setEmail($savedUser->getEmail());
            $subject->setFirstName($savedUser->getFirstName());
            $subject->setLastName($savedUser->getLastName());
            $subject->setInterfaceLocale($savedUser->getInterfaceLocale());
        }
    }

    /**
     * Generate random password string
     *
     * @return string
     * @throws Exception
     */
    private function generateRandomPassword(): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_-.';

        $pass = [];
        $alphaLength = strlen($characters) - 1;
        for ($i = 0; $i < 100; $i++) {
            $n = random_int(0, $alphaLength);
            $pass[] = $characters[$n];
        }
        return implode($pass);
    }
}
