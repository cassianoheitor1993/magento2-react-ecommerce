<?php

declare(strict_types=1);

namespace Petshop\Marketing\Controller\Adminhtml\Campaign;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

class NewAction extends Action
{
    public const ADMIN_RESOURCE = 'Petshop_Marketing::campaigns';

    public function __construct(Context $context)
    {
        parent::__construct($context);
    }

    public function execute()
    {
        return $this->resultRedirectFactory->create()->setPath('*/*/edit');
    }
}
