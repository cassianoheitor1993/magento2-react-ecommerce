<?php

declare(strict_types=1);

namespace LeisPet\Homepage\Block\Adminhtml\Widget;

use Magento\Backend\Block\Template;

class CreateModal extends Template
{
    public function getConfigJson(): string
    {
        return (string)json_encode([
            'formKey' => $this->formKey->getFormKey(),
            'urls' => [
                'create' => $this->getUrl('leispet_homepage/widget/create'),
                'update' => $this->getUrl('leispet_homepage/widget/update'),
                'getWidget' => $this->getUrl('leispet_homepage/widget/getwidget'),
                'generate' => $this->getUrl('leispet_homepage/widget/aigenerate'),
                'validateSchedule' => $this->getUrl('leispet_homepage/widget/validateschedule'),
                'getSchema' => $this->getUrl('leispet_homepage/widget/getschema')
            ]
        ]);
    }
}
