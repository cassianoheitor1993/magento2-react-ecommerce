<?php
namespace Petshop\Blog\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class AiJob extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('petshop_blog_ai_job', 'job_id');
    }
}
