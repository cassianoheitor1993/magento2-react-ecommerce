<?php
namespace LeisPet\Blog\Controller\Adminhtml\Post;

use LeisPet\Blog\Model\PostFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Registry;

class Edit extends AbstractPost
{
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        private readonly PostFactory $postFactory,
        private readonly Registry $coreRegistry
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $postId = (int) $this->getRequest()->getParam('post_id');
        $post = $this->postFactory->create();

        if ($postId) {
            $post->load($postId);
            if (!$post->getId()) {
                $this->messageManager->addErrorMessage(__('This post no longer exists.'));
                return $this->_redirect('*/*/index');
            }
        }

        $this->coreRegistry->register('leispet_blog_post', $post);

        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('LeisPet_Blog::posts');
        $resultPage->getConfig()->getTitle()->prepend(
            $post->getId() ? __('Edit Blog Post') : __('New Blog Post')
        );

        return $resultPage;
    }
}
