<?php
namespace LeisPet\Marketing\Controller\Adminhtml\Campaign;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    public const ADMIN_RESOURCE = 'LeisPet_Marketing::campaigns';

    public function __construct(Context $context, private readonly PageFactory $resultPageFactory)
    {
        parent::__construct($context);
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('LeisPet_Marketing::campaigns');
        $resultPage->getConfig()->getTitle()->prepend(__('Email Campaigns'));

        return $resultPage;
    }
}