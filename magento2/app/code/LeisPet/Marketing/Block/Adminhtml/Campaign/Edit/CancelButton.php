<?php

declare(strict_types=1);

namespace LeisPet\Marketing\Block\Adminhtml\Campaign\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class CancelButton extends GenericButton implements ButtonProviderInterface
{
    public function getButtonData(): array
    {
        $campaignId = $this->getCampaignId();
        if ($campaignId <= 0) {
            return [];
        }

        return [
            'label' => __('Cancel'),
            'class' => 'delete',
            'on_click' => sprintf(
                "confirmSetLocation('%s', '%s')",
                __('Are you sure you want to cancel this campaign?'),
                $this->getUrl('*/*/cancel', ['campaign_id' => $campaignId])
            ),
            'sort_order' => 60
        ];
    }
}
