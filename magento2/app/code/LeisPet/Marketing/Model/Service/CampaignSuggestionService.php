<?php

declare(strict_types=1);

namespace LeisPet\Marketing\Model\Service;

use LeisPet\Marketing\Model\Ai\CampaignSuggestionClient;
use Psr\Log\LoggerInterface;

class CampaignSuggestionService
{
    public function __construct(
        private readonly CampaignInsightsProvider $insightsProvider,
        private readonly CampaignSuggestionClient $suggestionClient,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param array<string, mixed> $editorContext
     * @return array{insights: array<string, mixed>, suggestions: array<int, array<string, mixed>>, source: string, fallback_reason: string}
     */
    public function getSuggestions(array $editorContext = []): array
    {
        $insights = $this->insightsProvider->getInsights();
        $source = 'ai';
        $fallbackReason = '';

        try {
            $suggestions = $this->suggestionClient->suggestCampaigns($insights, $editorContext);
            if (!$suggestions) {
                $source = 'fallback';
                $fallbackReason = 'AI returned no valid suggestions.';
            }
        } catch (\Throwable $e) {
            $this->logger->error('Unable to get AI campaign suggestions', ['exception' => $e]);
            $suggestions = [];
            $source = 'fallback';
            $fallbackReason = $e->getMessage();
        }

        if (!$suggestions) {
            $suggestions = $this->buildFallbackSuggestions($insights);
        }

        return [
            'insights' => $insights,
            'suggestions' => array_slice($suggestions, 0, 5),
            'source' => $source,
            'fallback_reason' => $fallbackReason,
        ];
    }

    /**
     * @param array<string, mixed> $insights
     * @return array<int, array<string, mixed>>
     */
    private function buildFallbackSuggestions(array $insights): array
    {
        $monthLabel = gmdate('F');
        $topProduct = (string) ($insights['top_selling_products_30d'][0]['name'] ?? 'Top store products');

        return [
            [
                'campaign_name' => sprintf('%s Favorites: Best-Sellers Spotlight', $monthLabel),
                'subject' => sprintf('Shop this month\'s customer favorites before they\'re gone'),
                'audience_type' => 'newsletter',
                'audience_filter_json' => [],
                'template_identifier' => '',
                'sender_name' => '',
                'sender_email' => '',
                'reason' => 'Uses social proof from recent sales to improve open and click rates.',
            ],
            [
                'campaign_name' => 'Smart Replenishment: Everyday Essentials',
                'subject' => 'Time to restock your pet essentials',
                'audience_type' => 'newsletter',
                'audience_filter_json' => [],
                'template_identifier' => '',
                'sender_name' => '',
                'sender_email' => '',
                'reason' => 'Supports recurring purchases and lifecycle retention.',
            ],
            [
                'campaign_name' => 'Wellness & Accessories Spotlight',
                'subject' => sprintf('Discover wellness picks, including %s', $topProduct),
                'audience_type' => 'newsletter',
                'audience_filter_json' => [],
                'template_identifier' => '',
                'sender_name' => '',
                'sender_email' => '',
                'reason' => 'Combines high-interest products with a care-focused narrative.',
            ],
            [
                'campaign_name' => sprintf('%s Seasonal Bundles for Pet Routines', $monthLabel),
                'subject' => sprintf('Seasonal bundles for simpler pet care this %s', $monthLabel),
                'audience_type' => 'newsletter',
                'audience_filter_json' => [],
                'template_identifier' => '',
                'sender_name' => '',
                'sender_email' => '',
                'reason' => 'Leverages seasonality and increases average order value via bundles.',
            ],
            [
                'campaign_name' => 'Last Chance: High-Demand Items',
                'subject' => 'Last chance: high-demand items with limited stock',
                'audience_type' => 'newsletter',
                'audience_filter_json' => [],
                'template_identifier' => '',
                'sender_name' => '',
                'sender_email' => '',
                'reason' => 'Creates urgency based on inventory pressure and demand trends.',
            ],
        ];
    }
}
