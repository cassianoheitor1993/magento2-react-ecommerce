<?php

declare(strict_types=1);

namespace Petshop\Homepage\Controller\Adminhtml\Widget;

use Petshop\Homepage\Api\WidgetRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;

class Create extends Action
{
    public const ADMIN_RESOURCE = 'Petshop_Homepage::widgets';

    public function __construct(
        Context $context,
        private readonly WidgetRepositoryInterface $widgetRepository
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $data = (array)$this->getRequest()->getPostValue();

        if (!$data) {
            return $result->setData([
                'success' => false,
                'messages' => [(string)__('No data provided to create widget.')],
                'invalid_fields' => []
            ]);
        }

        [$errors, $invalidFields] = $this->validateCreatePayload($data);
        if ($errors) {
            return $result->setData([
                'success' => false,
                'messages' => $errors,
                'invalid_fields' => $invalidFields
            ]);
        }

        try {
            $payload = [
                'widget_type' => (string)($data['widget_type'] ?? ''),
                'is_active' => (int)($data['is_active'] ?? 1),
                'sort_order' => (int)($data['sort_order'] ?? 0),
                'starts_at' => $this->normalizeDateTime((string)($data['starts_at'] ?? '')),
                'ends_at' => $this->normalizeDateTime((string)($data['ends_at'] ?? '')),
                'config_json' => (string)($data['config_json'] ?? '')
            ];

            $widget = $this->widgetRepository->saveWidgetData($payload);

            return $result->setData([
                'success' => true,
                'widget_id' => (int)$widget->getId(),
                'edit_url' => $this->getUrl('*/*/edit', ['widget_id' => (int)$widget->getId()])
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'messages' => [(string)__('Failed to create widget: %1', $e->getMessage())],
                'invalid_fields' => []
            ]);
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return array{0: array<int, string>, 1: array<int, string>}
     */
    private function validateCreatePayload(array $data): array
    {
        $errors = [];
        $invalidFields = [];

        $widgetType = trim((string)($data['widget_type'] ?? ''));
        $startsAt = trim((string)($data['starts_at'] ?? ''));
        $endsAt = trim((string)($data['ends_at'] ?? ''));
        $configJson = trim((string)($data['config_json'] ?? ''));

        if ($widgetType === '') {
            $errors[] = (string)__('Widget Type is required.');
            $invalidFields[] = 'widget_type';
        }

        if ($configJson === '') {
            $errors[] = (string)__('Generate content with AI before creating the widget.');
            $invalidFields[] = 'context';
        }

        if ($startsAt === '') {
            $errors[] = (string)__('Visible From is required.');
            $invalidFields[] = 'starts_at';
        }

        if ($endsAt === '') {
            $errors[] = (string)__('Visible Until is required.');
            $invalidFields[] = 'ends_at';
        }

        $startTimestamp = strtotime(str_replace('T', ' ', $startsAt));
        $endTimestamp = strtotime(str_replace('T', ' ', $endsAt));

        if ($startsAt !== '' && $startTimestamp === false) {
            $errors[] = (string)__('Visible From has invalid date/time.');
            $invalidFields[] = 'starts_at';
        }

        if ($endsAt !== '' && $endTimestamp === false) {
            $errors[] = (string)__('Visible Until has invalid date/time.');
            $invalidFields[] = 'ends_at';
        }

        if ($startTimestamp !== false && $endTimestamp !== false && $startTimestamp >= $endTimestamp) {
            $errors[] = (string)__('Visible From must be before Visible Until.');
            $invalidFields[] = 'starts_at';
            $invalidFields[] = 'ends_at';
        }

        return [array_values(array_unique($errors)), array_values(array_unique($invalidFields))];
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
