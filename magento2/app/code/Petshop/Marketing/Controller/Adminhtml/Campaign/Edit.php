<?php

declare(strict_types=1);

namespace Petshop\Marketing\Controller\Adminhtml\Campaign;

use Petshop\Marketing\Model\CampaignFactory;
use Petshop\Marketing\Model\ResourceModel\Campaign as CampaignResource;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\PageFactory;

class Edit extends Action
{
    public const ADMIN_RESOURCE = 'Petshop_Marketing::campaigns';

    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly CampaignFactory $campaignFactory,
        private readonly CampaignResource $campaignResource
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $campaignId = (int)$this->getRequest()->getParam('campaign_id');

        if ($campaignId > 0) {
            $campaign = $this->campaignFactory->create();
            $this->campaignResource->load($campaign, $campaignId);

            if (!$campaign->getId()) {
                throw new LocalizedException(__('This campaign no longer exists.'));
            }
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Petshop_Marketing::campaigns');
        $resultPage->getConfig()->getTitle()->prepend(
            $campaignId > 0 ? __('Edit Campaign #%1', $campaignId) : __('New Campaign')
        );

        return $resultPage;
    }
}
