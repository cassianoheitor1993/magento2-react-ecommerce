<?php
namespace LeisPet\Marketing\Model\Queue\Consumer;

use LeisPet\Marketing\Model\CampaignFactory;
use LeisPet\Marketing\Model\CampaignJobFactory;
use LeisPet\Marketing\Model\ResourceModel\Campaign as CampaignResource;
use LeisPet\Marketing\Model\ResourceModel\CampaignJob as CampaignJobResource;
use Psr\Log\LoggerInterface;

class CampaignSendConsumer
{
    public function __construct(
        private readonly CampaignJobFactory $jobFactory,
        private readonly CampaignJobResource $jobResource,
        private readonly CampaignFactory $campaignFactory,
        private readonly CampaignResource $campaignResource,
        private readonly LoggerInterface $logger
    ) {
    }

    public function process(string $jobIdPayload): void
    {
        $jobId = (int) $jobIdPayload;
        $job = $this->jobFactory->create();
        $this->jobResource->load($job, $jobId);

        if (!$job->getId()) {
            return;
        }

        if ((string)$job->getData('status') !== 'queued') {
            return;
        }

        $campaign = $this->campaignFactory->create();
        $this->campaignResource->load($campaign, (int)$job->getData('campaign_id'));
        $campaignStatus = (string)$campaign->getData('status');

        if ($campaignStatus === 'paused') {
            $job->setData('status', 'paused');
            $this->jobResource->save($job);
            return;
        }

        if ($campaignStatus === 'cancelled') {
            $job->setData('status', 'cancelled');
            $job->setData('processed_at', date('Y-m-d H:i:s'));
            $this->jobResource->save($job);
            return;
        }

        try {
            $job->setData('status', 'processing');
            $this->jobResource->save($job);

            // TODO: build and send email via TransportBuilder.

            $job->setData('status', 'sent');
            $job->setData('processed_at', date('Y-m-d H:i:s'));
            $this->jobResource->save($job);
        } catch (\Throwable $e) {
            $job->setData('status', 'failed');
            $job->setData('last_error', $e->getMessage());
            $job->setData('attempts', ((int)$job->getData('attempts')) + 1);
            $this->jobResource->save($job);

            $this->logger->error('LeisPet Marketing consumer failure', [
                'job_id' => $jobId,
                'error' => $e->getMessage()
            ]);
        }
    }
}