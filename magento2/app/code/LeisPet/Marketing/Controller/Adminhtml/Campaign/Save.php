<?php

namespace LeisPet\Marketing\Controller\Adminhtml\Campaign;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use LeisPet\Marketing\Api\CampaignRepositoryInterface;

class Save extends Action
{
    public const ADMIN_RESOURCE = 'LeisPet_Marketing::campaigns';

    public function __construct(
        Context $context,
        private readonly JsonFactory $jsonFactory,
        private readonly CampaignRepositoryInterface $campaignRepository,
        private readonly DataPersistorInterface $dataPersistor
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $isAjax = $this->getRequest()->isXmlHttpRequest();
        $result = $this->jsonFactory->create();
        $data = $this->normalizePostData((array)$this->getRequest()->getPostValue());

        if (empty($data)) {
            if ($isAjax) {
                return $result->setData([
                    'success' => false,
                    'error' => __('No data received.')
                ]);
            }

            return $this->resultRedirectFactory->create()->setPath('*/*/index');
        }

        if (!isset($data['campaign_id']) || !$data['campaign_id']) {
            $data['campaign_id'] = (int)$this->getRequest()->getParam('campaign_id');
        }

        try {
            $campaign = $this->campaignRepository->saveCampaignData($data);
            $this->dataPersistor->clear('leispet_marketing_campaign');

            if ($isAjax) {
                return $result->setData([
                    'success' => true,
                    'campaign_id' => $campaign->getId(),
                    'status' => $campaign->getData('status'),
                    'total_recipients' => $campaign->getData('total_recipients'),
                    'processed_count' => $campaign->getData('processed_count'),
                    'sent_count' => $campaign->getData('sent_count'),
                    'failed_count' => $campaign->getData('failed_count')
                ]);
            }

            $this->messageManager->addSuccessMessage(__('Campaign saved successfully.'));
            return $this->resultRedirectFactory->create()->setPath('*/*/edit', ['campaign_id' => (int)$campaign->getId()]);
        } catch (\Throwable $e) {
            if ($isAjax) {
                return $result->setData([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }

            $this->dataPersistor->set('leispet_marketing_campaign', $data);
            $this->messageManager->addErrorMessage(__('Failed to save campaign: %1', $e->getMessage()));

            $campaignId = (int)($data['campaign_id'] ?? 0);
            if ($campaignId > 0) {
                return $this->resultRedirectFactory->create()->setPath('*/*/edit', ['campaign_id' => $campaignId]);
            }

            return $this->resultRedirectFactory->create()->setPath('*/*/new');
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function normalizePostData(array $payload): array
    {
        if (isset($payload['data']) && is_array($payload['data'])) {
            $payload = $payload['data'];
        }

        if (isset($payload['general']) && is_array($payload['general'])) {
            $payload = array_merge($payload, $payload['general']);
            unset($payload['general']);
        }

        return $payload;
    }
}