<?php
namespace Petshop\Blog\Controller\Adminhtml\Post;

use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\Controller\ResultFactory;

class Index extends AbstractPost
{
    public function execute(): Page
    {
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Petshop_Blog::posts');
        $resultPage->getConfig()->getTitle()->prepend(__('Petshop Blog Posts'));

        return $resultPage;
    }
}
