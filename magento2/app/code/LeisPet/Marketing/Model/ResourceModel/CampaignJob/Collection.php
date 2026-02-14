<?php
namespace LeisPet\Marketing\Model\ResourceModel\CampaignJob;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(\LeisPet\Marketing\Model\CampaignJob::class, \LeisPet\Marketing\Model\ResourceModel\CampaignJob::class);
    }
}