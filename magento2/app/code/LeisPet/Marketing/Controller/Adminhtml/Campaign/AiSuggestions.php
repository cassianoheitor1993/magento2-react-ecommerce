<?php

declare(strict_types=1);

namespace LeisPet\Marketing\Controller\Adminhtml\Campaign;

use LeisPet\Marketing\Model\Service\CampaignSuggestionService;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class AiSuggestions extends Action
{
    public const ADMIN_RESOURCE = 'LeisPet_Marketing::campaigns';

    public function __construct(
        Context $context,
        private readonly JsonFactory $jsonFactory,
        private readonly CampaignSuggestionService $campaignSuggestionService
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();

        try {
            $editorContext = (array) $this->getRequest()->getParam('editor_context', []);
            $payload = $this->campaignSuggestionService->getSuggestions($editorContext);

            return $result->setData([
                'success' => true,
                'suggestions' => $payload['suggestions'],
                'insights' => $payload['insights'],
                'source' => $payload['source'] ?? 'ai',
                'fallback_reason' => $payload['fallback_reason'] ?? '',
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => (string) __('Unable to load AI suggestions: %1', $e->getMessage()),
            ]);
        }
    }
}
