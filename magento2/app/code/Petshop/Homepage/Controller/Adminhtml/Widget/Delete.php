<?php

declare(strict_types=1);

namespace Petshop\Homepage\Controller\Adminhtml\Widget;

use Petshop\Homepage\Api\WidgetRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

class Delete extends Action
{
    public const ADMIN_RESOURCE = 'Petshop_Homepage::widgets';

    public function __construct(
        Context $context,
        private readonly WidgetRepositoryInterface $widgetRepository
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $widgetId = (int)$this->getRequest()->getParam('widget_id');

        if ($widgetId <= 0) {
            $this->messageManager->addErrorMessage(__('Unable to find widget to delete.'));
            return $this->resultRedirectFactory->create()->setPath('*/*/index');
        }

        try {
            $this->widgetRepository->deleteById($widgetId);
            $this->messageManager->addSuccessMessage(__('Widget deleted successfully.'));
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage(__('Failed to delete widget: %1', $e->getMessage()));
        }

        return $this->resultRedirectFactory->create()->setPath('*/*/index');
    }
}
