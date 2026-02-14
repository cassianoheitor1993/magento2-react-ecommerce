<?php
namespace LeisPet\Blog\Controller\Adminhtml\Post;

use LeisPet\Blog\Model\DeepSeekClient;
use LeisPet\Blog\Model\StoreInsightsProvider;
use Magento\Framework\Controller\Result\JsonFactory;

class AiContext extends AbstractPost
{
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        private readonly JsonFactory $jsonFactory,
        private readonly StoreInsightsProvider $storeInsightsProvider,
        private readonly DeepSeekClient $deepSeekClient
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $this->releaseSessionLock();
        $result = $this->jsonFactory->create();

        try {
            $editorContext = (array) $this->getRequest()->getParam('editor_context', []);
            $storeInsights = $this->storeInsightsProvider->getInsights();
            $topics = $this->deepSeekClient->suggestTopics($storeInsights, $editorContext);

            if (!$topics) {
                $topics = [
                    [
                        'title' => 'Como montar uma rotina alimentar saudável para pets',
                        'reason' => 'Conecta orientação prática com recorrência de compras.',
                        'pet_type' => 'dogs and cats',
                        'tone' => 'helpful and professional'
                    ],
                    [
                        'title' => 'Top erros na escolha de ração e como evitar',
                        'reason' => 'Educativo e com alto potencial de busca orgânica.',
                        'pet_type' => 'all pets',
                        'tone' => 'educational and friendly'
                    ]
                ];
            }

            return $result->setData([
                'success' => true,
                'topics' => $topics,
                'store_insights' => $storeInsights
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => (string) __('Unable to load AI context: %1', $e->getMessage())
            ]);
        }
    }
}
