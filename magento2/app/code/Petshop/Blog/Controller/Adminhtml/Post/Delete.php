<?php
namespace Petshop\Blog\Controller\Adminhtml\Post;

use Petshop\Blog\Model\PostFactory;

class Delete extends AbstractPost
{
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        private readonly PostFactory $postFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $postId = (int) $this->getRequest()->getParam('post_id');
        if (!$postId) {
            $this->messageManager->addErrorMessage(__('We can\'t find a post to delete.'));
            return $this->_redirect('*/*/index');
        }

        try {
            $post = $this->postFactory->create()->load($postId);
            if (!$post->getId()) {
                throw new \RuntimeException((string) __('Post does not exist.'));
            }
            $post->delete();
            $this->messageManager->addSuccessMessage(__('The blog post has been deleted.'));
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $this->_redirect('*/*/edit', ['post_id' => $postId]);
        }

        return $this->_redirect('*/*/index');
    }
}
