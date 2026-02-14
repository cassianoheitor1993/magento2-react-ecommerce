<?php

declare(strict_types=1);

namespace Petshop\Homepage\Api;

use Petshop\Homepage\Model\Widget;

interface WidgetRepositoryInterface
{
    public function saveWidgetData(array $data): Widget;

    public function deleteById(int $widgetId): void;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getActiveWidgetsForPage(string $pageCode = 'home', string $placement = 'middle'): array;

    public function moveWidget(int $widgetId, string $direction): void;
}
