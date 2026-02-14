<?php

declare(strict_types=1);

namespace Petshop\Marketing\Block\Adminhtml\Campaign\Edit;

use Petshop\Marketing\Model\Config;
use Magento\Backend\Block\Template;

class AiAssistant extends Template
{
    public function __construct(
        Template\Context $context,
        private readonly Config $config,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getConfigJson(): string
    {
        return (string) json_encode([
            'enabled' => $this->config->isAiEnabled(),
            'campaignId' => (int) $this->getRequest()->getParam('campaign_id'),
            'formKey' => $this->formKey->getFormKey(),
            'urls' => [
                'suggestions' => $this->getUrl('petshop_marketing/campaign/aisuggestions'),
            ]
        ]);
    }
}
