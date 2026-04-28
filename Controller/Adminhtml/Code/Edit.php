<?php
declare(strict_types=1);

namespace Merlin\MultiCoupon\Controller\Adminhtml\Code;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Merlin\MultiCoupon\Model\CodeFactory;

class Edit extends AbstractCode
{
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly CodeFactory $codeFactory,
        private readonly Registry $coreRegistry
    ) {
        parent::__construct($context);
    }

    public function execute(): Page
    {
        $id = (int)$this->getRequest()->getParam('entity_id');
        $model = $this->codeFactory->create();

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                throw new LocalizedException(__('This deal code no longer exists.'));
            }
        }

        $this->coreRegistry->register('merlin_multicoupon_code', $model);

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Merlin_MultiCoupon::codes');
        $resultPage->getConfig()->getTitle()->prepend(
            $model->getId() ? __('Edit Promo Code') : __('New Promo Code')
        );

        return $resultPage;
    }
}
