<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\AdminAdobeIms\Model;

use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\LocalizedException;
use Magento\User\Model\User as AdminUser;
use Magento\AdminAdobeIms\Model\ResourceModel\User as AdminResourceUser;

class User extends AdminUser
{
    /**
     * Initialize user model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(AdminResourceUser::class);
    }

    /**
     * @param string $email
     * @return mixed
     */
    public function loadByEmail($email)
    {
        return $this->getResource()->loadByEmail($email);
    }

    /**
     * Login user
     *
     * @param string $username
     * @return User
     * @throws LocalizedException
     */
    public function loginByUsername($username): User
    {
        if ($this->authenticateByUsername($username)) {
            $this->getResource()->recordLogin($this);
        }
        return $this;
    }

    /**
     * Authenticate user name and save loaded record
     *
     * @param string $username
     * @return bool
     * @throws LocalizedException
     */
    public function authenticateByUsername(string $username): bool
    {
        $config = $this->_config->isSetFlag('admin/security/use_case_sensitive_login');
        $result = false;

        try {
            $this->_eventManager->dispatch(
                'admin_user_authenticate_before',
                ['username' => $username, 'user' => $this]
            );
            $this->loadByUsername($username);
            $sensitive = $config ? $username == $this->getUserName() : true;
            if ($sensitive && $this->getId()) {
                $result = $this->verifyIdentityWithoutPassword();
            }

        } catch (LocalizedException $e) {
            $this->unsetData();
            throw $e;
        }

        if (!$result) {
            $this->unsetData();
        }
        return $result;
    }

    /**
     * Check if the current user account is active.
     *
     * @return bool
     * @throws \Magento\Framework\Exception\AuthenticationException
     */
    public function verifyIdentityWithoutPassword(): bool
    {
        if ($this->getIsActive() != '1') {
            throw new AuthenticationException(
                __(
                    'The account sign-in was incorrect or your account is disabled temporarily. '
                    . 'Please wait and try again later.'
                )
            );
        }
        if (!$this->hasAssigned2Role($this->getId())) {
            throw new AuthenticationException(__('More permissions are needed to access this.'));
        }

        return true;
    }
}