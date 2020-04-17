<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SendFriend\Model;

use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Area;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DataObject;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\LocalizedException as CoreException;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context ;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Framework\Validator\EmailAddress;
use Magento\SendFriend\Model\ConfigInterface;
use Magento\SendFriend\Model\ResourceModel\SendFriend as SendFriendResourceModel;
use Magento\Store\Model\StoreManagerInterface;

/**
 * SendFriend Log
 *
 * @method int getIp()
 * @method \Magento\SendFriend\Model\SendFriend setIp(int $value)
 * @method int getTime()
 * @method \Magento\SendFriend\Model\SendFriend setTime(int $value)
 *
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @api
 * @since 100.0.2
 */
class SendFriend extends AbstractModel
{
    /**
     * Recipient Names
     *
     * @var array
     */
    protected $_names = [];

    /**
     * Recipient Emails
     *
     * @var array
     */
    protected $_emails = [];

    /**
     * Sender data array
     *
     * @var DataObject|array
     */
    protected $_sender = [];

    /**
     * Product Instance
     *
     * @var Product
     */
    protected $_product;

    /**
     * Count of sent in last period
     *
     * @var int
     */
    protected $_sentCount;

    /**
     * Last values for Cookie
     *
     * @var string
     */
    protected $_lastCookieValue = [];

    /**
     * @var ConfigInterface
     */
    protected $_sendFriendConfig = null;

    /**
     * Catalog image
     *
     * @var Image
     */
    protected $_catalogImage = null;

    /**
     * @var TransportBuilder
     */
    protected $_transportBuilder;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var Escaper
     */
    protected $_escaper;

    /**
     * @var StateInterface
     */
    protected $inlineTranslation;

    /**
     * @var CookieManagerInterface
     */
    protected $cookieManager;

    /**
     * @var RemoteAddress
     */
    protected $remoteAddress;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param StoreManagerInterface $storeManager
     * @param TransportBuilder $transportBuilder
     * @param Image $catalogImage
     * @param ConfigInterface $sendFriendConfig
     * @param Escaper $escaper
     * @param RemoteAddress $remoteAddress
     * @param CookieManagerInterface $cookieManager
     * @param StateInterface $inlineTranslation
     * @param AbstractResource $resource
     * @param AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        StoreManagerInterface $storeManager,
        TransportBuilder $transportBuilder,
        Image $catalogImage,
        ConfigInterface $sendFriendConfig,
        Escaper $escaper,
        RemoteAddress $remoteAddress,
        CookieManagerInterface $cookieManager,
        StateInterface $inlineTranslation,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_storeManager = $storeManager;
        $this->_transportBuilder = $transportBuilder;
        $this->_catalogImage = $catalogImage;
        $this->_sendFriendConfig = $sendFriendConfig;
        $this->_escaper = $escaper;
        $this->remoteAddress = $remoteAddress;
        $this->cookieManager = $cookieManager;
        $this->inlineTranslation = $inlineTranslation;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(SendFriendResourceModel::class);
    }

    /**
     * Sends email to recipients
     *
     * @return $this
     * @throws CoreException
     */
    public function send()
    {
        if ($this->isExceedLimit()) {
            throw new CoreException(
                __('You\'ve met your limit of %1 sends in an hour.', $this->getMaxSendsToFriend())
            );
        }

        $this->inlineTranslation->suspend();

        $message = nl2br($this->_escaper->escapeHtml($this->getSender()->getMessage()));
        $sender = [
            'name' => $this->_escaper->escapeHtml($this->getSender()->getName()),
            'email' => $this->_escaper->escapeHtml($this->getSender()->getEmail()),
        ];

        foreach ($this->getRecipients()->getEmails() as $k => $email) {
            $name = $this->getRecipients()->getNames($k);
            $this->_transportBuilder->setTemplateIdentifier(
                $this->_sendFriendConfig->getEmailTemplate()
            )->setTemplateOptions(
                [
                    'area' => Area::AREA_FRONTEND,
                    'store' => $this->_storeManager->getStore()->getId(),
                ]
            )->setFromByScope(
                'general'
            )->setReplyTo(
                $sender['email'],
                $sender['name']
            )->setTemplateVars(
                [
                    'name' => $name,
                    'email' => $email,
                    'product_name' => $this->getProduct()->getName(),
                    'product_url' => $this->getProduct()->getUrlInStore(),
                    'message' => $message,
                    'sender_name' => $sender['name'],
                    'sender_email' => $sender['email'],
                    'product_image' => $this->_catalogImage->init($this->getProduct(), 'sendfriend_small_image')
                        ->getUrl(),
                ]
            )->addTo(
                $email,
                $name
            );
            $transport = $this->_transportBuilder->getTransport();
            $transport->sendMessage();
        }

        $this->inlineTranslation->resume();

        $this->_incrementSentCount();

        return $this;
    }

    /**
     * Validate Form data
     *
     * @return bool|string[]
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function validate()
    {
        $errors = [];

        $name = $this->getSender()->getName();
        if (empty($name)) {
            $errors[] = __('Please enter a sender name.');
        }

        $email = $this->getSender()->getEmail();
        if (empty($email) || !\Zend_Validate::is($email, EmailAddress::class)) {
            $errors[] = __('Invalid Sender Email');
        }

        $message = $this->getSender()->getMessage();
        if (empty($message)) {
            $errors[] = __('Please enter a message.');
        }

        if (!$this->getRecipients()->getEmails()) {
            $errors[] = __('Please specify at least one recipient.');
        }

        // validate recipients email addresses
        foreach ($this->getRecipients()->getEmails() as $email) {
            if (!\Zend_Validate::is($email, EmailAddress::class)) {
                $errors[] = __('Please enter a correct recipient email address.');
                break;
            }
        }

        $maxRecipients = $this->getMaxRecipients();
        if (count($this->getRecipients()->getEmails()) > $maxRecipients) {
            $errors[] = __('No more than %1 emails can be sent at a time.', $this->getMaxRecipients());
        }

        if (empty($errors)) {
            return true;
        }

        return $errors;
    }

    /**
     * Set Recipients
     *
     * @param array $recipients
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function setRecipients($recipients)
    {
        // validate array
        if (!is_array(
            $recipients
        ) || !isset(
            $recipients['email']
        ) || !isset(
            $recipients['name']
        ) || !is_array(
            $recipients['email']
        ) || !is_array(
            $recipients['name']
        )
        ) {
            return $this;
        }

        $emails = [];
        $names = [];
        foreach ($recipients['email'] as $k => $email) {
            if (!isset($emails[$email]) && isset($recipients['name'][$k])) {
                $emails[$email] = true;
                $names[] = $recipients['name'][$k];
            }
        }

        if ($emails) {
            $emails = array_keys($emails);
        }

        return $this->setData(
            '_recipients',
            new DataObject(['emails' => $emails, 'names' => $names])
        );
    }

    /**
     * Retrieve Recipients object
     *
     * @return DataObject
     */
    public function getRecipients()
    {
        $recipients = $this->_getData('_recipients');
        if (!$recipients instanceof DataObject) {
            $recipients = new DataObject(['emails' => [], 'names' => []]);
            $this->setData('_recipients', $recipients);
        }
        return $recipients;
    }

    /**
     * Set product instance
     *
     * @param Product $product
     * @return $this
     */
    public function setProduct($product)
    {
        return $this->setData('_product', $product);
    }

    /**
     * Retrieve Product instance
     *
     * @throws CoreException
     * @return Product
     */
    public function getProduct()
    {
        $product = $this->_getData('_product');
        if (!$product instanceof Product) {
            throw new CoreException(__('Please define a correct product instance.'));
        }
        return $product;
    }

    /**
     * Set Sender Information array
     *
     * @param array $sender
     * @return $this
     */
    public function setSender($sender)
    {
        if (!is_array($sender)) {
            __('Invalid Sender Information');
        }

        return $this->setData('_sender', new DataObject($sender));
    }

    /**
     * Retrieve Sender Information Object
     *
     * @throws CoreException
     * @return DataObject
     */
    public function getSender()
    {
        $sender = $this->_getData('_sender');
        if (!$sender instanceof DataObject) {
            throw new CoreException(
                __('Please define the correct sender information.')
            );
        }
        return $sender;
    }

    /**
     * Get max allowed uses of "Send to Friend" function per hour
     *
     * @return integer
     */
    public function getMaxSendsToFriend()
    {
        return $this->_sendFriendConfig->getMaxEmailPerPeriod();
    }

    /**
     * Get max allowed recipients for "Send to a Friend" function
     *
     * @return integer
     */
    public function getMaxRecipients()
    {
        return $this->_sendFriendConfig->getMaxRecipients();
    }

    /**
     * Check if user is allowed to email product to a friend
     *
     * @return boolean
     */
    public function canEmailToFriend()
    {
        return $this->_sendFriendConfig->isEnabled();
    }

    /**
     * Check if user is exceed limit
     *
     * @return boolean
     */
    public function isExceedLimit()
    {
        return $this->getSentCount() >= $this->getMaxSendsToFriend();
    }

    /**
     * Return count of sent in last period
     *
     * @param bool $useCache - flag, is allow to use value of attribute of model if it is processed last time
     * @return int
     */
    public function getSentCount($useCache = true)
    {
        if ($useCache && $this->_sentCount !== null) {
            return $this->_sentCount;
        }

        switch ($this->_sendFriendConfig->getLimitBy()) {
            case ConfigInterface::CHECK_COOKIE:
                return $this->_sentCount = $this->_sentCountByCookies(false);
            case ConfigInterface::CHECK_IP:
                return $this->_sentCount = $this->_sentCountByIp(false);
            default:
                return 0;
        }
    }

    /**
     * Increase count of sent
     *
     * @return int
     */
    protected function _incrementSentCount()
    {
        switch ($this->_sendFriendConfig->getLimitBy()) {
            case ConfigInterface::CHECK_COOKIE:
                return $this->_sentCount = $this->_sentCountByCookies(true);
            case ConfigInterface::CHECK_IP:
                return $this->_sentCount = $this->_sentCountByIp(true);
            default:
                return 0;
        }
    }

    /**
     * Return count of sent in last period by cookie
     *
     * @param bool $increment - flag, increase count before return value
     * @return int
     */
    protected function _sentCountByCookies($increment = false)
    {
        $cookieName = $this->_sendFriendConfig->getCookieName();
        $time = time();
        $newTimes = [];

        if (isset($this->_lastCookieValue[$cookieName])) {
            $oldTimes = $this->_lastCookieValue[$cookieName];
        } else {
            $oldTimes = $this->cookieManager->getCookie($cookieName);
        }

        if ($oldTimes) {
            $oldTimes = explode(',', $oldTimes);
            foreach ($oldTimes as $oldTime) {
                $periodTime = $time - $this->_sendFriendConfig->getPeriod();
                if (is_numeric($oldTime) && $oldTime >= $periodTime) {
                    $newTimes[] = $oldTime;
                }
            }
        }

        if ($increment) {
            $newTimes[] = $time;
            $newValue = implode(',', $newTimes);
            $this->cookieManager->setSensitiveCookie($cookieName, $newValue);
            $this->_lastCookieValue[$cookieName] = $newValue;
        }

        return count($newTimes);
    }

    /**
     * Return count of sent in last period by IP address
     *
     * @param bool $increment - flag, increase count before return value
     * @return int
     */
    protected function _sentCountByIp($increment = false)
    {
        $time = time();
        $period = $this->_sendFriendConfig->getPeriod();
        $websiteId = $this->_storeManager->getStore()->getWebsiteId();

        if ($increment) {
            // delete expired logs
            $this->_getResource()->deleteLogsBefore($time - $period);
            // add new item
            $this->_getResource()->addSendItem($this->remoteAddress->getRemoteAddress(true), $time, $websiteId);
        }

        return $this->_getResource()->getSendCount(
            $this,
            $this->remoteAddress->getRemoteAddress(true),
            time() - $period,
            $websiteId
        );
    }
}
