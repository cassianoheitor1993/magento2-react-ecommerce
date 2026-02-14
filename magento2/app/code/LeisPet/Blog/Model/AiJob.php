<?php
namespace LeisPet\Blog\Model;

use Magento\Framework\Model\AbstractModel;

class AiJob extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(\LeisPet\Blog\Model\ResourceModel\AiJob::class);
    }
}
