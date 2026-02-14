<?php

declare(strict_types=1);

namespace LeisPet\Homepage\Block\Adminhtml\Widget\Edit;

use Magento\Backend\Block\Template;

class AiAssistant extends Template
{
    public function getConfigJson(): string
    {
        return (string)json_encode([
            'formKey' => $this->formKey->getFormKey(),
            'urls' => [
                'generate' => $this->getUrl('leispet_homepage/widget/aigenerate'),
                'getSchema' => $this->getUrl('leispet_homepage/widget/getschema')
            ]
        ]);
    }
}
