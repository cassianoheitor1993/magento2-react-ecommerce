<?php

declare(strict_types=1);

namespace LeisPet\Homepage\Model\Widget;

use LeisPet\Homepage\Model\ResourceModel\Widget\CollectionFactory;
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

        foreach ($this->collection->getItems() as $widget) {
            $this->loadedData[$widget->getId()] = $widget->getData();
        }

        $data = $this->dataPersistor->get('leispet_homepage_widget');
        if (!empty($data)) {
            $widget = $this->collection->getNewEmptyItem();
            $widget->setData($data);
            $this->loadedData[(string)($widget->getId() ?: 'new')] = $widget->getData();
            $this->dataPersistor->clear('leispet_homepage_widget');
        }

        return $this->loadedData;
    }
}
