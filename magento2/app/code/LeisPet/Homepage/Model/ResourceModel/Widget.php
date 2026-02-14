<?php

declare(strict_types=1);

namespace LeisPet\Homepage\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Widget extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('leispet_homepage_widget', 'widget_id');
    }
}
