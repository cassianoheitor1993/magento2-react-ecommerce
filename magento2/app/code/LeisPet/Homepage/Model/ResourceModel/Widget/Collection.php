<?php

declare(strict_types=1);

namespace LeisPet\Homepage\Model\ResourceModel\Widget;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct(): void
    {
        $this->_init(\LeisPet\Homepage\Model\Widget::class, \LeisPet\Homepage\Model\ResourceModel\Widget::class);
    }
}
