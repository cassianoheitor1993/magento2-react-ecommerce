<?php
namespace LeisPet\Blog\Model\ResourceModel\AiJob;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \LeisPet\Blog\Model\AiJob::class,
            \LeisPet\Blog\Model\ResourceModel\AiJob::class
        );
    }
}
