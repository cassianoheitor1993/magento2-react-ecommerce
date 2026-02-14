<?php
namespace LeisPet\Blog\Controller\Adminhtml\Post;

use LeisPet\Blog\Model\PostFactory;

class Save extends AbstractPost
{
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        private readonly PostFactory $postFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        if (!$data) {
            return $this->_redirect('*/*/index');
        }

        $postId = (int) ($data['post_id'] ?? 0);
        $post = $this->postFactory->create();

        if ($postId) {
            $post->load($postId);
            if (!$post->getId()) {
                $this->messageManager->addErrorMessage(__('Unable to find post to save.'));
                return $this->_redirect('*/*/index');
            }
        }

        unset($data['post_id']);
        $post->addData($data);

        if (empty($data['slug']) && !empty($data['title'])) {
            $slug = strtolower((string) $data['title']);
            $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
            $post->setData('slug', trim((string) $slug, '-'));
        }

        try {
            $post->save();
            $this->messageManager->addSuccessMessage(__('The blog post has been saved.'));

            if ($this->getRequest()->getParam('back')) {
                return $this->_redirect('*/*/edit', ['post_id' => $post->getId(), '_current' => true]);
            }

            return $this->_redirect('*/*/index');
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $this->_redirect('*/*/edit', ['post_id' => $postId]);
        }
    }
}
