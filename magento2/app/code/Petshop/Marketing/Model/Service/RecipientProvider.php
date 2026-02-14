<?php

namespace Petshop\Marketing\Model\Service;

use Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory as SubscriberCollectionFactory;

class RecipientProvider
{
	public function __construct(
		private readonly SubscriberCollectionFactory $subscriberCollectionFactory
	) {
	}

	/**
	 * @param array<string, mixed> $filters
	 * @return array<int, array<string, string>>
	 */
	public function getRecipients(string $audienceType = 'newsletter', array $filters = []): array
	{
		if ($audienceType !== 'newsletter') {
			return [];
		}

		$collection = $this->subscriberCollectionFactory->create();
		$collection->addFieldToFilter('subscriber_status', ['eq' => 1]);

		if (!empty($filters['email_like'])) {
			$collection->addFieldToFilter('subscriber_email', ['like' => (string) $filters['email_like']]);
		}

		$recipients = [];
		foreach ($collection as $subscriber) {
			$recipients[] = [
				'email' => (string) $subscriber->getSubscriberEmail(),
				'name' => '',
				'type' => 'subscriber',
			];
		}

		return $recipients;
	}
}
