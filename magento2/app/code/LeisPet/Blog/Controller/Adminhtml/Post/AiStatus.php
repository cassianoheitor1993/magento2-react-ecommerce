<?php
namespace LeisPet\Blog\Controller\Adminhtml\Post;

use LeisPet\Blog\Model\AiJobManager;
use Magento\Framework\Controller\Result\JsonFactory;

class AiStatus extends AbstractPost
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
            $jobId = (int) $this->getRequest()->getParam('job_id');
            $job = $this->aiJobManager->get($jobId);
            $resultPayload = (string) ($job->getData('result_payload') ?? '');
            $decodedResult = $resultPayload ? json_decode($resultPayload, true) : null;

            return $result->setData([
                'success' => true,
                'job_id' => (int) $job->getId(),
                'status' => (string) $job->getData('status'),
                'job_type' => (string) $job->getData('job_type'),
                'post_id' => (int) ($job->getData('post_id') ?: 0),
                'result' => is_array($decodedResult) ? $decodedResult : null,
                'error_message' => (string) ($job->getData('error_message') ?? '')
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => (string) __('Unable to fetch AI job status: %1', $e->getMessage())
            ]);
        }
    }
}
