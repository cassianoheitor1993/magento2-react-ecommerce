<?php
namespace LeisPet\Blog\Model;

use Magento\Framework\Model\AbstractModel;

class Post extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(\LeisPet\Blog\Model\ResourceModel\Post::class);
    }
}