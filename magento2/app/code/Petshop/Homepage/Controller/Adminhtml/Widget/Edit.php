<?php

declare(strict_types=1);

namespace Petshop\Homepage\Controller\Adminhtml\Widget;

use Petshop\Homepage\Model\ResourceModel\Widget as WidgetResource;
use Petshop\Homepage\Model\WidgetFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\PageFactory;

class Edit extends Action
{
    public const ADMIN_RESOURCE = 'Petshop_Homepage::widgets';

    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly WidgetFactory $widgetFactory,
        private readonly WidgetResource $widgetResource
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $widgetId = (int)$this->getRequest()->getParam('widget_id');

        if ($widgetId > 0) {
            $widget = $this->widgetFactory->create();
            $this->widgetResource->load($widget, $widgetId);
            if (!$widget->getId()) {
                throw new LocalizedException(__('This widget no longer exists.'));
            }
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Petshop_Homepage::widgets');
        $resultPage->getConfig()->getTitle()->prepend(
            $widgetId > 0 ? __('Edit Widget #%1', $widgetId) : __('New Widget')
        );

        return $resultPage;
    }
}
