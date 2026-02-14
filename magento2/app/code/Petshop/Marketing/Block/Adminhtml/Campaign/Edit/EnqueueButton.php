<?php

declare(strict_types=1);

namespace Petshop\Marketing\Block\Adminhtml\Campaign\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class EnqueueButton extends GenericButton implements ButtonProviderInterface
{
    public function getButtonData(): array
    {
        $campaignId = $this->getCampaignId();
        if ($campaignId <= 0) {
            return [];
        }

        return [
            'label' => __('Enqueue'),
            'class' => 'secondary',
            'on_click' => sprintf("setLocation('%s')", $this->getUrl('*/*/enqueue', ['campaign_id' => $campaignId])),
            'sort_order' => 30
        ];
    }
}
