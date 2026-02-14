<?php

declare(strict_types=1);

namespace Petshop\Homepage\Block\Adminhtml\Widget;

use Magento\Backend\Block\Template;

class CreateModal extends Template
{
    public function getConfigJson(): string
    {
        return (string)json_encode([
            'formKey' => $this->formKey->getFormKey(),
            'urls' => [
                'create' => $this->getUrl('petshop_homepage/widget/create'),
                'update' => $this->getUrl('petshop_homepage/widget/update'),
                'getWidget' => $this->getUrl('petshop_homepage/widget/getwidget'),
                'generate' => $this->getUrl('petshop_homepage/widget/aigenerate'),
                'validateSchedule' => $this->getUrl('petshop_homepage/widget/validateschedule'),
                'getSchema' => $this->getUrl('petshop_homepage/widget/getschema')
            ]
        ]);
    }
}
