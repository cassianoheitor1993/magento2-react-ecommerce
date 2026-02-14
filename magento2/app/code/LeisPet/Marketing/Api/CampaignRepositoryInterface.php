<?php

namespace LeisPet\Marketing\Api;

use LeisPet\Marketing\Model\Campaign;

interface CampaignRepositoryInterface
{
	public function saveCampaignData(array $data): Campaign;

	public function enqueueCampaign(int $campaignId): void;

	public function pauseCampaign(int $campaignId): void;

	public function resumeCampaign(int $campaignId): void;

	public function cancelCampaign(int $campaignId): void;

	/**
	 * @return array<string, mixed>
	 */
	public function getCampaignStatus(int $campaignId): array;
}
