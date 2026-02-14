<?php
namespace LeisPet\Blog\Model;

use LeisPet\Blog\Model\AiJobFactory;
use LeisPet\Blog\Model\ResourceModel\AiJob as AiJobResource;
use Magento\Framework\Exception\NoSuchEntityException;

class AiJobManager
{
    public function __construct(
        private readonly AiJobFactory $aiJobFactory,
        private readonly AiJobResource $aiJobResource
    ) {
    }

    public function enqueue(string $jobType, int $postId, array $payload): int
    {
        $job = $this->aiJobFactory->create();
        $job->setData([
            'job_type' => $jobType,
            'post_id' => $postId > 0 ? $postId : null,
            'status' => 'queued',
            'request_payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'result_payload' => null,
            'error_message' => null
        ]);
        $this->aiJobResource->save($job);

        return (int) $job->getId();
    }

    public function get(int $jobId): AiJob
    {
        $job = $this->aiJobFactory->create();
        $this->aiJobResource->load($job, $jobId);

        if (!$job->getId()) {
            throw new NoSuchEntityException(__('AI job with ID %1 does not exist.', $jobId));
        }

        return $job;
    }

    public function markProcessing(AiJob $job): void
    {
        $job->setData('status', 'processing');
        $job->setData('error_message', null);
        $this->aiJobResource->save($job);
    }

    public function markCompleted(AiJob $job, array $resultPayload): void
    {
        $job->setData('status', 'completed');
        $job->setData('result_payload', json_encode($resultPayload, JSON_UNESCAPED_UNICODE));
        $job->setData('error_message', null);
        $this->aiJobResource->save($job);
    }

    public function markFailed(AiJob $job, string $errorMessage): void
    {
        $job->setData('status', 'failed');
        $job->setData('error_message', $errorMessage);
        $this->aiJobResource->save($job);
    }
}
