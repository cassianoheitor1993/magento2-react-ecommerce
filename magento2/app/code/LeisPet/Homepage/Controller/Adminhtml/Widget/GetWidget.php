<?php

declare(strict_types=1);

namespace LeisPet\Homepage\Controller\Adminhtml\Widget;

use LeisPet\Homepage\Model\ResourceModel\Widget as WidgetResource;
use LeisPet\Homepage\Model\WidgetFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * AJAX endpoint that returns a single widget's full data by ID.
 * Used by the edit-via-modal flow on the listing page.
 */
class GetWidget extends Action
{
    public const ADMIN_RESOURCE = 'LeisPet_Homepage::widgets';

    public function __construct(
        Context $context,
        private readonly JsonFactory $jsonFactory,
        private readonly WidgetFactory $widgetFactory,
        private readonly WidgetResource $widgetResource
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();

        try {
            $widgetId = (int)$this->getRequest()->getParam('widget_id', 0);

            if ($widgetId <= 0) {
                return $result->setData([
                    'success' => false,
                    'message' => (string)__('Widget ID is required.')
                ]);
            }

            $widget = $this->widgetFactory->create();
            $this->widgetResource->load($widget, $widgetId);

            if (!$widget->getId()) {
                return $result->setData([
                    'success' => false,
                    'message' => (string)__('Widget with ID %1 does not exist.', $widgetId)
                ]);
            }

            return $result->setData([
                'success' => true,
                'widget' => [
                    'widget_id'   => (int)$widget->getId(),
                    'widget_type' => (string)$widget->getData('widget_type'),
                    'title'       => (string)$widget->getData('title'),
                    'is_active'   => (int)$widget->getData('is_active'),
                    'sort_order'  => (int)$widget->getData('sort_order'),
                    'config_json' => (string)$widget->getData('config_json'),
                    'starts_at'   => (string)($widget->getData('starts_at') ?? ''),
                    'ends_at'     => (string)($widget->getData('ends_at') ?? ''),
                ]
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => (string)__('Failed to load widget: %1', $e->getMessage())
            ]);
        }
    }
}
