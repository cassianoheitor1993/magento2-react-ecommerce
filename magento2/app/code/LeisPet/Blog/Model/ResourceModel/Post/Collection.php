<?php
namespace LeisPet\Blog\Model\ResourceModel\Post;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \LeisPet\Blog\Model\Post::class,
            \LeisPet\Blog\Model\ResourceModel\Post::class
        );
    }
}