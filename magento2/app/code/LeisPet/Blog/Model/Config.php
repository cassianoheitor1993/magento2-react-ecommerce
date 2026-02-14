<?php
namespace LeisPet\Blog\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Config
{
    private const XML_PATH_ENABLED = 'leispet_blog/ai/enabled';
    private const XML_PATH_API_KEY = 'leispet_blog/ai/api_key';
    private const XML_PATH_MODEL = 'leispet_blog/ai/model';
    private const XML_PATH_BASE_URL = 'leispet_blog/ai/base_url';

    public function __construct(private readonly ScopeConfigInterface $scopeConfig)
    {
    }

    public function isAiEnabled(): bool
    {
        return (bool) $this->scopeConfig->getValue(self::XML_PATH_ENABLED);
    }

    public function getApiKey(): string
    {
        return (string) $this->scopeConfig->getValue(self::XML_PATH_API_KEY);
    }

    public function getModel(): string
    {
        return (string) ($this->scopeConfig->getValue(self::XML_PATH_MODEL) ?: 'deepseek-chat');
    }

    public function getBaseUrl(): string
    {
        return (string) ($this->scopeConfig->getValue(self::XML_PATH_BASE_URL) ?: 'https://api.deepseek.com/v1/chat/completions');
    }
}
