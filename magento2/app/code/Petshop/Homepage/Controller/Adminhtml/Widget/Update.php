<?php

declare(strict_types=1);

namespace Petshop\Homepage\Controller\Adminhtml\Widget;

use Petshop\Homepage\Api\WidgetRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * AJAX endpoint that updates an existing widget.
 * Used by the edit-via-modal flow (same modal as create, but with widget_id).
 */
class Update extends Action
{
    public const ADMIN_RESOURCE = 'Petshop_Homepage::widgets';

    public function __construct(
        Context $context,
        private readonly JsonFactory $jsonFactory,
        private readonly WidgetRepositoryInterface $widgetRepository
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        $data = (array)$this->getRequest()->getPostValue();

        if (!$data) {
            return $result->setData([
                'success' => false,
                'messages' => [(string)__('No data provided.')],
                'invalid_fields' => []
            ]);
        }

        $widgetId = (int)($data['widget_id'] ?? 0);

        if ($widgetId <= 0) {
            return $result->setData([
                'success' => false,
                'messages' => [(string)__('Widget ID is required for update.')],
                'invalid_fields' => []
            ]);
        }

        [$errors, $invalidFields] = $this->validatePayload($data);

        if ($errors) {
            return $result->setData([
                'success' => false,
                'messages' => $errors,
                'invalid_fields' => $invalidFields
            ]);
        }

        try {
            $payload = [
                'widget_id'   => $widgetId,
                'widget_type' => (string)($data['widget_type'] ?? ''),
                'title'       => trim((string)($data['title'] ?? '')),
                'is_active'   => (int)($data['is_active'] ?? 1),
                'sort_order'  => (int)($data['sort_order'] ?? 0),
                'starts_at'   => $this->normalizeDateTime((string)($data['starts_at'] ?? '')),
                'ends_at'     => $this->normalizeDateTime((string)($data['ends_at'] ?? '')),
                'config_json' => (string)($data['config_json'] ?? '')
            ];

            $widget = $this->widgetRepository->saveWidgetData($payload);

            return $result->setData([
                'success'   => true,
                'widget_id' => (int)$widget->getId(),
                'message'   => (string)__('Widget updated successfully.')
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'messages' => [(string)__('Failed to update widget: %1', $e->getMessage())],
                'invalid_fields' => []
            ]);
        }
    }

    /**
     * @return array{0: string[], 1: string[]}
     */
    private function validatePayload(array $data): array
    {
        $errors = [];
        $invalidFields = [];

        $widgetType = trim((string)($data['widget_type'] ?? ''));
        $configJson = trim((string)($data['config_json'] ?? ''));
        $startsAt = trim((string)($data['starts_at'] ?? ''));
        $endsAt = trim((string)($data['ends_at'] ?? ''));

        if ($widgetType === '') {
            $errors[] = (string)__('Widget Type is required.');
            $invalidFields[] = 'widget_type';
        }

        if ($configJson === '') {
            $errors[] = (string)__('Widget content (Config JSON) is required.');
            $invalidFields[] = 'config_json';
        }

        if ($startsAt === '') {
            $errors[] = (string)__('Visible From is required.');
            $invalidFields[] = 'starts_at';
        }

        if ($endsAt === '') {
            $errors[] = (string)__('Visible Until is required.');
            $invalidFields[] = 'ends_at';
        }

        $startTs = strtotime(str_replace('T', ' ', $startsAt));
        $endTs = strtotime(str_replace('T', ' ', $endsAt));

        if ($startsAt !== '' && $startTs === false) {
            $errors[] = (string)__('Visible From has an invalid date/time.');
            $invalidFields[] = 'starts_at';
        }

        if ($endsAt !== '' && $endTs === false) {
            $errors[] = (string)__('Visible Until has an invalid date/time.');
            $invalidFields[] = 'ends_at';
        }

        if ($startTs !== false && $endTs !== false && $startTs >= $endTs) {
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

        return $timestamp !== false ? date('Y-m-d H:i:s', $timestamp) : null;
    }
}
