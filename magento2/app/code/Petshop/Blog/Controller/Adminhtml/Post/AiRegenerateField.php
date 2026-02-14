<?php
namespace Petshop\Blog\Controller\Adminhtml\Post;

use Petshop\Blog\Model\DeepSeekClient;
use Petshop\Blog\Model\PostFactory;
use Petshop\Blog\Model\StoreInsightsProvider;
use Magento\Framework\Controller\Result\JsonFactory;

class AiRegenerateField extends AbstractPost
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
            $field = trim((string) $this->getRequest()->getParam('field'));
            $petType = trim((string) $this->getRequest()->getParam('pet_type', 'all pets'));
            $tone = trim((string) $this->getRequest()->getParam('tone', 'helpful and professional'));
            $editorContext = (array) $this->getRequest()->getParam('editor_context', []);

            $post = $this->postFactory->create();
            if ($postId) {
                $post->load($postId);
            }

            $storeInsights = $this->storeInsightsProvider->getInsights();
            $context = [
                'store' => $storeInsights,
                'editor' => $editorContext,
                'existing_post' => $post->getData()
            ];

            $value = $this->deepSeekClient->regenerateField($field, $context, $petType, $tone);

            if ($value !== '') {
                $post->setData($field, $value);

                if (!$post->getData('slug') && $field === 'title') {
                    $slug = strtolower($value);
                    $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
                    $post->setData('slug', trim((string) $slug, '-'));
                }

                $post->save();
            }

            return $result->setData([
                'success' => true,
                'post_id' => (int) $post->getId(),
                'field' => $field,
                'value' => $value,
                'slug' => (string) $post->getData('slug'),
                'generated_fields' => [$field]
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => (string) __('Unable to regenerate field: %1', $e->getMessage())
            ]);
        }
    }
}
