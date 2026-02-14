<?php

declare(strict_types=1);

namespace LeisPet\Homepage\Model\Resolver;

use LeisPet\Homepage\Api\WidgetRepositoryInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class HomePageLayout implements ResolverInterface
{
    public function __construct(private readonly WidgetRepositoryInterface $widgetRepository)
    {
    }

    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ): array {
        $pageCode = (string)($args['pageCode'] ?? 'home');

        return [
            'page_code' => $pageCode,
            'static_sections' => [
                ['code' => 'announcement_bar', 'is_enabled' => true, 'is_orderable' => false],
                ['code' => 'header', 'is_enabled' => true, 'is_orderable' => false],
                ['code' => 'hero', 'is_enabled' => true, 'is_orderable' => false],
                ['code' => 'footer', 'is_enabled' => true, 'is_orderable' => false]
            ],
            'middle_widgets' => $this->widgetRepository->getActiveWidgetsForPage($pageCode, 'middle')
        ];
    }
}
