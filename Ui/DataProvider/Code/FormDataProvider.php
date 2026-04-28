<?php
declare(strict_types=1);

namespace Merlin\MultiCoupon\Ui\DataProvider\Code;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Merlin\MultiCoupon\Model\Code;
use Merlin\MultiCoupon\Model\ResourceModel\Code\CollectionFactory;

class FormDataProvider extends AbstractDataProvider
{
    /**
     * @var array<int, array<string, mixed>>
     */
    private array $loadedData = [];

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param array<string, mixed> $meta
     * @param array<string, mixed> $data
     */
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
     * @return array<int, array<string, mixed>>
     */
    public function getData(): array
    {
        if ($this->loadedData) {
            return $this->loadedData;
        }

        /** @var Code $item */
        foreach ($this->collection->getItems() as $item) {
            $this->loadedData[(int)$item->getId()] = $item->getData();
        }

        $persistedData = $this->dataPersistor->get('merlin_multicoupon_code');
        if (!empty($persistedData)) {
            $model = $this->collection->getNewEmptyItem();
            $model->setData($persistedData);
            $this->loadedData[(int)$model->getId()] = $model->getData();
            $this->dataPersistor->clear('merlin_multicoupon_code');
        }

        return $this->loadedData;
    }
}
