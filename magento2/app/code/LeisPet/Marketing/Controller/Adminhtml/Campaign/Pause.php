<?php

namespace LeisPet\Marketing\Controller\Adminhtml\Campaign;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use LeisPet\Marketing\Api\CampaignRepositoryInterface;

class Pause extends Action
{
    public const ADMIN_RESOURCE = 'LeisPet_Marketing::campaigns';

    public function __construct(
        Context $context,
        private readonly JsonFactory $jsonFactory,
        private readonly CampaignRepositoryInterface $campaignRepository
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $isAjax = $this->getRequest()->isXmlHttpRequest();
        $result = $this->jsonFactory->create();
        $campaignId = (int) $this->getRequest()->getParam('campaign_id');

        try {
            $this->campaignRepository->pauseCampaign($campaignId);
            if ($isAjax) {
                return $result->setData([
                    'success' => true,
                    'message' => __('Campaign paused successfully')
                ]);
            }

            $this->messageManager->addSuccessMessage(__('Campaign paused successfully.'));
            return $this->resultRedirectFactory->create()->setPath('*/*/edit', ['campaign_id' => $campaignId]);
        } catch (\Throwable $e) {
            if ($isAjax) {
                return $result->setData([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }

            $this->messageManager->addErrorMessage(__('Failed to pause campaign: %1', $e->getMessage()));
            return $this->resultRedirectFactory->create()->setPath('*/*/edit', ['campaign_id' => $campaignId]);
        }
    }
}