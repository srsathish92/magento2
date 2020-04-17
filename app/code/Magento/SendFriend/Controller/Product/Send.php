<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SendFriend\Controller\Product;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Session;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Forward;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Result\Page;
use Magento\SendFriend\Block\Send as BlockSend;
use Magento\SendFriend\Controller\Product;
use Magento\SendFriend\Model\ConfigInterface;
use Magento\SendFriend\Model\SendFriend;

/**
 * Controller class. Represents rendering and request flow
 */
class Send extends Product implements HttpGetActionInterface
{

    /**
     * @var EventManagerInterface
     */
    protected $_eventManager;

    public function __construct(
        Registry $coreRegistry,
        Validator $formKeyValidator,
        SendFriend $sendFriend,
        ProductRepositoryInterface $productRepository,
        UrlInterface $url,
        ManagerInterface $messageManager,
        RequestInterface $request,
        ResultFactory $resultFactory,
        Session $catalogSession,
        ConfigInterface $sendFriendConfig,
        CustomerSession $customerSession,
        EventManagerInterface $eventManager
    ) {
        $this->_eventManager = $eventManager;
        parent::__construct(
            $coreRegistry,
            $formKeyValidator,
            $sendFriend,
            $productRepository,
            $catalogSession,
            $url,
            $messageManager,
            $request,
            $resultFactory,
            $sendFriendConfig,
            $customerSession
        );
    }

    /**
     * Show Send to a Friend Form
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $product = $this->_initProduct();

        if (!$product) {
            /** @var Forward $resultForward */
            $resultForward = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);
            $resultForward->forward('noroute');
            return $resultForward;
        }

        if ($this->sendFriend->getMaxSendsToFriend() && $this->sendFriend->isExceedLimit()) {
            $this->messageManager->addNoticeMessage(
                __('You can\'t send messages more than %1 times an hour.', $this->sendFriend->getMaxSendsToFriend())
            );
        }

        /** @var Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        $this->_eventManager->dispatch('sendfriend_product', ['product' => $product]);
        $data = $this->catalogSession->getSendfriendFormData();
        if ($data) {
            $this->catalogSession->setSendfriendFormData(true);
            $block = $resultPage->getLayout()->getBlock('sendfriend.send');
            if ($block) {
                /** @var BlockSend $block */
                $block->setFormData($data);
            }
        }

        return $resultPage;
    }
}
