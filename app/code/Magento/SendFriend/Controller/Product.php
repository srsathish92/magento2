<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SendFriend\Controller;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\Catalog\Model\Session as CatalogSession;
use Magento\Customer\Model\Session;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\SendFriend\Model\ConfigInterface;
use Magento\SendFriend\Model\SendFriend;

/**
 * Email to a Friend Product Controller
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class Product implements ActionInterface
{
    /**
     * Core registry
     *
     * @var Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var Validator
     */
    protected $_formKeyValidator;

    /**
     * @var SendFriend
     */
    protected $sendFriend;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var CatalogSession
     */
    protected $catalogSession;

    /**
     * @var UrlInterface
     */
    protected $_url;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var RequestInterface
     */
    protected $_request;

    /**
     * @var ResultFactory
     */
    protected $resultFactory;

    /**
     * @var ConfigInterface
     */
    protected $sendFriendConfig;

    /**
     * @var Session
     */
    protected $customerSession;

    public function __construct(
        Registry $coreRegistry,
        Validator $formKeyValidator,
        SendFriend $sendFriend,
        ProductRepositoryInterface $productRepository,
        CatalogSession $catalogSession,
        UrlInterface $url,
        ManagerInterface $messageManager,
        RequestInterface $request,
        ResultFactory $resultFactory,
        ConfigInterface $sendFriendConfig,
        Session $customerSession
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->_formKeyValidator = $formKeyValidator;
        $this->sendFriend = $sendFriend;
        $this->productRepository = $productRepository;
        $this->catalogSession = $catalogSession;
        $this->_url = $url;
        $this->_request = $request;
        $this->messageManager = $messageManager;
        $this->resultFactory = $resultFactory;
        $this->sendFriendConfig = $sendFriendConfig;
        $this->customerSession = $customerSession;
    }

    /**
     * Check if module is enabled
     *
     * If allow only for customer - redirect to login page
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws NotFoundException
     */
    public function dispatch(RequestInterface $request)
    {
        /** @var ConfigInterface $sendFriendConfig */
        $sendFriendConfig = $this->sendFriendConfig;

        /** @var Session $session */
        $session = $this->customerSession;

        if (!$sendFriendConfig->isEnabled()) {
            throw new NotFoundException(__('Page not found.'));
        }

        if (!$sendFriendConfig->isAllowForGuest() && !$session->authenticate()) {
            if ($this->getRequest()->getActionName() == 'sendemail') {
                $session->setBeforeAuthUrl($this->_url->getUrl('sendfriend/product/send', ['_current' => true]));
                $this->catalogSession->setSendfriendFormData($request->getPostValue());
            }
        }

        return $this->execute();
    }

    /**
     * Initialize Product Instance
     *
     * @return ProductModel
     */
    protected function _initProduct()
    {
        $productId = (int)$this->getRequest()->getParam('id');
        if (!$productId) {
            return false;
        }
        try {
            $product = $this->productRepository->getById($productId);
            if (!$product->isVisibleInSiteVisibility() || !$product->isVisibleInCatalog()) {
                return false;
            }
        } catch (NoSuchEntityException $noEntityException) {
            return false;
        }

        $this->_coreRegistry->register('product', $product);

        return $product;
    }

    /**
     * get Request
     *
     * @return RequestInterface
     */
    protected function getRequest()
    {
        return $this->_request;
    }
}
