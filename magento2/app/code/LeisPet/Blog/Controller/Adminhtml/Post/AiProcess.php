<?php
namespace LeisPet\Blog\Controller\Adminhtml\Post;

use LeisPet\Blog\Model\AiJobManager;
use LeisPet\Blog\Model\DeepSeekClient;
use LeisPet\Blog\Model\PostFactory;
use LeisPet\Blog\Model\StoreInsightsProvider;
use Magento\Framework\Controller\Result\JsonFactory;

class AiProcess extends AbstractPost
{
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        private readonly JsonFactory $jsonFactory,
        private readonly AiJobManager $aiJobManager,
        private readonly DeepSeekClient $deepSeekClient,
        private readonly StoreInsightsProvider $storeInsightsProvider,
        private readonly PostFactory $postFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $this->releaseSessionLock();
        $result = $this->jsonFactory->create();

        try {
            $jobId = (int) $this->getRequest()->getParam('job_id');
            $job = $this->aiJobManager->get($jobId);
            $status = (string) $job->getData('status');

            if ($status === 'completed' || $status === 'failed') {
                return $result->setData([
                    'success' => true,
                    'job_id' => (int) $job->getId(),
                    'status' => $status
                ]);
            }

            if ($status === 'processing') {
                return $result->setData([
                    'success' => true,
                    'job_id' => (int) $job->getId(),
                    'status' => 'processing'
                ]);
            }

            $this->aiJobManager->markProcessing($job);

            $requestPayload = json_decode((string) $job->getData('request_payload'), true);
            if (!is_array($requestPayload)) {
                throw new \RuntimeException((string) __('Invalid AI job payload.'));
            }

            $actionType = (string) ($requestPayload['action_type'] ?? 'full_post');
            $storeInsights = $this->storeInsightsProvider->getInsights();
            $editorContext = (array) ($requestPayload['editor_context'] ?? []);
            $postId = (int) ($job->getData('post_id') ?: 0);

            $post = $this->postFactory->create();
            if ($postId) {
                $post->load($postId);
            }

            if ($actionType === 'regenerate_field') {
                $field = trim((string) ($requestPayload['field'] ?? ''));
                $value = $this->deepSeekClient->regenerateField(
                    $field,
                    [
                        'store' => $storeInsights,
                        'editor' => $editorContext,
                        'existing_post' => $post->getData()
                    ],
                    (string) ($requestPayload['pet_type'] ?? 'all pets'),
                    (string) ($requestPayload['tone'] ?? 'helpful and professional')
                );

                $post->setData($field, $value);
                if (!$post->getData('slug') && $field === 'title') {
                    $slug = strtolower($value);
                    $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
                    $post->setData('slug', trim((string) $slug, '-'));
                }
                $post->save();

                $resultPayload = [
                    'post_id' => (int) $post->getId(),
                    'field' => $field,
                    'value' => $value,
                    'slug' => (string) $post->getData('slug'),
                    'generated_fields' => [$field]
                ];

                $this->aiJobManager->markCompleted($job, $resultPayload);
                return $result->setData([
                    'success' => true,
                    'job_id' => (int) $job->getId(),
                    'status' => 'completed',
                    'result' => $resultPayload
                ]);
            }

            $topic = trim((string) ($requestPayload['topic'] ?? ''));
            if ($topic === '') {
                $topic = trim((string) ($post->getData('title') ?: 'Pet care and nutrition tips'));
            }

            $generated = $this->deepSeekClient->generatePostWithContext(
                topic: $topic,
                petType: (string) ($requestPayload['pet_type'] ?? 'all pets'),
                tone: (string) ($requestPayload['tone'] ?? 'helpful and professional'),
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

            $resultPayload = [
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
            ];

            $this->aiJobManager->markCompleted($job, $resultPayload);
            return $result->setData([
                'success' => true,
                'job_id' => (int) $job->getId(),
                'status' => 'completed',
                'result' => $resultPayload
            ]);
        } catch (\Throwable $e) {
            if (isset($job) && $job->getId()) {
                try {
                    $this->aiJobManager->markFailed($job, $e->getMessage());
                } catch (\Throwable $markFailedException) {
                    // Ignore secondary persistence failures to keep JSON response stable.
                }
            }

            return $result->setData([
                'success' => false,
                'status' => 'failed',
                'message' => (string) __('Unable to process AI job: %1', $e->getMessage())
            ]);
        }
    }
}
