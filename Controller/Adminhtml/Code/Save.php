<?php
declare(strict_types=1);

namespace Merlin\MultiCoupon\Controller\Adminhtml\Code;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Merlin\MultiCoupon\Model\CodeFactory;
use Merlin\MultiCoupon\Model\Config;

class Save extends AbstractCode
{
    public const ADMIN_RESOURCE = 'Merlin_MultiCoupon::codes_save';

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        private readonly CodeFactory $codeFactory,
        private readonly DataPersistorInterface $dataPersistor,
        private readonly Config $config
    ) {
        parent::__construct($context);
    }

    public function execute(): Redirect
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();

        if (!$data) {
            return $resultRedirect->setPath('*/*/');
        }

        $id = isset($data['entity_id']) ? (int)$data['entity_id'] : 0;
        $model = $this->codeFactory->create();

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This deal code no longer exists.'));
                return $resultRedirect->setPath('*/*/');
            }
        }

        $code = strtoupper(trim((string)($data['code'] ?? '')));
        $label = trim((string)($data['label'] ?? ''));

        try {
            if ($code === '') {
                throw new LocalizedException(__('The code field is required.'));
            }

            if ($this->config->isOfferCode($code)) {
                throw new LocalizedException(__('OFFER codes are managed dynamically and cannot be created here.'));
            }

            if ($label === '') {
                throw new LocalizedException(__('The label field is required.'));
            }

            $model->setData('code', $code);
            $model->setData('label', $label);
            $model->setData('is_active', isset($data['is_active']) ? (int)$data['is_active'] : 0);
            $model->setData('sort_order', isset($data['sort_order']) ? (int)$data['sort_order'] : 0);

            $model->save();

            $this->messageManager->addSuccessMessage(__('The promo code has been saved.'));
            $this->dataPersistor->clear('merlin_multicoupon_code');

            if ($this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/edit', ['entity_id' => $model->getId()]);
            }

            return $resultRedirect->setPath('*/*/');
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage(__('Something went wrong while saving the promo code.'));
        }

        $this->dataPersistor->set('merlin_multicoupon_code', $data);

        return $resultRedirect->setPath(
            '*/*/edit',
            ['entity_id' => $id ?: null]
        );
    }
}
