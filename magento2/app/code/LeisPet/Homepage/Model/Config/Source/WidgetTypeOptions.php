<?php

declare(strict_types=1);

namespace LeisPet\Homepage\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class WidgetTypeOptions implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'trust_badges', 'label' => __('Trust Badges')],
            ['value' => 'testimonials', 'label' => __('Testimonials')],
            ['value' => 'cta', 'label' => __('CTA')],
            ['value' => 'categories_carousel', 'label' => __('Categories Carousel')],
            ['value' => 'newsletter', 'label' => __('Newsletter')]
        ];
    }
}
