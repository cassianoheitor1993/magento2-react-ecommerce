<?php
namespace Petshop\Marketing\Model;

use Magento\Framework\Model\AbstractModel;

class Campaign extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(\Petshop\Marketing\Model\ResourceModel\Campaign::class);
    }
}