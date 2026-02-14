<?php
namespace LeisPet\Blog\Controller\Adminhtml\Post;

use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\Controller\ResultFactory;

class Index extends AbstractPost
{
    public function execute(): Page
    {
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('LeisPet_Blog::posts');
        $resultPage->getConfig()->getTitle()->prepend(__('LeisPet Blog Posts'));

        return $resultPage;
    }
}
