<?php
namespace Petshop\Blog\Controller\Adminhtml\Post;

use Petshop\Blog\Model\DeepSeekClient;
use Petshop\Blog\Model\PostFactory;
use Petshop\Blog\Model\StoreInsightsProvider;
use Magento\Framework\Controller\Result\JsonFactory;

class AiGenerate extends AbstractPost
{
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        private readonly JsonFactory $jsonFactory,
        private readonly DeepSeekClient $deepSeekClient,
        private readonly StoreInsightsProvider $storeInsightsProvider,
        private readonly PostFactory $postFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();

        try {
            $postId = (int) $this->getRequest()->getParam('post_id');
            $topic = trim((string) $this->getRequest()->getParam('topic'));
            $petType = trim((string) $this->getRequest()->getParam('pet_type', 'all pets'));
            $tone = trim((string) $this->getRequest()->getParam('tone', 'helpful and professional'));
            $editorContext = (array) $this->getRequest()->getParam('editor_context', []);

            $post = $this->postFactory->create();
            if ($postId) {
                $post->load($postId);
            }

            if ($topic === '') {
                $topic = trim((string) ($post->getData('title') ?: 'Pet care and nutrition tips'));
            }

            $storeInsights = $this->storeInsightsProvider->getInsights();
            $generated = $this->deepSeekClient->generatePostWithContext(
                topic: $topic,
                petType: $petType,
                tone: $tone,
                title: $post->getData('title') ? (string) $post->getData('title') : null,
                context: [
                    'store' => $storeInsights,
                    'editor' => $editorContext
                ]
            );

            $post->addData($generated);
            if (!$post->getData('slug') && $post->getData('title')) {
                $slug = strtolower((string) $post->getData('title'));
                $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
                $post->setData('slug', trim((string) $slug, '-'));
            }
            $post->save();

            return $result->setData([
                'success' => true,
                'post_id' => (int) $post->getId(),
                'generated_fields' => ['title', 'excerpt', 'content', 'tags', 'author'],
                'data' => [
                    'title' => (string) $post->getData('title'),
                    'slug' => (string) $post->getData('slug'),
                    'excerpt' => (string) $post->getData('excerpt'),
                    'content' => (string) $post->getData('content'),
                    'tags' => (string) $post->getData('tags'),
                    'author' => (string) $post->getData('author')
                ]
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => (string) __('Unable to generate AI content: %1', $e->getMessage())
            ]);
        }
    }
}
