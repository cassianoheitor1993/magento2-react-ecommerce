<?php
namespace Petshop\Blog\Controller\Adminhtml\Post;

use Petshop\Blog\Model\AiJobManager;
use Magento\Framework\Controller\Result\JsonFactory;

class AiEnqueue extends AbstractPost
{
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        private readonly JsonFactory $jsonFactory,
        private readonly AiJobManager $aiJobManager
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $this->releaseSessionLock();
        $result = $this->jsonFactory->create();

        try {
            $action = trim((string) $this->getRequest()->getParam('action_type', 'full_post'));
            $postId = (int) $this->getRequest()->getParam('post_id', 0);

            $payload = [
                'action_type' => $action,
                'topic' => trim((string) $this->getRequest()->getParam('topic', '')),
                'pet_type' => trim((string) $this->getRequest()->getParam('pet_type', 'all pets')),
                'tone' => trim((string) $this->getRequest()->getParam('tone', 'helpful and professional')),
                'field' => trim((string) $this->getRequest()->getParam('field', '')),
                'editor_context' => (array) $this->getRequest()->getParam('editor_context', [])
            ];

            $jobId = $this->aiJobManager->enqueue($action, $postId, $payload);

            return $result->setData([
                'success' => true,
                'job_id' => $jobId,
                'status' => 'queued'
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => (string) __('Unable to enqueue AI job: %1', $e->getMessage())
            ]);
        }
    }
}
