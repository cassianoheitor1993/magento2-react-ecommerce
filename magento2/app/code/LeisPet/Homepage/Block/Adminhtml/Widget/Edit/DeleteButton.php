<?php

declare(strict_types=1);

namespace LeisPet\Homepage\Block\Adminhtml\Widget\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class DeleteButton extends GenericButton implements ButtonProviderInterface
{
    public function getButtonData(): array
    {
        $widgetId = $this->getWidgetId();
        if ($widgetId <= 0) {
            return [];
        }

        return [
            'label' => __('Delete Widget'),
            'class' => 'delete',
            'on_click' => sprintf(
                "deleteConfirm('%s', '%s')",
                __('Are you sure you want to do this?'),
                $this->getUrl('*/*/delete', ['widget_id' => $widgetId])
            ),
            'sort_order' => 30
        ];
    }
}
