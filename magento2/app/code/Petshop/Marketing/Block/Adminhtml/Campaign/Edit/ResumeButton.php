<?php

declare(strict_types=1);

namespace Petshop\Marketing\Block\Adminhtml\Campaign\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class ResumeButton extends GenericButton implements ButtonProviderInterface
{
    public function getButtonData(): array
    {
        $campaignId = $this->getCampaignId();
        if ($campaignId <= 0) {
            return [];
        }

        return [
            'label' => __('Resume'),
            'class' => 'secondary',
            'on_click' => sprintf("setLocation('%s')", $this->getUrl('*/*/resume', ['campaign_id' => $campaignId])),
            'sort_order' => 50
        ];
    }
}
