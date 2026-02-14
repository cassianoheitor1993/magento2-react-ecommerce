<?php

declare(strict_types=1);

namespace Petshop\Homepage\Model\Config;

/**
 * Canonical schema definitions for every widget type.
 *
 * Each schema is an array of field definitions used by:
 *  - The JS config editor (to render editable form fields)
 *  - The WidgetConfigValidator (for required / type checks)
 *  - The AI generation service (to align prompts)
 *
 * Field definition keys:
 *  path       – dot-notation path inside config_json (e.g. "content.headline")
 *  label      – human-readable field label
 *  type       – text | textarea | url | image | number | boolean | select
 *  required   – whether the field is mandatory
 *  group      – visual grouping label for the editor
 *  options    – for "select" type, an array of {value, label} pairs
 *  repeatable – for array-of-objects containers (badges[], items[])
 *  children   – nested field definitions inside a repeatable group
 *  default    – optional default value
 */
class WidgetTypeSchemaRegistry
{
    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function getAllSchemas(): array
    {
        return [
            'cta' => $this->getCtaSchema(),
            'trust_badges' => $this->getTrustBadgesSchema(),
            'testimonials' => $this->getTestimonialsSchema(),
            'newsletter' => $this->getNewsletterSchema(),
            'categories_carousel' => $this->getCategoriesCarouselSchema(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getSchema(string $widgetType): array
    {
        $all = $this->getAllSchemas();
        return $all[$widgetType] ?? [];
    }

    /**
     * @return string[]
     */
    public function getSupportedTypes(): array
    {
        return array_keys($this->getAllSchemas());
    }

    // ── CTA ──────────────────────────────────────────────────────────────

    private function getCtaSchema(): array
    {
        return [
            // Content group
            ['path' => 'content.eyebrow',     'label' => 'Eyebrow Text',     'type' => 'text',     'required' => false, 'group' => 'Content'],
            ['path' => 'content.headline',     'label' => 'Headline',         'type' => 'text',     'required' => true,  'group' => 'Content'],
            ['path' => 'content.subheadline',  'label' => 'Sub-headline',     'type' => 'text',     'required' => false, 'group' => 'Content'],
            ['path' => 'content.body',         'label' => 'Body Text',        'type' => 'textarea', 'required' => false, 'group' => 'Content'],
            ['path' => 'content.disclaimer',   'label' => 'Disclaimer',       'type' => 'text',     'required' => false, 'group' => 'Content'],

            // CTA Buttons group
            ['path' => 'cta.label',            'label' => 'Primary Button Label', 'type' => 'text', 'required' => true,  'group' => 'Buttons'],
            ['path' => 'cta.secondaryLabel',   'label' => 'Secondary Button Label', 'type' => 'text', 'required' => false, 'group' => 'Buttons'],
            ['path' => 'cta.icon.name',        'label' => 'Icon Name',        'type' => 'text',     'required' => false, 'group' => 'Buttons'],
            ['path' => 'cta.icon.position',    'label' => 'Icon Position',    'type' => 'select',   'required' => false, 'group' => 'Buttons',
                'options' => [['value' => 'left', 'label' => 'Left'], ['value' => 'right', 'label' => 'Right']]],

            // Behavior group
            ['path' => 'behavior.primaryAction.type', 'label' => 'Primary Action Type', 'type' => 'select', 'required' => true, 'group' => 'Behavior',
                'options' => [['value' => 'navigate', 'label' => 'Navigate'], ['value' => 'modal', 'label' => 'Open Modal'], ['value' => 'scroll', 'label' => 'Scroll To']]],
            ['path' => 'behavior.primaryAction.url',    'label' => 'Primary Action URL',    'type' => 'url',    'required' => true,  'group' => 'Behavior'],
            ['path' => 'behavior.primaryAction.target',  'label' => 'Primary Link Target',   'type' => 'select', 'required' => false, 'group' => 'Behavior',
                'options' => [['value' => '_self', 'label' => 'Same Tab'], ['value' => '_blank', 'label' => 'New Tab']]],
            ['path' => 'behavior.secondaryAction.type', 'label' => 'Secondary Action Type', 'type' => 'select', 'required' => false, 'group' => 'Behavior',
                'options' => [['value' => 'navigate', 'label' => 'Navigate'], ['value' => 'modal', 'label' => 'Open Modal']]],
            ['path' => 'behavior.secondaryAction.modalId', 'label' => 'Secondary Modal ID', 'type' => 'text', 'required' => false, 'group' => 'Behavior'],

            // Design group
            ['path' => 'design.variant',   'label' => 'Variant',    'type' => 'select', 'required' => false, 'group' => 'Design',
                'options' => [['value' => 'primary', 'label' => 'Primary'], ['value' => 'secondary', 'label' => 'Secondary'], ['value' => 'outline', 'label' => 'Outline']]],
            ['path' => 'design.size',      'label' => 'Size',       'type' => 'select', 'required' => false, 'group' => 'Design',
                'options' => [['value' => 'small', 'label' => 'Small'], ['value' => 'medium', 'label' => 'Medium'], ['value' => 'large', 'label' => 'Large']]],
            ['path' => 'design.alignment', 'label' => 'Alignment',  'type' => 'select', 'required' => false, 'group' => 'Design',
                'options' => [['value' => 'left', 'label' => 'Left'], ['value' => 'center', 'label' => 'Center'], ['value' => 'right', 'label' => 'Right']]],
            ['path' => 'design.theme',     'label' => 'Theme',      'type' => 'select', 'required' => false, 'group' => 'Design',
                'options' => [['value' => 'light', 'label' => 'Light'], ['value' => 'dark', 'label' => 'Dark']]],
            ['path' => 'design.fullWidth', 'label' => 'Full Width', 'type' => 'boolean', 'required' => false, 'group' => 'Design'],
            ['path' => 'design.backgroundImageUrl', 'label' => 'Background Image URL', 'type' => 'image', 'required' => false, 'group' => 'Design'],
        ];
    }

    // ── Trust Badges ─────────────────────────────────────────────────────

    private function getTrustBadgesSchema(): array
    {
        return [
            // Badges repeatable group
            [
                'path' => 'badges',
                'label' => 'Badges',
                'type' => 'repeatable',
                'required' => true,
                'group' => 'Badges',
                'children' => [
                    ['path' => 'icon',        'label' => 'Icon (emoji or class)', 'type' => 'text',     'required' => true],
                    ['path' => 'title',       'label' => 'Title',                 'type' => 'text',     'required' => true],
                    ['path' => 'description', 'label' => 'Description',           'type' => 'text',     'required' => false],
                    ['path' => 'url',         'label' => 'Link URL',              'type' => 'url',      'required' => false],
                ],
            ],
            // Layout settings
            ['path' => 'layout', 'label' => 'Layout', 'type' => 'select', 'required' => false, 'group' => 'Settings',
                'options' => [['value' => 'grid', 'label' => 'Grid'], ['value' => 'horizontal', 'label' => 'Horizontal']]],
            ['path' => 'theme', 'label' => 'Theme', 'type' => 'select', 'required' => false, 'group' => 'Settings',
                'options' => [['value' => 'light', 'label' => 'Light'], ['value' => 'dark', 'label' => 'Dark']]],
            ['path' => 'columns_desktop', 'label' => 'Columns (Desktop)', 'type' => 'number', 'required' => false, 'group' => 'Settings', 'default' => 3],
            ['path' => 'columns_mobile',  'label' => 'Columns (Mobile)',  'type' => 'number', 'required' => false, 'group' => 'Settings', 'default' => 1],
        ];
    }

    // ── Testimonials ─────────────────────────────────────────────────────

    private function getTestimonialsSchema(): array
    {
        return [
            [
                'path' => 'items',
                'label' => 'Testimonials',
                'type' => 'repeatable',
                'required' => true,
                'group' => 'Testimonials',
                'children' => [
                    ['path' => 'quote',            'label' => 'Quote',            'type' => 'textarea', 'required' => true],
                    ['path' => 'author_name',      'label' => 'Author Name',      'type' => 'text',     'required' => true],
                    ['path' => 'author_title',     'label' => 'Author Title/Role', 'type' => 'text',    'required' => false],
                    ['path' => 'author_image_url', 'label' => 'Author Image URL', 'type' => 'image',    'required' => false],
                    ['path' => 'rating',           'label' => 'Rating (1-5)',      'type' => 'number',   'required' => false],
                ],
            ],
            ['path' => 'autoplay',             'label' => 'Auto-play',            'type' => 'boolean', 'required' => false, 'group' => 'Settings', 'default' => true],
            ['path' => 'autoplay_interval_ms', 'label' => 'Auto-play Interval (ms)', 'type' => 'number', 'required' => false, 'group' => 'Settings', 'default' => 4500],
            ['path' => 'layout', 'label' => 'Layout', 'type' => 'select', 'required' => false, 'group' => 'Settings',
                'options' => [['value' => 'carousel', 'label' => 'Carousel'], ['value' => 'grid', 'label' => 'Grid']]],
            ['path' => 'show_rating', 'label' => 'Show Rating Stars', 'type' => 'boolean', 'required' => false, 'group' => 'Settings', 'default' => true],
            ['path' => 'show_avatar', 'label' => 'Show Author Avatar', 'type' => 'boolean', 'required' => false, 'group' => 'Settings', 'default' => true],
        ];
    }

    // ── Newsletter ───────────────────────────────────────────────────────

    private function getNewsletterSchema(): array
    {
        return [
            ['path' => 'headline',          'label' => 'Headline',             'type' => 'text',     'required' => true,  'group' => 'Content'],
            ['path' => 'description',       'label' => 'Description',          'type' => 'textarea', 'required' => false, 'group' => 'Content'],
            ['path' => 'email_placeholder', 'label' => 'Email Placeholder',    'type' => 'text',     'required' => false, 'group' => 'Content'],
            ['path' => 'button_label',      'label' => 'Button Label',         'type' => 'text',     'required' => true,  'group' => 'Content'],
            ['path' => 'success_message',   'label' => 'Success Message',      'type' => 'text',     'required' => false, 'group' => 'Content'],
            ['path' => 'disclaimer_text',   'label' => 'Disclaimer Text',      'type' => 'text',     'required' => false, 'group' => 'Content'],
            ['path' => 'background_image_url', 'label' => 'Background Image URL', 'type' => 'image', 'required' => false, 'group' => 'Design'],
            ['path' => 'layout', 'label' => 'Layout', 'type' => 'select', 'required' => false, 'group' => 'Design',
                'options' => [['value' => 'inline', 'label' => 'Inline'], ['value' => 'stacked', 'label' => 'Stacked']]],
            ['path' => 'theme', 'label' => 'Theme', 'type' => 'select', 'required' => false, 'group' => 'Design',
                'options' => [['value' => 'light', 'label' => 'Light'], ['value' => 'dark', 'label' => 'Dark']]],
        ];
    }

    // ── Categories Carousel ──────────────────────────────────────────────

    private function getCategoriesCarouselSchema(): array
    {
        return [
            [
                'path' => 'items',
                'label' => 'Categories',
                'type' => 'repeatable',
                'required' => true,
                'group' => 'Categories',
                'children' => [
                    ['path' => 'label',         'label' => 'Category Label',   'type' => 'text',   'required' => true],
                    ['path' => 'url',           'label' => 'Category URL',     'type' => 'url',    'required' => true],
                    ['path' => 'image_url',     'label' => 'Category Image',   'type' => 'image',  'required' => false],
                    ['path' => 'product_count', 'label' => 'Product Count',    'type' => 'number', 'required' => false],
                ],
            ],
            ['path' => 'show_image',            'label' => 'Show Images',           'type' => 'boolean', 'required' => false, 'group' => 'Settings', 'default' => true],
            ['path' => 'show_product_count',    'label' => 'Show Product Count',    'type' => 'boolean', 'required' => false, 'group' => 'Settings', 'default' => false],
            ['path' => 'items_per_view_desktop', 'label' => 'Items Per View (Desktop)', 'type' => 'number', 'required' => false, 'group' => 'Settings', 'default' => 4],
            ['path' => 'items_per_view_mobile',  'label' => 'Items Per View (Mobile)',  'type' => 'number', 'required' => false, 'group' => 'Settings', 'default' => 2],
            ['path' => 'autoplay',  'label' => 'Auto-play',   'type' => 'boolean', 'required' => false, 'group' => 'Settings', 'default' => false],
            ['path' => 'cta_label', 'label' => 'CTA Label',   'type' => 'text',    'required' => false, 'group' => 'Settings'],
            ['path' => 'cta_url',   'label' => 'CTA URL',     'type' => 'url',     'required' => false, 'group' => 'Settings'],
        ];
    }

    /**
     * Normalize a legacy flat CTA config to the canonical contract format.
     */
    public function normalizeLegacyCta(array $data): array
    {
        if (isset($data['content']) || isset($data['cta']) || isset($data['behavior'])) {
            return $data;
        }

        if (!isset($data['headline']) && !isset($data['primary_label'])) {
            return $data;
        }

        return [
            'content' => [
                'eyebrow' => '',
                'headline' => $data['headline'] ?? '',
                'subheadline' => $data['description'] ?? '',
                'body' => '',
                'disclaimer' => '',
            ],
            'cta' => [
                'label' => $data['primary_label'] ?? '',
                'secondaryLabel' => $data['secondary_label'] ?? '',
                'icon' => ['name' => '', 'position' => 'right'],
            ],
            'behavior' => [
                'primaryAction' => [
                    'type' => 'navigate',
                    'url' => $data['primary_url'] ?? '/',
                    'target' => '_self',
                ],
                'secondaryAction' => [
                    'type' => 'navigate',
                    'modalId' => '',
                ],
            ],
            'design' => [
                'variant' => 'primary',
                'size' => 'large',
                'alignment' => $data['alignment'] ?? 'center',
                'theme' => 'light',
                'fullWidth' => false,
                'backgroundImageUrl' => $data['background_image_url'] ?? '',
            ],
        ];
    }
}
