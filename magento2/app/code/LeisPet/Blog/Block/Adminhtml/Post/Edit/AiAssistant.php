<?php
namespace LeisPet\Blog\Block\Adminhtml\Post\Edit;

use LeisPet\Blog\Model\Config;
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

    public function isEnabled(): bool
    {
        return $this->config->isAiEnabled();
    }

    public function getConfigJson(): string
    {
        $payload = [
            'enabled' => $this->isEnabled(),
            'postId' => (int) $this->getRequest()->getParam('post_id'),
            'formKey' => $this->formKey->getFormKey(),
            'urls' => [
                'context' => $this->getUrl('leispet_blog/post/aicontext'),
                'enqueue' => $this->getUrl('leispet_blog/post/aienqueue'),
                'process' => $this->getUrl('leispet_blog/post/aiprocess'),
                'status' => $this->getUrl('leispet_blog/post/aistatus')
            ]
        ];

        return (string) json_encode($payload);
    }
}
