<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminAdobeIms\Plugin;

use Magento\AdminAdobeIms\Logger\AdminAdobeImsLogger;
use Magento\AdobeImsApi\Api\IsTokenValidInterface;
use Magento\Backend\Model\Auth;

/**
 * Validate ims access token
 */
class ValidateAccessTokenPlugin
{
    /**
     * @var AdminAdobeImsLogger
     */
    private AdminAdobeImsLogger $logger;

    /**
     * @var IsTokenValidInterface
     */
    private IsTokenValidInterface $isTokenValid;

    /**
     * @param IsTokenValidInterface $isTokenValid
     * @param AdminAdobeImsLogger $logger
     */
    public function __construct(
        IsTokenValidInterface $isTokenValid,
        AdminAdobeImsLogger $logger
    ) {
        $this->isTokenValid = $isTokenValid;
        $this->logger = $logger;
    }

    /**
     * Check if IMS access token is still valid
     *
     * @param Auth $subject
     * @param bool $result
     * @return bool
     * @throws \Magento\Framework\Exception\AuthorizationException
     */
    public function afterIsLoggedIn(Auth $subject, bool $result): bool
    {
        $accessToken = $subject->getAuthStorage()->getAdobeAccessToken();
        if ($result && $accessToken) {
            if (!$this->isTokenValid->validateToken($accessToken)) {
                $subject->logout();
                $this->logger->error('Admin Access Token is not valid');
                return false;
            }
        }
        return $result;
    }
}
