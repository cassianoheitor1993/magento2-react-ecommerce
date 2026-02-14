<?php
namespace Petshop\Blog\Model;

use Magento\Framework\Model\AbstractModel;

class AiJob extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(\Petshop\Blog\Model\ResourceModel\AiJob::class);
    }
}
