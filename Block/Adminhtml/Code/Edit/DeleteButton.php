<?php
declare(strict_types=1);

namespace Merlin\MultiCoupon\Block\Adminhtml\Code\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class DeleteButton extends GenericButton implements ButtonProviderInterface
{
    public function getButtonData(): array
    {
        $data = [];

        if ($this->getId() !== null) {
            $data = [
                'label' => __('Delete'),
                'class' => 'delete',
                'on_click' => sprintf(
                    "deleteConfirm('%s', '%s')",
                    __('Are you sure you want to delete this deal code?'),
                    $this->getUrl('merlin_multicoupon/code/delete', ['entity_id' => $this->getId()])
                ),
                'sort_order' => 20,
            ];
        }

        return $data;
    }
}
