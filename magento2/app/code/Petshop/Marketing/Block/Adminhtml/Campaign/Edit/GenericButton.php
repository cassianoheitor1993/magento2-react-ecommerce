<?php

declare(strict_types=1);

namespace Petshop\Marketing\Block\Adminhtml\Campaign\Edit;

use Magento\Backend\Block\Widget\Context;

class GenericButton
{
    public function __construct(protected readonly Context $context)
    {
    }

    protected function getCampaignId(): int
    {
        return (int)$this->context->getRequest()->getParam('campaign_id');
    }

    protected function getUrl(string $route = '', array $params = []): string
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}
