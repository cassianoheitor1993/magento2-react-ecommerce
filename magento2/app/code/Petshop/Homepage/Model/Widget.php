<?php

declare(strict_types=1);

namespace Petshop\Homepage\Model;

use Magento\Framework\Model\AbstractModel;

class Widget extends AbstractModel
{
    protected function _construct(): void
    {
        $this->_init(\Petshop\Homepage\Model\ResourceModel\Widget::class);
    }
}
