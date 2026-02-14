<?php

declare(strict_types=1);

namespace Petshop\Homepage\Controller\Adminhtml\Widget;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    public const ADMIN_RESOURCE = 'Petshop_Homepage::widgets';

    public function __construct(Context $context, private readonly PageFactory $resultPageFactory)
    {
        parent::__construct($context);
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Petshop_Homepage::widgets');
        $resultPage->getConfig()->getTitle()->prepend(__('Homepage Widgets'));

        return $resultPage;
    }
}
