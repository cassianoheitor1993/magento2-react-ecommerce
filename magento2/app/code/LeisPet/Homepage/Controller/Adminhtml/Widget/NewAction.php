<?php

declare(strict_types=1);

namespace LeisPet\Homepage\Controller\Adminhtml\Widget;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

class NewAction extends Action
{
    public const ADMIN_RESOURCE = 'LeisPet_Homepage::widgets';

    public function __construct(Context $context)
    {
        parent::__construct($context);
    }

    public function execute()
    {
        return $this->resultRedirectFactory->create()->setPath('*/*/edit');
    }
}
