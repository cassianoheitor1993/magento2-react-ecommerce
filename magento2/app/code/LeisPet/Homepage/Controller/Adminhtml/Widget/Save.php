<?php

declare(strict_types=1);

namespace LeisPet\Homepage\Controller\Adminhtml\Widget;

use LeisPet\Homepage\Api\WidgetRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;

class Save extends Action
{
    public const ADMIN_RESOURCE = 'LeisPet_Homepage::widgets';

    public function __construct(
        Context $context,
        private readonly WidgetRepositoryInterface $widgetRepository,
        private readonly DataPersistorInterface $dataPersistor
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $data = (array)$this->getRequest()->getPostValue();

        if (isset($data['data']) && is_array($data['data'])) {
            $data = $data['data'];
        }
        if (isset($data['general']) && is_array($data['general'])) {
            $data = array_merge($data, $data['general']);
            unset($data['general']);
        }

        $data['starts_at'] = $this->normalizeDateTime((string)($data['starts_at'] ?? ''));
        $data['ends_at'] = $this->normalizeDateTime((string)($data['ends_at'] ?? ''));

        if (!$data) {
            return $this->resultRedirectFactory->create()->setPath('*/*/index');
        }

        try {
            $widget = $this->widgetRepository->saveWidgetData($data);
            $this->dataPersistor->clear('leispet_homepage_widget');
            $this->messageManager->addSuccessMessage(__('Widget saved successfully.'));

            return $this->resultRedirectFactory->create()->setPath('*/*/edit', ['widget_id' => (int)$widget->getId()]);
        } catch (\Throwable $e) {
            $this->dataPersistor->set('leispet_homepage_widget', $data);
            $this->messageManager->addErrorMessage(__('Failed to save widget: %1', $e->getMessage()));

            $widgetId = (int)($data['widget_id'] ?? 0);
            if ($widgetId > 0) {
                return $this->resultRedirectFactory->create()->setPath('*/*/edit', ['widget_id' => $widgetId]);
            }

            return $this->resultRedirectFactory->create()->setPath('*/*/new');
        }
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
