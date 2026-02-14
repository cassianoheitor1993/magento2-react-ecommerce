<?php
namespace Petshop\Blog\Model\ResourceModel\AiJob;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \Petshop\Blog\Model\AiJob::class,
            \Petshop\Blog\Model\ResourceModel\AiJob::class
        );
    }
}
