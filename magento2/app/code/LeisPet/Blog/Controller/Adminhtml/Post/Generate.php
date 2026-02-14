<?php
namespace LeisPet\Blog\Controller\Adminhtml\Post;

use LeisPet\Blog\Model\DeepSeekClient;
use LeisPet\Blog\Model\PostFactory;

class Generate extends AbstractPost
{
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        private readonly DeepSeekClient $deepSeekClient,
        private readonly PostFactory $postFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $postId = (int) $this->getRequest()->getParam('post_id');
        $topic = trim((string) $this->getRequest()->getParam('topic'));
        $petType = trim((string) $this->getRequest()->getParam('pet_type', 'dogs'));
        $tone = trim((string) $this->getRequest()->getParam('tone', 'helpful and professional'));

        try {
            $post = $this->postFactory->create();
            if ($postId) {
                $post->load($postId);
            }

            if ($topic === '') {
                $topic = (string) ($post->getData('title') ?: 'Pet care and nutrition tips');
            }

            $generated = $this->deepSeekClient->generatePost(
                topic: $topic,
                petType: $petType,
                tone: $tone,
                title: $post->getData('title') ? (string) $post->getData('title') : null
            );

            $post->addData($generated);
            if (!$post->getData('slug') && $post->getData('title')) {
                $slug = strtolower((string) $post->getData('title'));
                $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
                $post->setData('slug', trim((string) $slug, '-'));
            }

            $post->save();

            $this->messageManager->addSuccessMessage(__('AI content generated successfully. Review and save any additional edits.'));
            return $this->_redirect('*/*/edit', ['post_id' => $post->getId()]);
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage(__('Unable to generate AI content: %1', $e->getMessage()));
            if ($postId) {
                return $this->_redirect('*/*/edit', ['post_id' => $postId]);
            }

            return $this->_redirect('*/*/new');
        }
    }
}
