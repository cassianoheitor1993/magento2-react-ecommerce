<?php
namespace LeisPet\Marketing\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Campaign extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('leispet_marketing_campaign', 'campaign_id');
    }
}