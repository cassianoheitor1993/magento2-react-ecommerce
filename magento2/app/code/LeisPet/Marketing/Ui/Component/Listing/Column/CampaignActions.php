<?php

declare(strict_types=1);

namespace LeisPet\Marketing\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class CampaignActions extends Column
{
    private const URL_PATH_EDIT = 'leispet_marketing/campaign/edit';
    private const URL_PATH_ENQUEUE = 'leispet_marketing/campaign/enqueue';
    private const URL_PATH_PAUSE = 'leispet_marketing/campaign/pause';
    private const URL_PATH_RESUME = 'leispet_marketing/campaign/resume';
    private const URL_PATH_CANCEL = 'leispet_marketing/campaign/cancel';

    public function __construct(
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory,
        private readonly UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        $name = (string)$this->getData('name');

        foreach ($dataSource['data']['items'] as &$item) {
            if (empty($item['campaign_id'])) {
                continue;
            }

            $campaignId = (int)$item['campaign_id'];

            $item[$name]['edit'] = [
                'href' => $this->urlBuilder->getUrl(self::URL_PATH_EDIT, ['campaign_id' => $campaignId]),
                'label' => __('Edit')
            ];
            $item[$name]['enqueue'] = [
                'href' => $this->urlBuilder->getUrl(self::URL_PATH_ENQUEUE, ['campaign_id' => $campaignId]),
                'label' => __('Enqueue')
            ];
            $item[$name]['pause'] = [
                'href' => $this->urlBuilder->getUrl(self::URL_PATH_PAUSE, ['campaign_id' => $campaignId]),
                'label' => __('Pause')
            ];
            $item[$name]['resume'] = [
                'href' => $this->urlBuilder->getUrl(self::URL_PATH_RESUME, ['campaign_id' => $campaignId]),
                'label' => __('Resume')
            ];
            $item[$name]['cancel'] = [
                'href' => $this->urlBuilder->getUrl(self::URL_PATH_CANCEL, ['campaign_id' => $campaignId]),
                'label' => __('Cancel'),
                'confirm' => [
                    'title' => __('Cancel campaign'),
                    'message' => __('Are you sure you want to cancel this campaign?')
                ]
            ];
        }

        return $dataSource;
    }
}
