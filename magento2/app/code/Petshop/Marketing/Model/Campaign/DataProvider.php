<?php

declare(strict_types=1);

namespace Petshop\Marketing\Model\Campaign;

use Petshop\Marketing\Model\ResourceModel\Campaign\CollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;

class DataProvider extends AbstractDataProvider
{
    /**
     * @var array<int|string, array<string, mixed>>
     */
    private array $loadedData = [];

    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        CollectionFactory $collectionFactory,
        private readonly DataPersistorInterface $dataPersistor,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @return array<int|string, array<string, mixed>>
     */
    public function getData(): array
    {
        if (!empty($this->loadedData)) {
            return $this->loadedData;
        }

        $items = $this->collection->getItems();
        foreach ($items as $campaign) {
            $this->loadedData[$campaign->getId()] = $campaign->getData();
        }

        $data = $this->dataPersistor->get('petshop_marketing_campaign');
        if (!empty($data)) {
            $campaign = $this->collection->getNewEmptyItem();
            $campaign->setData($data);
            $this->loadedData[$campaign->getId()] = $campaign->getData();
            $this->dataPersistor->clear('petshop_marketing_campaign');
        }

        return $this->loadedData;
    }
}
