<?php

declare(strict_types=1);

namespace Petshop\Homepage\Block\Adminhtml\Widget\Edit;

use Magento\Backend\Block\Template;

class AiAssistant extends Template
{
    public function getConfigJson(): string
    {
        return (string)json_encode([
            'formKey' => $this->formKey->getFormKey(),
            'urls' => [
                'generate' => $this->getUrl('petshop_homepage/widget/aigenerate'),
                'getSchema' => $this->getUrl('petshop_homepage/widget/getschema')
            ]
        ]);
    }
}
