<?php

declare(strict_types=1);

namespace Petshop\Homepage\Controller\Adminhtml\Widget;

use Petshop\Homepage\Model\Config\WidgetTypeSchemaRegistry;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class GetSchema extends Action
{
    public const ADMIN_RESOURCE = 'Petshop_Homepage::widgets';

    public function __construct(
        Context $context,
        private readonly JsonFactory $jsonFactory,
        private readonly WidgetTypeSchemaRegistry $schemaRegistry
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();

        try {
            $widgetType = trim((string)$this->getRequest()->getParam('widget_type', ''));

            if ($widgetType === '') {
                return $result->setData([
                    'success' => false,
                    'message' => __('Widget type is required.')
                ]);
            }

            $schema = $this->schemaRegistry->getSchema($widgetType);

            if (empty($schema)) {
                return $result->setData([
                    'success' => false,
                    'message' => __('Unknown widget type: %1', $widgetType)
                ]);
            }

            return $result->setData([
                'success' => true,
                'widget_type' => $widgetType,
                'schema' => $schema
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => __('Unable to load schema: %1', $e->getMessage())
            ]);
        }
    }
}
