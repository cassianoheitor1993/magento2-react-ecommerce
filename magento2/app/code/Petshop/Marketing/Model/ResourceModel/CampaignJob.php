<?php
namespace Petshop\Marketing\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class CampaignJob extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('petshop_marketing_campaign_job', 'job_id');
    }
}
