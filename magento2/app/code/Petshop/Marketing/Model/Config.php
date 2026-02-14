<?php

declare(strict_types=1);

namespace Petshop\Marketing\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Config
{
    private const XML_PATH_AI_ENABLED = 'petshop_marketing/ai/enabled';
    private const XML_PATH_AI_API_KEY = 'petshop_marketing/ai/api_key';
    private const XML_PATH_AI_MODEL = 'petshop_marketing/ai/model';
    private const XML_PATH_AI_BASE_URL = 'petshop_marketing/ai/base_url';

    public function __construct(private readonly ScopeConfigInterface $scopeConfig)
    {
    }

    public function isAiEnabled(): bool
    {
        return (bool) $this->scopeConfig->getValue(self::XML_PATH_AI_ENABLED);
    }

    public function getApiKey(): string
    {
        return (string) $this->scopeConfig->getValue(self::XML_PATH_AI_API_KEY);
    }

    public function getModel(): string
    {
        return (string) ($this->scopeConfig->getValue(self::XML_PATH_AI_MODEL) ?: 'deepseek-chat');
    }

    public function getBaseUrl(): string
    {
        return (string) ($this->scopeConfig->getValue(self::XML_PATH_AI_BASE_URL) ?: 'https://api.deepseek.com/v1/chat/completions');
    }
}
