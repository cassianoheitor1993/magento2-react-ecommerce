<?php
namespace LeisPet\Marketing\Model\ResourceModel\Campaign;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(\LeisPet\Marketing\Model\Campaign::class, \LeisPet\Marketing\Model\ResourceModel\Campaign::class);
    }
}