<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SendFriend\Block;

use Magento\Captcha\Block\Captcha;
use Magento\Customer\Helper\View;
use Magento\Customer\Model\Context as CustomerContext;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\DataObject;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\SendFriend\Model\ConfigInterface;
use Magento\SendFriend\Model\SendFriend;

/**
 * Email to a Friend Block
 *
 * @api
 * @since 100.0.2
 */
class Send extends Template
{
    /**
     * SendFriend config data
     *
     * @var ConfigInterface
     */
    protected $_sendFriendConfig = null;

    /**
     * Core registry
     *
     * @var Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var Session
     */
    protected $_customerSession;

    /**
     * @var HttpContext
     */
    protected $httpContext;

    /**
     * @var View
     */
    protected $_customerViewHelper;

    /**
     * @var SendFriend
     */
    protected $sendfriend;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param ConfigInterface $sendFriendConfig
     * @param Registry $registry
     * @param View $customerViewHelper
     * @param HttpContext $httpContext
     * @param SendFriend $sendfriend
     * @param array $data
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        ConfigInterface $sendFriendConfig,
        Registry $registry,
        View $customerViewHelper,
        HttpContext $httpContext,
        SendFriend $sendfriend,
        array $data = []
    ) {
        $this->_customerSession = $customerSession;
        $this->_coreRegistry = $registry;
        $this->_sendFriendConfig = $sendFriendConfig;
        $this->sendfriend = $sendfriend;
        parent::__construct($context, $data);
        $this->_isScopePrivate = true;
        $this->httpContext = $httpContext;
        $this->_customerViewHelper = $customerViewHelper;
    }

    /**
     * Retrieve username for form field
     *
     * @return string
     */
    public function getUserName()
    {
        $name = $this->getFormData()->getData('sender/name');
        if (!empty($name)) {
            return trim($name);
        }

        /** @var Session $session */
        $session = $this->_customerSession;

        if ($this->httpContext->getValue(CustomerContext::CONTEXT_AUTH)) {
            return $this->_customerViewHelper->getCustomerName(
                $session->getCustomerDataObject()
            );
        }

        return '';
    }

    /**
     * Retrieve sender email address
     *
     * @return string
     */
    public function getEmail()
    {
        $email = $this->getFormData()->getData('sender/email');
        if (!empty($email)) {
            return trim($email);
        }

        /** @var Session $session */
        $session = $this->_customerSession;

        if ($this->httpContext->getValue(CustomerContext::CONTEXT_AUTH)) {
            return $session->getCustomerDataObject()->getEmail();
        }

        return '';
    }

    /**
     * Retrieve Message text
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->getFormData()->getData('sender/message');
    }

    /**
     * Retrieve Form data or empty DataObject
     *
     * @return DataObject
     */
    public function getFormData()
    {
        $data = $this->getData('form_data');
        if (!$data instanceof DataObject) {
            $data = new DataObject();
            $this->setData('form_data', $data);
        }

        return $data;
    }

    /**
     * Set Form data array
     *
     * @param array $data
     * @return $this
     */
    public function setFormData($data)
    {
        if (is_array($data)) {
            $this->setData('form_data', new DataObject($data));
        }

        return $this;
    }

    /**
     * Retrieve Current Product Id
     *
     * @SuppressWarnings(PHPMD.RequestAwareBlockMethod)
     * @return int
     */
    public function getProductId()
    {
        return $this->getRequest()->getParam('id', null);
    }

    /**
     * Retrieve current category id for product
     *
     * @SuppressWarnings(PHPMD.RequestAwareBlockMethod)
     * @return int
     */
    public function getCategoryId()
    {
        return $this->getRequest()->getParam('cat_id', null);
    }

    /**
     * Retrieve Max Recipients
     *
     * @return int
     */
    public function getMaxRecipients()
    {
        return $this->_sendFriendConfig->getMaxRecipients();
    }

    /**
     * Retrieve Send URL for Form Action
     *
     * @return string
     */
    public function getSendUrl()
    {
        return $this->getUrl(
            'sendfriend/product/sendmail',
            [
                'id' => $this->getProductId(),
                'cat_id' => $this->getCategoryId(),
            ]
        );
    }

    /**
     * Check if user is allowed to send
     *
     * @return boolean
     */
    public function canSend()
    {
        return !$this->sendfriend->isExceedLimit();
    }

    /**
     * @inheritdoc
     */
    protected function _prepareLayout()
    {
        if (!$this->getChildBlock('captcha')) {
            $this->addChild(
                'captcha',
                Captcha::class,
                [
                    'cacheable' => false,
                    'after' => '-',
                    'form_id' => 'product_sendtofriend_form',
                    'image_width' => 230,
                    'image_height' => 230
                ]
            );
        }

        return $this;
    }
}
