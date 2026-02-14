<?php

declare(strict_types=1);

namespace Petshop\Homepage\Controller\Adminhtml\Widget;

use Petshop\Homepage\Model\ResourceModel\Widget\CollectionFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class ValidateSchedule extends Action
{
    public const ADMIN_RESOURCE = 'Petshop_Homepage::widgets';

    public function __construct(
        Context $context,
        private readonly JsonFactory $jsonFactory,
        private readonly CollectionFactory $collectionFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();

        $widgetId = (int)$this->getRequest()->getParam('widget_id', 0);
        $isActive = (int)$this->getRequest()->getParam('is_active', 1);
        $widgetType = trim((string)$this->getRequest()->getParam('widget_type', ''));
        $startsAt = $this->normalizeDateTime((string)$this->getRequest()->getParam('starts_at', ''));
        $endsAt = $this->normalizeDateTime((string)$this->getRequest()->getParam('ends_at', ''));

        if ($isActive !== 1) {
            return $result->setData([
                'valid' => true,
                'message' => __('Widget is inactive, schedule conflict check skipped.')
            ]);
        }

        if ($startsAt === null || $endsAt === null) {
            return $result->setData([
                'valid' => false,
                'message' => __('Provide both Visible From and Visible Until to validate conflicts.'),
                'invalid_fields' => ['starts_at', 'ends_at']
            ]);
        }

        if (strtotime($startsAt) >= strtotime($endsAt)) {
            return $result->setData([
                'valid' => false,
                'message' => __('Visible From must be before Visible Until.'),
                'invalid_fields' => ['starts_at', 'ends_at']
            ]);
        }

        if ($widgetType === '') {
            return $result->setData([
                'valid' => false,
                'message' => __('Select a widget type before schedule validation.'),
                'invalid_fields' => ['widget_type']
            ]);
        }

        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('page_code', 'home')
            ->addFieldToFilter('placement', 'middle')
            ->addFieldToFilter('is_active', 1)
            ->addFieldToFilter('widget_type', $widgetType);

        if ($widgetId > 0) {
            $collection->addFieldToFilter('widget_id', ['neq' => $widgetId]);
        }

        $startTs = (int)strtotime($startsAt);
        $endTs = (int)strtotime($endsAt);

        foreach ($collection as $existing) {
            $existingStartRaw = (string)($existing->getData('starts_at') ?? '');
            $existingEndRaw = (string)($existing->getData('ends_at') ?? '');

            if ($existingStartRaw === '' || $existingEndRaw === '') {
                return $result->setData([
                    'valid' => false,
                    'message' => __('Widget #%1 has incomplete schedule and blocks auto-validation.', (int)$existing->getId())
                ]);
            }

            $existingStartTs = (int)strtotime($existingStartRaw);
            $existingEndTs = (int)strtotime($existingEndRaw);
            $overlaps = $startTs < $existingEndTs && $existingStartTs < $endTs;

            if ($overlaps) {
                return $result->setData([
                    'valid' => false,
                    'message' => __('Conflict with widget #%1 (%2 to %3) for widget type "%4".', (int)$existing->getId(), $existingStartRaw, $existingEndRaw, $widgetType),
                    'conflict' => [
                        'widget_id' => (int)$existing->getId(),
                        'widget_type' => (string)$existing->getData('widget_type'),
                        'starts_at' => $existingStartRaw,
                        'ends_at' => $existingEndRaw
                    ]
                ]);
            }
        }

        return $result->setData([
            'valid' => true,
            'message' => __('No scheduling conflicts detected.')
        ]);
    }

    private function normalizeDateTime(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $timestamp = strtotime(str_replace('T', ' ', $value));
        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d H:i:s', $timestamp);
    }
}
