<?php

declare(strict_types=1);

namespace LeisPet\Homepage\Block\Adminhtml\Widget\Edit;

use Magento\Backend\Block\Widget\Context;

class GenericButton
{
    public function __construct(protected readonly Context $context)
    {
    }

    protected function getWidgetId(): int
    {
        return (int)$this->context->getRequest()->getParam('widget_id');
    }

    protected function getUrl(string $route = '', array $params = []): string
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}
