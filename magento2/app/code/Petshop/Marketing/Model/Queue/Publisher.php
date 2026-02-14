<?php
namespace Petshop\Marketing\Model\Queue;

use Magento\Framework\MessageQueue\PublisherInterface;

class Publisher
{
    public const TOPIC = 'petshop.marketing.campaign.send';

    public function __construct(private readonly PublisherInterface $publisher)
    {
    }

    public function publish(int $jobId): void
    {
        $this->publisher->publish(self::TOPIC, (string) $jobId);
    }
}