<?php

declare(strict_types=1);

namespace LeisPet\Homepage\Model;

use Magento\Framework\Model\AbstractModel;

class Widget extends AbstractModel
{
    protected function _construct(): void
    {
        $this->_init(\LeisPet\Homepage\Model\ResourceModel\Widget::class);
    }
}
