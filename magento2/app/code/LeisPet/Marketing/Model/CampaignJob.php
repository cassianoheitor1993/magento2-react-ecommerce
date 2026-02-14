<?php
namespace LeisPet\Marketing\Model;

use Magento\Framework\Model\AbstractModel;

class CampaignJob extends AbstractModel
{
	protected function _construct()
	{
		$this->_init(\LeisPet\Marketing\Model\ResourceModel\CampaignJob::class);
	}
}
