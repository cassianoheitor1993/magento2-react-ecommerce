<?php

declare(strict_types=1);

namespace Petshop\Homepage\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class WidgetActions extends Column
{
    private const URL_PATH_EDIT = 'petshop_homepage/widget/edit';
    private const URL_PATH_DELETE = 'petshop_homepage/widget/delete';
    private const URL_PATH_SORT = 'petshop_homepage/widget/sort';

    public function __construct(
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory,
        private readonly UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        $name = (string)$this->getData('name');

        foreach ($dataSource['data']['items'] as &$item) {
            if (empty($item['widget_id'])) {
                continue;
            }

            $widgetId = (int)$item['widget_id'];
            $item[$name]['edit'] = [
                'href' => '#edit-widget-' . $widgetId,
                'label' => __('Edit'),
            ];
            $item[$name]['move_up'] = [
                'href' => $this->urlBuilder->getUrl(self::URL_PATH_SORT, ['widget_id' => $widgetId, 'direction' => 'up']),
                'label' => __('Move Up')
            ];
            $item[$name]['move_down'] = [
                'href' => $this->urlBuilder->getUrl(self::URL_PATH_SORT, ['widget_id' => $widgetId, 'direction' => 'down']),
                'label' => __('Move Down')
            ];
            $item[$name]['delete'] = [
                'href' => $this->urlBuilder->getUrl(self::URL_PATH_DELETE, ['widget_id' => $widgetId]),
                'label' => __('Delete'),
                'confirm' => [
                    'title' => __('Delete widget'),
                    'message' => __('Are you sure you want to delete this widget?')
                ]
            ];
        }

        return $dataSource;
    }
}
