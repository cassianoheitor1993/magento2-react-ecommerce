<?php

namespace Petshop\Marketing\Model;

use Petshop\Marketing\Api\CampaignRepositoryInterface;
use Petshop\Marketing\Model\Queue\Publisher;
use Petshop\Marketing\Model\ResourceModel\Campaign as CampaignResource;
use Petshop\Marketing\Model\ResourceModel\CampaignJob\CollectionFactory as CampaignJobCollectionFactory;
use Petshop\Marketing\Model\Service\CampaignManager;
use Petshop\Marketing\Model\Service\RecipientProvider;
use Magento\Framework\Exception\LocalizedException;

class CampaignRepository implements CampaignRepositoryInterface
{
	public function __construct(
		private readonly CampaignFactory $campaignFactory,
		private readonly CampaignResource $campaignResource,
		private readonly CampaignJobFactory $campaignJobFactory,
		private readonly ResourceModel\CampaignJob $campaignJobResource,
		private readonly CampaignJobCollectionFactory $campaignJobCollectionFactory,
		private readonly RecipientProvider $recipientProvider,
		private readonly CampaignManager $campaignManager,
		private readonly Publisher $publisher
	) {
	}

	public function saveCampaignData(array $data): Campaign
	{
		$campaignId = isset($data['campaign_id']) ? (int) $data['campaign_id'] : 0;
		$campaign = $this->campaignFactory->create();

		if ($campaignId > 0) {
			$this->campaignResource->load($campaign, $campaignId);
		}

		$campaign->setData('name', (string)($data['name'] ?? ''));
		$campaign->setData('subject', (string)($data['subject'] ?? ''));
		$campaign->setData('sender_name', (string)($data['sender_name'] ?? ''));
		$campaign->setData('sender_email', (string)($data['sender_email'] ?? ''));
		$campaign->setData('template_identifier', (string)($data['template_identifier'] ?? ''));

		if ($campaign->getData('name') === '' || $campaign->getData('subject') === '' || $campaign->getData('template_identifier') === '') {
			throw new LocalizedException(__('Name, Subject and Template Identifier are required.'));
		}

		$campaign->setData('audience_type', (string)($data['audience_type'] ?? 'newsletter'));
		$campaign->setData('audience_filter_json', isset($data['audience_filter_json'])
			? (string)$data['audience_filter_json']
			: null);
		$campaign->setData('scheduled_at', !empty($data['scheduled_at']) ? (string)$data['scheduled_at'] : null);

		if (!$campaign->getId()) {
			$campaign->setData('status', 'draft');
			$campaign->setData('total_recipients', 0);
			$campaign->setData('processed_count', 0);
			$campaign->setData('sent_count', 0);
			$campaign->setData('failed_count', 0);
		}

		$this->campaignResource->save($campaign);
		return $campaign;
	}

	public function enqueueCampaign(int $campaignId): void
	{
		$campaign = $this->getCampaignOrFail($campaignId);

		$filters = [];
		if ($campaign->getData('audience_filter_json')) {
			$decoded = json_decode((string) $campaign->getData('audience_filter_json'), true);
			if (is_array($decoded)) {
				$filters = $decoded;
			}
		}

		$recipients = $this->recipientProvider->getRecipients((string)$campaign->getData('audience_type'), $filters);
		if (!$recipients) {
			throw new LocalizedException(__('No recipients found for this campaign.'));
		}

		foreach ($recipients as $recipient) {
			$job = $this->campaignJobFactory->create();
			$job->setData('campaign_id', $campaignId);
			$job->setData('recipient_email', (string)($recipient['email'] ?? ''));
			$job->setData('recipient_name', (string)($recipient['name'] ?? ''));
			$job->setData('recipient_type', (string)($recipient['type'] ?? 'subscriber'));
			$job->setData('status', 'queued');
			$job->setData('attempts', 0);

			try {
				$this->campaignJobResource->save($job);
				$this->publisher->publish((int)$job->getId());
			} catch (\Throwable $e) {
				// Likely duplicate recipient from unique key; skip this recipient.
				continue;
			}
		}

		$campaign->setData('status', 'queued');
		$campaign->setData('started_at', date('Y-m-d H:i:s'));
		$campaign->setData('finished_at', null);
		$campaign->setData('processed_count', 0);
		$campaign->setData('sent_count', 0);
		$campaign->setData('failed_count', 0);
		$this->campaignResource->save($campaign);

		$this->campaignManager->syncCampaignCounters($campaignId);
	}

	public function pauseCampaign(int $campaignId): void
	{
		$campaign = $this->getCampaignOrFail($campaignId);
		$campaign->setData('status', 'paused');
		$this->campaignResource->save($campaign);

		$jobs = $this->campaignJobCollectionFactory->create();
		$jobs->addFieldToFilter('campaign_id', $campaignId)
			->addFieldToFilter('status', ['in' => ['queued', 'processing']]);

		foreach ($jobs as $job) {
			$job->setData('status', 'paused');
			$this->campaignJobResource->save($job);
		}
	}

	public function resumeCampaign(int $campaignId): void
	{
		$campaign = $this->getCampaignOrFail($campaignId);
		$campaign->setData('status', 'queued');
		$this->campaignResource->save($campaign);

		$jobs = $this->campaignJobCollectionFactory->create();
		$jobs->addFieldToFilter('campaign_id', $campaignId)
			->addFieldToFilter('status', 'paused');

		foreach ($jobs as $job) {
			$job->setData('status', 'queued');
			$this->campaignJobResource->save($job);
			$this->publisher->publish((int)$job->getId());
		}
	}

	public function cancelCampaign(int $campaignId): void
	{
		$campaign = $this->getCampaignOrFail($campaignId);
		$campaign->setData('status', 'cancelled');
		$campaign->setData('finished_at', date('Y-m-d H:i:s'));
		$this->campaignResource->save($campaign);

		$jobs = $this->campaignJobCollectionFactory->create();
		$jobs->addFieldToFilter('campaign_id', $campaignId)
			->addFieldToFilter('status', ['in' => ['queued', 'processing', 'paused']]);

		foreach ($jobs as $job) {
			$job->setData('status', 'cancelled');
			$job->setData('processed_at', date('Y-m-d H:i:s'));
			$this->campaignJobResource->save($job);
		}

		$this->campaignManager->syncCampaignCounters($campaignId);
	}

	public function getCampaignStatus(int $campaignId): array
	{
		$campaign = $this->getCampaignOrFail($campaignId);
		$this->campaignManager->syncCampaignCounters($campaignId);
		$this->campaignResource->load($campaign, $campaignId);

		return [
			'campaign_id' => (int)$campaign->getId(),
			'status' => (string)$campaign->getData('status'),
			'total_recipients' => (int)$campaign->getData('total_recipients'),
			'processed_count' => (int)$campaign->getData('processed_count'),
			'sent_count' => (int)$campaign->getData('sent_count'),
			'failed_count' => (int)$campaign->getData('failed_count'),
			'started_at' => $campaign->getData('started_at'),
			'finished_at' => $campaign->getData('finished_at'),
		];
	}

	private function getCampaignOrFail(int $campaignId): Campaign
	{
		$campaign = $this->campaignFactory->create();
		$this->campaignResource->load($campaign, $campaignId);

		if (!$campaign->getId()) {
			throw new LocalizedException(__('Campaign with ID %1 does not exist.', $campaignId));
		}

		return $campaign;
	}
}
