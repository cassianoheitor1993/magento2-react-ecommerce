<?php
namespace Petshop\Blog\Model\ResourceModel\Post;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \Petshop\Blog\Model\Post::class,
            \Petshop\Blog\Model\ResourceModel\Post::class
        );
    }
}