<?php

declare(strict_types=1);

namespace LeisPet\Marketing\Model\Ai;

use LeisPet\Marketing\Model\Config;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;

class CampaignSuggestionClient
{
    public function __construct(
        private readonly Curl $curl,
        private readonly Config $config,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param array<string, mixed> $insights
     * @param array<string, mixed> $editorContext
     * @return array<int, array<string, mixed>>
     */
    public function suggestCampaigns(array $insights, array $editorContext = []): array
    {
        if (!$this->config->isAiEnabled()) {
            return [];
        }

        $apiKey = trim($this->config->getApiKey());
        if ($apiKey === '') {
            throw new LocalizedException(__('AI API key is missing in LeisPet Marketing configuration.'));
        }

        $compactInsights = $this->buildCompactInsights($insights);

        $prompt = sprintf(
            "Today is %s. Based on this ecommerce store context and current campaign draft context, suggest exactly 5 email campaigns. Respond in ENGLISH (US) only. Return ONLY valid JSON using this schema: {\"suggestions\":[{\"campaign_name\":\"\",\"subject\":\"\",\"audience_type\":\"newsletter\",\"audience_filter_json\":{},\"template_identifier\":\"\",\"sender_name\":\"\",\"sender_email\":\"\",\"reason\":\"\"}]}. Keep suggestions practical and seasonal for the current date.",
            gmdate('Y-m-d'),
            json_encode($compactInsights, JSON_UNESCAPED_UNICODE) . ' | Draft context: ' . json_encode($editorContext, JSON_UNESCAPED_UNICODE)
        );

        $response = $this->requestChat($apiKey, [
            [
                'role' => 'system',
                'content' => 'You are a senior ecommerce CRM strategist. Always write in English (US). Return only valid JSON.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ]);

        $content = (string) ($response['choices'][0]['message']['content'] ?? '');
        $decoded = $this->extractJson($content);
        if (!is_array($decoded)) {
            throw new LocalizedException(__('AI response could not be parsed into JSON.'));
        }

        $suggestions = $decoded['suggestions'] ?? [];
        if (!is_array($suggestions)) {
            return [];
        }

        $normalized = [];
        foreach ($suggestions as $item) {
            if (!is_array($item)) {
                continue;
            }

            $campaignName = $this->normalizeText($item['campaign_name'] ?? '');
            $subject = $this->normalizeText($item['subject'] ?? '');
            if ($campaignName === '' || $subject === '') {
                continue;
            }

            $audienceType = $this->normalizeText($item['audience_type'] ?? 'newsletter');
            if ($audienceType === '') {
                $audienceType = 'newsletter';
            }

            $audienceFilter = $item['audience_filter_json'] ?? new \stdClass();
            if (is_string($audienceFilter)) {
                $decodedFilter = json_decode($audienceFilter, true);
                $audienceFilter = is_array($decodedFilter) ? $decodedFilter : [];
            }
            if (!is_array($audienceFilter)) {
                $audienceFilter = [];
            }

            $normalized[] = [
                'campaign_name' => $campaignName,
                'subject' => $subject,
                'audience_type' => $audienceType,
                'audience_filter_json' => $audienceFilter,
                'template_identifier' => $this->normalizeText($item['template_identifier'] ?? ''),
                'sender_name' => $this->normalizeText($item['sender_name'] ?? ''),
                'sender_email' => $this->normalizeText($item['sender_email'] ?? ''),
                'reason' => $this->normalizeText($item['reason'] ?? ''),
            ];
        }

        return array_slice($normalized, 0, 5);
    }

    /**
     * @param array<int, array<string, string>> $messages
     * @return array<string, mixed>
     */
    private function requestChat(string $apiKey, array $messages): array
    {
        $payload = [
            'model' => $this->config->getModel(),
            'temperature' => 0.6,
            'messages' => $messages,
        ];

        $maxAttempts = 2;
        $lastError = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $this->curl->setOption(CURLOPT_CONNECTTIMEOUT, 10);
                $this->curl->setOption(CURLOPT_TIMEOUT, 45);
                $this->curl->addHeader('Content-Type', 'application/json');
                $this->curl->addHeader('Authorization', 'Bearer ' . $apiKey);
                $this->curl->post($this->config->getBaseUrl(), (string) json_encode($payload));

                $status = (int) $this->curl->getStatus();
                $body = (string) $this->curl->getBody();

                if ($status >= 500 && $attempt < $maxAttempts) {
                    $this->logger->warning('AI request failed with server error, retrying', [
                        'status' => $status,
                        'attempt' => $attempt,
                    ]);
                    usleep(300000);
                    continue;
                }

                if ($status >= 400) {
                    throw new LocalizedException(__('AI request failed: %1', $body));
                }

                $decoded = json_decode($body, true);
                if (!is_array($decoded)) {
                    $this->logger->error('LeisPet Marketing AI response is not valid JSON', ['body' => mb_substr($body, 0, 2000)]);
                    throw new LocalizedException(__('Invalid JSON response received from AI provider.'));
                }

                return $decoded;
            } catch (\Throwable $e) {
                $lastError = $e;
                if ($attempt < $maxAttempts) {
                    $this->logger->warning('AI request transport error, retrying', [
                        'attempt' => $attempt,
                        'message' => $e->getMessage(),
                    ]);
                    usleep(300000);
                    continue;
                }
            }
        }

        if ($lastError instanceof LocalizedException) {
            throw $lastError;
        }

        throw new LocalizedException(__('AI request failed: %1', $lastError ? $lastError->getMessage() : 'Unknown error'));
    }

    /**
     * @param array<string, mixed> $insights
     * @return array<string, mixed>
     */
    private function buildCompactInsights(array $insights): array
    {
        $productHighlights = is_array($insights['product_highlights'] ?? null)
            ? array_slice($insights['product_highlights'], 0, 6)
            : [];

        $topSelling = is_array($insights['top_selling_products_30d'] ?? null)
            ? array_slice($insights['top_selling_products_30d'], 0, 5)
            : [];

        return [
            'generated_at_utc' => $insights['generated_at_utc'] ?? gmdate('Y-m-d H:i:s'),
            'store_name' => $insights['store_name'] ?? '',
            'sales_summary_30d' => $insights['sales_summary_30d'] ?? [],
            'inventory_summary' => $insights['inventory_summary'] ?? [],
            'product_highlights' => $productHighlights,
            'top_selling_products_30d' => $topSelling,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function extractJson(string $raw): ?array
    {
        $raw = trim($raw);
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $raw, $match)) {
            $decoded = json_decode($match[0], true);
            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }

    private function normalizeText(mixed $value): string
    {
        if (is_string($value) || is_numeric($value)) {
            return trim((string) $value);
        }

        return '';
    }
}
