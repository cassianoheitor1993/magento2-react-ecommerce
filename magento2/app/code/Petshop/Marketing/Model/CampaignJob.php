<?php
namespace Petshop\Marketing\Model;

use Magento\Framework\Model\AbstractModel;

class CampaignJob extends AbstractModel
{
	protected function _construct()
	{
		$this->_init(\Petshop\Marketing\Model\ResourceModel\CampaignJob::class);
	}
}
