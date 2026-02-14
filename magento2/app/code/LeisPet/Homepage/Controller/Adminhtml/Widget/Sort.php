<?php

declare(strict_types=1);

namespace LeisPet\Homepage\Controller\Adminhtml\Widget;

use LeisPet\Homepage\Api\WidgetRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

class Sort extends Action
{
    public const ADMIN_RESOURCE = 'LeisPet_Homepage::widgets';

    public function __construct(
        Context $context,
        private readonly WidgetRepositoryInterface $widgetRepository
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $widgetId = (int)$this->getRequest()->getParam('widget_id');
        $direction = (string)$this->getRequest()->getParam('direction', 'up');

        if ($widgetId <= 0 || !in_array($direction, ['up', 'down'], true)) {
            $this->messageManager->addErrorMessage(__('Invalid sort request.'));
            return $this->resultRedirectFactory->create()->setPath('*/*/index');
        }

        try {
            $this->widgetRepository->moveWidget($widgetId, $direction);
            $this->messageManager->addSuccessMessage(__('Widget order updated.'));
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage(__('Failed to update order: %1', $e->getMessage()));
        }

        return $this->resultRedirectFactory->create()->setPath('*/*/index');
    }
}
