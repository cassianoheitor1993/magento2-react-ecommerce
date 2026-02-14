<?php
namespace Petshop\Marketing\Model\ResourceModel\CampaignJob;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(\Petshop\Marketing\Model\CampaignJob::class, \Petshop\Marketing\Model\ResourceModel\CampaignJob::class);
    }
}