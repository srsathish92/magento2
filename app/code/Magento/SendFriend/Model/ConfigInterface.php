<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SendFriend\Model;

/**
 * SendFriend configuration
 *
 * @api
 * @since 100.0.2
 */
interface ConfigInterface
{
    /**
     * Enabled config path
     */
    const XML_PATH_ENABLED = 'sendfriend/email/enabled';

    /**
     * Allow guest path
     */
    const XML_PATH_ALLOW_FOR_GUEST = 'sendfriend/email/allow_guest';

    /**
     * Recipient email config path
     */
    const XML_PATH_MAX_RECIPIENTS = 'sendfriend/email/max_recipients';

    /**
     * Email template config path
     */
    const XML_PATH_EMAIL_TEMPLATE = 'sendfriend/email/template';

    /**
     * Max products 1ent in 1 Hour config path
     */
    const XML_PATH_MAX_PER_HOUR = 'sendfriend/email/max_per_hour';

    /**
     * Limit sending by config path
     */
    const XML_PATH_LIMIT_BY = 'sendfriend/email/check_by';

    /**
     * Key name for cookie
     */
    const COOKIE_NAME = 'stf';

    const CHECK_IP = 1;

    const CHECK_COOKIE = 0;

    /**
     * Check if sendfriend module is enabled
     *
     * @param int $store
     * @return bool
     * @since 100.2.0
     */
    public function isEnabled($store = null);

    /**
     * Check allow send email for guest
     *
     * @param null|int|string $store
     * @return bool
     */
    public function isAllowForGuest($store = null);

     /**
     * Retrieve Max Recipients
     *
     * @param null|int|string $store
     * @return int
     */
    public function getMaxRecipients($store = null);

    /**
     * Retrieve Max Products Sent in 1 Hour
     *
     * @param null|int|string $store
     * @return int
     */
    public function getMaxEmailPerPeriod($store = null);

    /**
     * Retrieve Limitation Period in seconds (1 hour)
     *
     * @return int
     */
    public function getPeriod();

    /**
     * Retrieve Limit Sending By
     *
     * @param null|int|string $store
     * @return int
     */
    public function getLimitBy($store = null);

    /**
     * Retrieve email template identifier
     *
     * @param null|int|string $store
     * @return mixed
     * @since 100.2.0
     */
    public function getEmailTemplate($store = null);

     /**
     * Retrieve Key Name for Cookie
     *
     * @see self::COOKIE_NAME
     * @return string
     */
    public function getCookieName();
}
