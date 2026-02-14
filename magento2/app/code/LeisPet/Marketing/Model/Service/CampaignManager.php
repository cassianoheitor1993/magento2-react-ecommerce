<?php

namespace LeisPet\Marketing\Model\Service;

use LeisPet\Marketing\Model\CampaignFactory;
use LeisPet\Marketing\Model\ResourceModel\Campaign as CampaignResource;
use LeisPet\Marketing\Model\ResourceModel\CampaignJob\CollectionFactory as CampaignJobCollectionFactory;

class CampaignManager
{
	public function __construct(
		private readonly CampaignFactory $campaignFactory,
		private readonly CampaignResource $campaignResource,
		private readonly CampaignJobCollectionFactory $jobCollectionFactory
	) {
	}

	/**
	 * @return array<string, int>
	 */
	public function getCampaignCounters(int $campaignId): array
	{
		$baseCollection = $this->jobCollectionFactory->create();
		$baseCollection->addFieldToFilter('campaign_id', $campaignId);
		$total = (int) $baseCollection->getSize();

		$sentCollection = $this->jobCollectionFactory->create();
		$sentCollection->addFieldToFilter('campaign_id', $campaignId);
		$sentCollection->addFieldToFilter('status', 'sent');
		$sent = (int) $sentCollection->getSize();

		$failedCollection = $this->jobCollectionFactory->create();
		$failedCollection->addFieldToFilter('campaign_id', $campaignId);
		$failedCollection->addFieldToFilter('status', 'failed');
		$failed = (int) $failedCollection->getSize();

		return [
			'total_recipients' => $total,
			'sent_count' => $sent,
			'failed_count' => $failed,
			'processed_count' => $sent + $failed,
		];
	}

	public function syncCampaignCounters(int $campaignId): void
	{
		$campaign = $this->campaignFactory->create();
		$this->campaignResource->load($campaign, $campaignId);
		if (!$campaign->getId()) {
			return;
		}

		$counters = $this->getCampaignCounters($campaignId);
		$campaign->addData($counters);

		$isFinished = $counters['total_recipients'] > 0
			&& $counters['processed_count'] >= $counters['total_recipients'];

		if ($isFinished && !in_array((string)$campaign->getData('status'), ['cancelled', 'completed'], true)) {
			$campaign->setData('status', 'completed');
			$campaign->setData('finished_at', date('Y-m-d H:i:s'));
		}

		$this->campaignResource->save($campaign);
	}
}
