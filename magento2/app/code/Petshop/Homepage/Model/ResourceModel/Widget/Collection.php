<?php

declare(strict_types=1);

namespace Petshop\Homepage\Model\ResourceModel\Widget;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct(): void
    {
        $this->_init(\Petshop\Homepage\Model\Widget::class, \Petshop\Homepage\Model\ResourceModel\Widget::class);
    }
}
