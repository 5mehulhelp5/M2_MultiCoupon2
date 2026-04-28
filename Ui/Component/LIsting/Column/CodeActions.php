<?php
declare(strict_types=1);

namespace Merlin\MultiCoupon\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class CodeActions extends Column
{
    private const URL_PATH_EDIT = 'merlin_multicoupon/code/edit';
    private const URL_PATH_DELETE = 'merlin_multicoupon/code/delete';

    /**
     * @param UrlInterface $urlBuilder
     * @param array<string, mixed> $components
     * @param array<string, mixed> $data
     */
    public function __construct(
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory,
        private readonly UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare row actions.
     *
     * @param array<string, mixed> $dataSource
     * @return array<string, mixed>
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as &$item) {
            if (!isset($item['entity_id'])) {
                continue;
            }

            $name = $this->getData('name');

            $item[$name]['edit'] = [
                'href' => $this->urlBuilder->getUrl(self::URL_PATH_EDIT, ['entity_id' => $item['entity_id']]),
                'label' => __('Edit'),
            ];

            $item[$name]['delete'] = [
                'href' => $this->urlBuilder->getUrl(self::URL_PATH_DELETE, ['entity_id' => $item['entity_id']]),
                'label' => __('Delete'),
                'confirm' => [
                    'title' => __('Delete Deal Code'),
                    'message' => __('Are you sure you want to delete deal code "%1"?', $item['code'] ?? '')
                ],
                'post' => true,
            ];
        }

        return $dataSource;
    }
}
