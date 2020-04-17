<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SendFriend\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * SendFriend module configuration
 */
class Config implements ConfigInterface
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @inheritDoc
     */
    public function isEnabled($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @inheritDoc
     */
    public function isAllowForGuest($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ALLOW_FOR_GUEST,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @inheritDoc
     */
    public function getMaxRecipients($store = null)
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_MAX_RECIPIENTS,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @inheritDoc
     */
    public function getMaxEmailPerPeriod($store = null)
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_MAX_PER_HOUR,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @inheritDoc
     */
    public function getPeriod()
    {
        return 3600;
    }

    /**
     * @inheritDoc
     */
    public function getLimitBy($store = null)
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_LIMIT_BY,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @inheritDoc
     */
    public function getEmailTemplate($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_EMAIL_TEMPLATE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @inheritDoc
     */
    public function getCookieName()
    {
        return self::COOKIE_NAME;
    }
}
