<?php
namespace LeisPet\Marketing\Model;

use Magento\Framework\Model\AbstractModel;

class Campaign extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(\LeisPet\Marketing\Model\ResourceModel\Campaign::class);
    }
}