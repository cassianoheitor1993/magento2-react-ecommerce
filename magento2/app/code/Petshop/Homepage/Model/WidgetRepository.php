<?php

declare(strict_types=1);

namespace Petshop\Homepage\Model;

use Petshop\Homepage\Api\WidgetRepositoryInterface;
use Petshop\Homepage\Model\Config\WidgetConfigValidator;
use Petshop\Homepage\Model\ResourceModel\Widget as WidgetResource;
use Petshop\Homepage\Model\ResourceModel\Widget\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;

class WidgetRepository implements WidgetRepositoryInterface
{
    /**
     * @var array<string, string>
     */
    private array $defaultTitles = [
        'trust_badges' => 'Trust Badges',
        'testimonials' => 'Testimonials',
        'cta' => 'CTA',
        'categories_carousel' => 'Categories Carousel',
        'newsletter' => 'Newsletter'
    ];

    public function __construct(
        private readonly WidgetFactory $widgetFactory,
        private readonly WidgetResource $widgetResource,
        private readonly CollectionFactory $collectionFactory,
        private readonly WidgetConfigValidator $widgetConfigValidator
    ) {
    }

    public function saveWidgetData(array $data): Widget
    {
        $widgetId = isset($data['widget_id']) ? (int)$data['widget_id'] : 0;
        $widget = $this->widgetFactory->create();

        if ($widgetId > 0) {
            $this->widgetResource->load($widget, $widgetId);
        }

        $widgetType = (string)($data['widget_type'] ?? '');
        $title = trim((string)($data['title'] ?? ''));
        $isActive = (int)($data['is_active'] ?? 1);
        $sortOrder = (int)($data['sort_order'] ?? 0);
        $configJson = isset($data['config_json']) ? (string)$data['config_json'] : null;
        $startsAt = !empty($data['starts_at']) ? (string)$data['starts_at'] : null;
        $endsAt = !empty($data['ends_at']) ? (string)$data['ends_at'] : null;

        if ($widgetType === '') {
            throw new LocalizedException(__('Widget Type is required.'));
        }

        if ($title === '') {
            $title = $this->defaultTitles[$widgetType] ?? ucfirst(str_replace('_', ' ', $widgetType));
        }

        if ($sortOrder <= 0) {
            $sortOrder = $this->getNextSortOrder();
        }

        if ($startsAt !== null && $endsAt !== null && strtotime($startsAt) > strtotime($endsAt)) {
            throw new LocalizedException(__('Start date must be before end date.'));
        }

        if ($isActive === 1) {
            if ($startsAt === null || $endsAt === null) {
                throw new LocalizedException(__('Active widgets must have both start and end date/time.'));
            }

            $this->assertNoScheduleConflict($widgetId, $widgetType, $startsAt, $endsAt);
        }

        $normalizedConfig = $this->widgetConfigValidator->validateAndNormalize($widgetType, $configJson);

        $widget->setData('page_code', 'home');
        $widget->setData('placement', 'middle');
        $widget->setData('widget_type', $widgetType);
        $widget->setData('title', $title);
        $widget->setData('is_active', $isActive === 1 ? 1 : 0);
        $widget->setData('sort_order', $sortOrder);
        $widget->setData('config_json', json_encode($normalizedConfig, JSON_UNESCAPED_SLASHES));
        $widget->setData('starts_at', $startsAt);
        $widget->setData('ends_at', $endsAt);

        $this->widgetResource->save($widget);

        return $widget;
    }

    public function deleteById(int $widgetId): void
    {
        $widget = $this->widgetFactory->create();
        $this->widgetResource->load($widget, $widgetId);

        if (!$widget->getId()) {
            throw new LocalizedException(__('Widget with ID %1 does not exist.', $widgetId));
        }

        $this->widgetResource->delete($widget);
    }

    public function getActiveWidgetsForPage(string $pageCode = 'home', string $placement = 'middle'): array
    {
        $now = date('Y-m-d H:i:s');

        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('page_code', $pageCode)
            ->addFieldToFilter('placement', $placement)
            ->addFieldToFilter('is_active', 1)
            ->addFieldToFilter(['starts_at', 'starts_at'], [['null' => true], ['lteq' => $now]])
            ->addFieldToFilter(['ends_at', 'ends_at'], [['null' => true], ['gteq' => $now]])
            ->setOrder('sort_order', 'ASC')
            ->setOrder('widget_id', 'ASC');

        $items = [];
        foreach ($collection as $widget) {
            $items[] = [
                'widget_id' => (int)$widget->getId(),
                'widget_type' => (string)$widget->getData('widget_type'),
                'title' => (string)$widget->getData('title'),
                'page_code' => (string)$widget->getData('page_code'),
                'placement' => (string)$widget->getData('placement'),
                'is_active' => (bool)$widget->getData('is_active'),
                'sort_order' => (int)$widget->getData('sort_order'),
                'config_json' => (string)$widget->getData('config_json'),
                'starts_at' => $widget->getData('starts_at'),
                'ends_at' => $widget->getData('ends_at')
            ];
        }

        return $items;
    }

    public function moveWidget(int $widgetId, string $direction): void
    {
        $widget = $this->widgetFactory->create();
        $this->widgetResource->load($widget, $widgetId);

        if (!$widget->getId()) {
            throw new LocalizedException(__('Widget with ID %1 does not exist.', $widgetId));
        }

        $isUp = strtolower($direction) === 'up';

        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('page_code', (string)$widget->getData('page_code'))
            ->addFieldToFilter('placement', (string)$widget->getData('placement'));

        if ($isUp) {
            $collection->addFieldToFilter('sort_order', ['lt' => (int)$widget->getData('sort_order')])
                ->setOrder('sort_order', 'DESC');
        } else {
            $collection->addFieldToFilter('sort_order', ['gt' => (int)$widget->getData('sort_order')])
                ->setOrder('sort_order', 'ASC');
        }

        $collection->setPageSize(1);
        $target = $collection->getFirstItem();

        if (!$target->getId()) {
            return;
        }

        $currentSort = (int)$widget->getData('sort_order');
        $targetSort = (int)$target->getData('sort_order');

        $widget->setData('sort_order', $targetSort);
        $target->setData('sort_order', $currentSort);

        $this->widgetResource->save($widget);
        $this->widgetResource->save($target);
    }

    private function getNextSortOrder(): int
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('page_code', 'home')
            ->addFieldToFilter('placement', 'middle')
            ->setOrder('sort_order', 'DESC')
            ->setPageSize(1);

        $last = $collection->getFirstItem();
        $lastOrder = (int)$last->getData('sort_order');

        return $lastOrder > 0 ? $lastOrder + 10 : 10;
    }

    private function assertNoScheduleConflict(int $currentWidgetId, string $widgetType, string $startsAt, string $endsAt): void
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('page_code', 'home')
            ->addFieldToFilter('placement', 'middle')
            ->addFieldToFilter('is_active', 1)
            ->addFieldToFilter('widget_type', $widgetType);

        if ($currentWidgetId > 0) {
            $collection->addFieldToFilter('widget_id', ['neq' => $currentWidgetId]);
        }

        $currentStart = strtotime($startsAt) ?: 0;
        $currentEnd = strtotime($endsAt) ?: 0;

        foreach ($collection as $existing) {
            $existingStartRaw = (string)($existing->getData('starts_at') ?? '');
            $existingEndRaw = (string)($existing->getData('ends_at') ?? '');

            if ($existingStartRaw === '' || $existingEndRaw === '') {
                throw new LocalizedException(
                    __('Widget #%1 has no complete schedule. Please complete its schedule first.', (int)$existing->getId())
                );
            }

            $existingStart = strtotime($existingStartRaw) ?: 0;
            $existingEnd = strtotime($existingEndRaw) ?: 0;

            $overlaps = $currentStart < $existingEnd && $existingStart < $currentEnd;
            if ($overlaps) {
                throw new LocalizedException(
                    __('Schedule conflict with widget #%1 (%2 to %3) for widget type "%4". Overlaps are blocked only within the same widget type.',
                        (int)$existing->getId(),
                        $existingStartRaw,
                        $existingEndRaw,
                        $widgetType
                    )
                );
            }
        }
    }
}
