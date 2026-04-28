<?php
declare(strict_types=1);

namespace Merlin\MultiCoupon\Controller\Adminhtml\Code;

use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Merlin\MultiCoupon\Model\CodeFactory;

class Delete extends AbstractCode
{
    public const ADMIN_RESOURCE = 'Merlin_MultiCoupon::codes_delete';

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        private readonly CodeFactory $codeFactory
    ) {
        parent::__construct($context);
    }

    public function execute(): Redirect
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = (int)$this->getRequest()->getParam('entity_id');

        if (!$id) {
            $this->messageManager->addErrorMessage(__('We can\'t find a promo code to delete.'));
            return $resultRedirect->setPath('*/*/');
        }

        try {
            $model = $this->codeFactory->create()->load($id);
            if (!$model->getId()) {
                throw new LocalizedException(__('This promo code no longer exists.'));
            }

            $model->delete();
            $this->messageManager->addSuccessMessage(__('The promo code has been deleted.'));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage(__('Something went wrong while deleting the promo code.'));
        }

        return $resultRedirect->setPath('*/*/');
    }
}
