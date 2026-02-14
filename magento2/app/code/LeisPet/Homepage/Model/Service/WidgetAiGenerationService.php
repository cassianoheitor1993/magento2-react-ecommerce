<?php

declare(strict_types=1);

namespace LeisPet\Homepage\Model\Service;

use LeisPet\Blog\Model\DeepSeekClient;
use LeisPet\Homepage\Model\Service\StoreContextProvider;
use Psr\Log\LoggerInterface;

class WidgetAiGenerationService
{
    public function __construct(
        private readonly DeepSeekClient $deepSeekClient,
        private readonly StoreContextProvider $storeContextProvider,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @return array{payload: array<string, mixed>, source: string}
     */
    public function generateWithMeta(string $widgetType, string $context = ''): array
    {
        $context = trim($context);

        try {
            $aiPayload = $this->deepSeekClient->generateStructuredJson(
                $this->getSystemPrompt($widgetType),
                $this->getUserPrompt($widgetType, $context),
                0.7,
                'homepage_widget_' . $widgetType
            );

            if (!empty($aiPayload)) {
                return [
                    'payload' => $aiPayload,
                    'source' => 'ai'
                ];
            }
        } catch (\Throwable $e) {
            $this->logger->warning('Homepage widget AI generation failed. Falling back to template.', [
                'widget_type' => $widgetType,
                'context' => $context,
                'exception' => $e
            ]);
        }

        $fallback = match ($widgetType) {
            'cta' => $this->fallbackCta($context),
            'testimonials' => $this->fallbackTestimonials($context),
            'trust_badges' => $this->fallbackTrustBadges($context),
            'newsletter' => $this->fallbackNewsletter($context),
            'categories_carousel' => $this->fallbackCategoriesCarousel($context),
            default => []
        };

        return [
            'payload' => $fallback,
            'source' => 'fallback'
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function generate(string $widgetType, string $context = ''): array
    {
        return $this->generateWithMeta($widgetType, $context)['payload'];
    }

    private function getSystemPrompt(string $widgetType): string
    {
        $storeContext = $this->storeContextProvider->getContextForPrompt();

        return sprintf(
            'You are a senior ecommerce content strategist for a pet supplies online store called LeisPet. '
            . 'Generate a JSON object for widget type "%s". '
            . 'Return ONLY valid JSON without markdown fences. '
            . 'Use realistic, engaging ecommerce copy. Never use placeholder text like "lorem ipsum". '
            . "\n\nCRITICAL: You MUST only use URLs that actually exist in the store. "
            . 'Do NOT invent or guess URLs — only use the exact paths listed below. '
            . "If a category or route is not listed here, it does NOT exist.\n\n"
            . "--- REAL STORE DATA ---\n%s\n--- END STORE DATA ---",
            $widgetType,
            $storeContext
        );
    }

    private function getUserPrompt(string $widgetType, string $context): string
    {
        $contextText = $context !== '' ? $context : 'No extra context provided. Use sensible defaults for a pet supplies ecommerce store.';

        return match ($widgetType) {
            'cta' => $this->getCtaPrompt($contextText),
            'trust_badges' => $this->getTrustBadgesPrompt($contextText),
            'testimonials' => $this->getTestimonialsPrompt($contextText),
            'newsletter' => $this->getNewsletterPrompt($contextText),
            'categories_carousel' => $this->getCategoriesCarouselPrompt($contextText),
            default => sprintf('Generate JSON for widget type "%s". Context: "%s". Return only JSON.', $widgetType, $contextText),
        };
    }

    // ── Per-type Prompts ─────────────────────────────────────────────────

    private function getCtaPrompt(string $contextText): string
    {
        $routes = $this->buildRoutesList();

        return sprintf(
            'Create a CTA widget JSON with this exact structure:
{
  "content": {
    "eyebrow": "short label above headline",
    "headline": "main heading text (REQUIRED)",
    "subheadline": "supporting text",
    "body": "longer description paragraph",
    "disclaimer": "small print text"
  },
  "cta": {
    "label": "primary button text (REQUIRED)",
    "secondaryLabel": "secondary button text",
    "icon": { "name": "icon-name", "position": "left|right" }
  },
  "behavior": {
    "primaryAction": { "type": "navigate", "url": "/shop (REQUIRED — must be a REAL route from the list below)", "target": "_self|_blank" },
    "secondaryAction": { "type": "navigate|modal", "modalId": "optional_modal_id" }
  },
  "design": {
    "variant": "primary|secondary|outline",
    "size": "small|medium|large",
    "alignment": "left|center|right",
    "theme": "light|dark",
    "fullWidth": false,
    "backgroundImageUrl": ""
  }
}
Rules:
- content.headline, cta.label, and behavior.primaryAction.url are REQUIRED.
- behavior.primaryAction.url MUST be one of these REAL store routes: %s
- Do NOT invent URLs. Only use routes from the list above.
- Use concrete, non-generic copy. Adapt to this context: "%s".
Return only JSON.',
            $routes,
            $contextText
        );
    }

    private function getTrustBadgesPrompt(string $contextText): string
    {
        $routes = $this->buildRoutesList();

        return sprintf(
            'Create a Trust Badges widget JSON with this exact structure:
{
  "badges": [
    { "icon": "emoji or icon class (REQUIRED)", "title": "badge title (REQUIRED)", "description": "short description", "url": "" }
  ],
  "layout": "grid|horizontal",
  "theme": "light|dark",
  "columns_desktop": 3,
  "columns_mobile": 1
}
Rules: Include 3-5 badges. Each badge must have icon and title. Use relevant trust signals for an ecommerce pet store (secure checkout, shipping, returns, support, quality). If a badge has a url, it MUST be one of these REAL routes: %s. Context: "%s".
Return only JSON.',
            $routes,
            $contextText
        );
    }

    private function getTestimonialsPrompt(string $contextText): string
    {
        return sprintf(
            'Create a Testimonials widget JSON with this exact structure:
{
  "items": [
    {
      "quote": "testimonial text (REQUIRED)",
      "author_name": "customer name (REQUIRED)",
      "author_title": "role or pet type (e.g. Dog Owner)",
      "author_image_url": "",
      "rating": 5
    }
  ],
  "autoplay": true,
  "autoplay_interval_ms": 4500,
  "layout": "carousel|grid",
  "show_rating": true,
  "show_avatar": true
}
Rules: Include 3-4 testimonials. Each must have quote and author_name. Ratings 1-5. Write realistic customer reviews about pet products. Context: "%s".
Return only JSON.',
            $contextText
        );
    }

    private function getNewsletterPrompt(string $contextText): string
    {
        return sprintf(
            'Create a Newsletter signup widget JSON with this exact structure:
{
  "headline": "newsletter signup heading (REQUIRED)",
  "description": "supporting text explaining the benefit",
  "email_placeholder": "email input placeholder text",
  "button_label": "subscribe button text (REQUIRED)",
  "success_message": "message shown after subscription",
  "disclaimer_text": "privacy/unsubscribe notice",
  "background_image_url": "",
  "layout": "inline|stacked",
  "theme": "light|dark"
}
Rules: headline and button_label are required. Write compelling copy that encourages newsletter signups for a pet store. Mention exclusive deals or pet care tips. Context: "%s".
Return only JSON.',
            $contextText
        );
    }

    private function getCategoriesCarouselPrompt(string $contextText): string
    {
        $categoriesList = $this->buildCategoriesList();

        return sprintf(
            'Create a Categories Carousel widget JSON with this exact structure:
{
  "items": [
    { "label": "category name (REQUIRED)", "url": "/category-url (REQUIRED)", "image_url": "", "product_count": 0 }
  ],
  "show_image": true,
  "show_product_count": true,
  "items_per_view_desktop": 4,
  "items_per_view_mobile": 2,
  "autoplay": false,
  "cta_label": "view all button text",
  "cta_url": "/shop"
}
Rules:
- You MUST use ONLY the real categories listed below. Do NOT invent categories.
- Each item\'s url MUST exactly match the url_path from the list.
- Set product_count to the real count from the list.
- Only include categories that have at least 1 product.

REAL CATEGORIES:
%s

Context: "%s".
Return only JSON.',
            $categoriesList,
            $contextText
        );
    }

    // ── Fallback Structures ──────────────────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    private function fallbackCta(string $context): array
    {
        $headline = $context !== '' ? $context : 'Everything Your Pet Needs, Delivered';

        // Pick the best CTA URL from real store routes
        $ctx = $this->storeContextProvider->getContext();
        $ctaUrl = '/shop'; // safe default
        foreach ($ctx['available_routes'] as $route) {
            if ($route === '/shop') {
                $ctaUrl = '/shop';
                break;
            }
        }

        return [
            'content' => [
                'eyebrow' => 'Limited Time Offer',
                'headline' => $headline,
                'subheadline' => 'Premium pet supplies with fast, free shipping on orders over $49',
                'body' => 'Join thousands of happy pet parents who trust LeisPet for quality nutrition, toys, and wellness products.',
                'disclaimer' => '* Free shipping on qualifying orders. Some exclusions apply.'
            ],
            'cta' => [
                'label' => 'Shop Now',
                'secondaryLabel' => 'Learn More',
                'icon' => [
                    'name' => 'arrow-right',
                    'position' => 'right'
                ]
            ],
            'behavior' => [
                'primaryAction' => [
                    'type' => 'navigate',
                    'url' => $ctaUrl,
                    'target' => '_self'
                ],
                'secondaryAction' => [
                    'type' => 'navigate',
                    'modalId' => ''
                ]
            ],
            'design' => [
                'variant' => 'primary',
                'size' => 'large',
                'alignment' => 'center',
                'theme' => 'dark',
                'fullWidth' => false,
                'backgroundImageUrl' => ''
            ]
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fallbackTestimonials(string $context): array
    {
        return [
            'items' => [
                [
                    'quote' => $context !== '' ? $context : 'My golden retriever absolutely loves the grain-free kibble. Coat is shinier than ever!',
                    'author_name' => 'Maria S.',
                    'author_title' => 'Dog Owner',
                    'author_image_url' => '',
                    'rating' => 5
                ],
                [
                    'quote' => 'Fast delivery and the cat toys are incredibly well-made. My cats play with them every day.',
                    'author_name' => 'Gabriel P.',
                    'author_title' => 'Cat Parent',
                    'author_image_url' => '',
                    'rating' => 5
                ],
                [
                    'quote' => 'Great customer support helped me find the right food for my senior dog. Highly recommend!',
                    'author_name' => 'Ana L.',
                    'author_title' => 'Dog Owner',
                    'author_image_url' => '',
                    'rating' => 4
                ]
            ],
            'autoplay' => true,
            'autoplay_interval_ms' => 4500,
            'layout' => 'carousel',
            'show_rating' => true,
            'show_avatar' => true
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fallbackTrustBadges(string $context): array
    {
        return [
            'badges' => [
                [
                    'icon' => '🔒',
                    'title' => 'Secure Checkout',
                    'description' => 'SSL-encrypted payments',
                    'url' => ''
                ],
                [
                    'icon' => '🚚',
                    'title' => 'Free Shipping',
                    'description' => $context !== '' ? $context : 'On orders over $49',
                    'url' => ''
                ],
                [
                    'icon' => '↩️',
                    'title' => 'Easy Returns',
                    'description' => '30-day hassle-free returns',
                    'url' => ''
                ],
                [
                    'icon' => '💬',
                    'title' => 'Pet Expert Support',
                    'description' => 'Real humans who love pets',
                    'url' => ''
                ]
            ],
            'layout' => 'horizontal',
            'theme' => 'light',
            'columns_desktop' => 4,
            'columns_mobile' => 2
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fallbackNewsletter(string $context): array
    {
        return [
            'headline' => 'Join the LeisPet Pack',
            'description' => $context !== '' ? $context : 'Get exclusive deals, pet care tips, and new product alerts delivered to your inbox.',
            'email_placeholder' => 'your@email.com',
            'button_label' => 'Subscribe',
            'success_message' => 'Welcome to the pack! Check your inbox for a surprise.',
            'disclaimer_text' => 'We respect your privacy. Unsubscribe anytime.',
            'background_image_url' => '',
            'layout' => 'inline',
            'theme' => 'light'
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fallbackCategoriesCarousel(string $context): array
    {
        $categories = $this->storeContextProvider->getCategoriesWithProducts();

        // Build items from real category data
        $items = [];
        foreach ($categories as $cat) {
            $items[] = [
                'label' => $cat['name'],
                'url' => '/' . ltrim($cat['url_path'], '/'),
                'image_url' => '',
                'product_count' => $cat['product_count']
            ];
        }

        // If no categories were found, provide a minimal fallback
        if (empty($items)) {
            $items = [
                ['label' => 'Shop', 'url' => '/shop', 'image_url' => '', 'product_count' => 0],
            ];
        }

        return [
            'items' => $items,
            'show_image' => true,
            'show_product_count' => true,
            'items_per_view_desktop' => min(4, count($items)),
            'items_per_view_mobile' => 2,
            'autoplay' => false,
            'cta_label' => $context !== '' ? $context : 'Browse All Categories',
            'cta_url' => '/shop'
        ];
    }

    // ── Helper methods ───────────────────────────────────────────────────

    /**
     * Build a formatted list of real categories for inclusion in AI prompts.
     */
    private function buildCategoriesList(): string
    {
        $categories = $this->storeContextProvider->getCategoriesWithProducts();
        if (empty($categories)) {
            return '  - "Shop" → /shop';
        }

        $lines = [];
        foreach ($categories as $cat) {
            $lines[] = sprintf(
                '  - "%s" → /%s (%d products)',
                $cat['name'],
                ltrim($cat['url_path'], '/'),
                $cat['product_count']
            );
        }
        return implode("\n", $lines);
    }

    /**
     * Build a comma-separated list of available store routes for AI prompts.
     */
    private function buildRoutesList(): string
    {
        $ctx = $this->storeContextProvider->getContext();
        if (empty($ctx['available_routes'])) {
            return '/shop, /';
        }
        return implode(', ', $ctx['available_routes']);
    }
}
