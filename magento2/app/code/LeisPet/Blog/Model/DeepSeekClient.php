<?php
namespace LeisPet\Blog\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;

class DeepSeekClient
{
    public function __construct(
        private readonly Curl $curl,
        private readonly Config $config,
        private readonly LoggerInterface $logger
    ) {
    }

    public function generatePost(
        string $topic,
        string $petType,
        string $tone,
        ?string $title = null
    ): array {
        return $this->generatePostWithContext($topic, $petType, $tone, $title, []);
    }

    public function generatePostWithContext(
        string $topic,
        string $petType,
        string $tone,
        ?string $title = null,
        array $context = []
    ): array {
        if (!$this->config->isAiEnabled()) {
            throw new LocalizedException(__('DeepSeek integration is disabled in configuration.'));
        }

        $apiKey = trim($this->config->getApiKey());
        if ($apiKey === '') {
            throw new LocalizedException(__('DeepSeek API key is missing.'));
        }

        $prompt = $this->buildPrompt($topic, $petType, $tone, $title, $context);
        $response = $this->requestChat($apiKey, [
            [
                'role' => 'system',
                'content' => 'You are a professional pet-care content writer. Return only valid JSON.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ], 0.7, 'generate_post', [
            'topic' => $topic,
            'pet_type' => $petType,
            'tone' => $tone,
            'context' => $context
        ]);

        $messageContent = $response['choices'][0]['message']['content'] ?? '';
        $content = is_array($messageContent)
            ? $this->normalizeText($messageContent)
            : (string) $messageContent;
        if (!$content) {
            throw new LocalizedException(__('DeepSeek response is empty.'));
        }

        $decoded = $this->extractJson($content);
        if (!is_array($decoded)) {
            throw new LocalizedException(__('DeepSeek response could not be parsed into JSON.'));
        }

        return [
            'title' => $this->normalizeText($decoded['title'] ?? $title ?? 'Untitled Blog Post'),
            'excerpt' => $this->normalizeText($decoded['excerpt'] ?? ''),
            'content' => $this->normalizeText($decoded['content'] ?? ''),
            'tags' => $this->normalizeTags($decoded['tags'] ?? ''),
            'author' => $this->normalizeText($decoded['author'] ?? 'LeisPet AI Editor'),
            'is_published' => 1
        ];
    }

    public function suggestTopics(array $storeInsights, array $editorContext = []): array
    {
        if (!$this->config->isAiEnabled()) {
            throw new LocalizedException(__('DeepSeek integration is disabled in configuration.'));
        }

        $apiKey = trim($this->config->getApiKey());
        if ($apiKey === '') {
            throw new LocalizedException(__('DeepSeek API key is missing.'));
        }

        $prompt = sprintf(
            "Based on this pet store context and editor context, suggest exactly 6 blog topics. Return ONLY JSON with key 'topics' (array of objects with keys: title, reason, pet_type, tone). Store context: %s. Editor context: %s.",
            json_encode($storeInsights, JSON_UNESCAPED_UNICODE),
            json_encode($editorContext, JSON_UNESCAPED_UNICODE)
        );

        $response = $this->requestChat($apiKey, [
            [
                'role' => 'system',
                'content' => 'You are a strategic content planner for ecommerce pet stores. Return only valid JSON.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ], 0.6, 'suggest_topics', [
            'store_insights' => $storeInsights,
            'editor_context' => $editorContext
        ]);

        $messageContent = $response['choices'][0]['message']['content'] ?? '';
        $raw = is_array($messageContent) ? $this->normalizeText($messageContent) : (string) $messageContent;
        $decoded = $this->extractJson($raw);
        $topics = $decoded['topics'] ?? [];

        if (!is_array($topics)) {
            return [];
        }

        $normalized = [];
        foreach ($topics as $topic) {
            if (!is_array($topic)) {
                continue;
            }

            $title = $this->normalizeText($topic['title'] ?? '');
            if ($title === '') {
                continue;
            }

            $normalized[] = [
                'title' => $title,
                'reason' => $this->normalizeText($topic['reason'] ?? ''),
                'pet_type' => $this->normalizeText($topic['pet_type'] ?? 'all pets'),
                'tone' => $this->normalizeText($topic['tone'] ?? 'helpful and professional')
            ];
        }

        return array_slice($normalized, 0, 6);
    }

    public function regenerateField(
        string $field,
        array $context,
        string $petType = 'all pets',
        string $tone = 'helpful and professional'
    ): string {
        if (!$this->config->isAiEnabled()) {
            throw new LocalizedException(__('DeepSeek integration is disabled in configuration.'));
        }

        $apiKey = trim($this->config->getApiKey());
        if ($apiKey === '') {
            throw new LocalizedException(__('DeepSeek API key is missing.'));
        }

        $supported = ['title', 'excerpt', 'content', 'author', 'tags'];
        if (!in_array($field, $supported, true)) {
            throw new LocalizedException(__('Invalid field for AI regeneration.'));
        }

        $prompt = sprintf(
            "Regenerate only the '%s' field for a pet-store blog post. Return ONLY JSON with key '%s'. Keep it consistent with this context: %s. Pet type: %s. Tone: %s.",
            $field,
            $field,
            json_encode($context, JSON_UNESCAPED_UNICODE),
            $petType,
            $tone
        );

        $response = $this->requestChat($apiKey, [
            [
                'role' => 'system',
                'content' => 'You are a professional ecommerce content editor. Return only valid JSON.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ], 0.7, 'regenerate_field', [
            'field' => $field,
            'pet_type' => $petType,
            'tone' => $tone,
            'context' => $context
        ]);

        $messageContent = $response['choices'][0]['message']['content'] ?? '';
        $raw = is_array($messageContent) ? $this->normalizeText($messageContent) : (string) $messageContent;
        $decoded = $this->extractJson($raw);
        if (!is_array($decoded) || !array_key_exists($field, $decoded)) {
            throw new LocalizedException(__('DeepSeek did not return the requested field.'));
        }

        if ($field === 'tags') {
            return $this->normalizeTags($decoded[$field]);
        }

        return $this->normalizeText($decoded[$field]);
    }

    /**
     * Generic structured JSON generation using existing DeepSeek infrastructure.
     *
     * @return array<string, mixed>
     */
    public function generateStructuredJson(
        string $systemPrompt,
        string $userPrompt,
        float $temperature = 0.6,
        string $operation = 'structured_json'
    ): array {
        if (!$this->config->isAiEnabled()) {
            throw new LocalizedException(__('DeepSeek integration is disabled in configuration.'));
        }

        $apiKey = trim($this->config->getApiKey());
        if ($apiKey === '') {
            throw new LocalizedException(__('DeepSeek API key is missing.'));
        }

        $response = $this->requestChat($apiKey, [
            [
                'role' => 'system',
                'content' => $systemPrompt
            ],
            [
                'role' => 'user',
                'content' => $userPrompt
            ]
        ], $temperature, $operation, [
            'system_prompt' => $systemPrompt,
            'user_prompt' => $userPrompt
        ]);

        $messageContent = $response['choices'][0]['message']['content'] ?? '';
        $raw = is_array($messageContent) ? $this->normalizeText($messageContent) : (string) $messageContent;
        if ($raw === '') {
            throw new LocalizedException(__('DeepSeek response is empty.'));
        }

        $decoded = $this->extractJson($raw);
        if (!is_array($decoded)) {
            throw new LocalizedException(__('DeepSeek response could not be parsed into JSON.'));
        }

        return $decoded;
    }

    private function buildPrompt(string $topic, string $petType, string $tone, ?string $title, array $context = []): string
    {
        $contextJson = $context ? json_encode($context, JSON_UNESCAPED_UNICODE) : '{}';

        return sprintf(
            "Create a compelling Magento blog post as JSON with keys: title, excerpt, content, tags, author. Topic: %s. Pet type: %s. Tone: %s. %s Use HTML formatting in 'content' with h2/h3/ul/ol/strong/img tags. Keep the output aligned with this store/editor context JSON: %s. IMPORTANT: Never output placeholder text like [Default Store View], [Store Name], [Brand], [Category], or [Product]. Use concrete values from context JSON; if a concrete value is missing, omit the claim instead of using a placeholder.",
            $topic,
            $petType,
            $tone,
            $title ? 'Preferred title: ' . $title . '.' : '',
            $contextJson
        );
    }

    private function requestChat(
        string $apiKey,
        array $messages,
        float $temperature = 0.7,
        string $operation = 'chat',
        array $meta = []
    ): array
    {
        $payload = [
            'model' => $this->config->getModel(),
            'temperature' => $temperature,
            'messages' => $messages
        ];

        $this->logger->info('LeisPet Blog AI request payload', [
            'operation' => $operation,
            'meta' => $this->truncateForLog($meta),
            'payload' => $this->truncateForLog($payload)
        ]);

        $this->curl->addHeader('Content-Type', 'application/json');
        $this->curl->addHeader('Authorization', 'Bearer ' . $apiKey);
        $this->curl->post($this->config->getBaseUrl(), json_encode($payload));

        $status = (int) $this->curl->getStatus();
        $body = (string) $this->curl->getBody();

        $this->logger->info('LeisPet Blog AI response payload', [
            'operation' => $operation,
            'status' => $status,
            'body' => $this->truncateForLog($body)
        ]);

        if ($status >= 400) {
            throw new LocalizedException(__('DeepSeek request failed: %1', $body));
        }

        $response = json_decode($body, true);
        if (!is_array($response)) {
            throw new LocalizedException(__('Invalid JSON response received from DeepSeek.'));
        }

        return $response;
    }

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
            return (string) $value;
        }

        if (is_array($value)) {
            $parts = [];
            foreach ($value as $item) {
                if (is_array($item) && isset($item['text']) && (is_string($item['text']) || is_numeric($item['text']))) {
                    $parts[] = (string) $item['text'];
                    continue;
                }

                if (is_string($item) || is_numeric($item)) {
                    $parts[] = (string) $item;
                }
            }

            return trim(implode("\n", $parts));
        }

        return '';
    }

    private function normalizeTags(mixed $value): string
    {
        if (is_string($value) || is_numeric($value)) {
            return (string) $value;
        }

        if (is_array($value)) {
            $tags = [];
            foreach ($value as $tag) {
                if (is_string($tag) || is_numeric($tag)) {
                    $text = trim((string) $tag);
                    if ($text !== '') {
                        $tags[] = $text;
                    }
                }
            }

            return implode(', ', $tags);
        }

        return '';
    }

    private function truncateForLog(mixed $value, int $maxLength = 4000): mixed
    {
        if (is_string($value)) {
            if (strlen($value) <= $maxLength) {
                return $value;
            }

            return substr($value, 0, $maxLength) . '... [truncated]';
        }

        if (is_array($value)) {
            $result = [];
            foreach ($value as $key => $item) {
                $result[$key] = $this->truncateForLog($item, $maxLength);
            }

            return $result;
        }

        return $value;
    }
}
