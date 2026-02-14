<?php

namespace LeisPet\Marketing\Controller\Adminhtml\Campaign;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use LeisPet\Marketing\Api\CampaignRepositoryInterface;

class Status extends Action
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
        $result = $this->jsonFactory->create();
        $campaignId = (int) $this->getRequest()->getParam('campaign_id');

        try {
            $statusData = $this->campaignRepository->getCampaignStatus($campaignId);
            return $result->setData(array_merge([
                'success' => true,
                'message' => __('Campaign status retrieved successfully')
            ], $statusData));
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}