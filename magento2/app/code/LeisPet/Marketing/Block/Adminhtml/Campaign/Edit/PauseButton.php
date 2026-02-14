<?php

declare(strict_types=1);

namespace LeisPet\Marketing\Block\Adminhtml\Campaign\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class PauseButton extends GenericButton implements ButtonProviderInterface
{
    public function getButtonData(): array
    {
        $campaignId = $this->getCampaignId();
        if ($campaignId <= 0) {
            return [];
        }

        return [
            'label' => __('Pause'),
            'class' => 'secondary',
            'on_click' => sprintf("setLocation('%s')", $this->getUrl('*/*/pause', ['campaign_id' => $campaignId])),
            'sort_order' => 40
        ];
    }
}
