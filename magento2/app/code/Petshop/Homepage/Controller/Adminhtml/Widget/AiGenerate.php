<?php

declare(strict_types=1);

namespace Petshop\Homepage\Controller\Adminhtml\Widget;

use Petshop\Homepage\Model\Service\WidgetAiGenerationService;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class AiGenerate extends Action
{
    public const ADMIN_RESOURCE = 'Petshop_Homepage::widgets';

    public function __construct(
        Context $context,
        private readonly JsonFactory $jsonFactory,
        private readonly WidgetAiGenerationService $generationService
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();

        try {
            $widgetType = (string)$this->getRequest()->getParam('widget_type', '');
            $context = (string)$this->getRequest()->getParam('context', '');

            if ($widgetType === '') {
                return $result->setData([
                    'success' => false,
                    'message' => __('Widget type is required.')
                ]);
            }

            $generation = $this->generationService->generateWithMeta($widgetType, $context);
            $payload = $generation['payload'] ?? [];
            $source = (string)($generation['source'] ?? 'fallback');

            return $result->setData([
                'success' => true,
                'widget_type' => $widgetType,
                'config_json' => (string)json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                'source' => $source
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => __('Unable to generate widget content: %1', $e->getMessage())
            ]);
        }
    }
}
