<?php
declare(strict_types=1);

namespace Merlin\MultiCoupon\Controller\Adminhtml\Code;

use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

class Index extends AbstractCode
{
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        private readonly PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
    }

    public function execute(): Page
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Merlin_MultiCoupon::codes');
        $resultPage->getConfig()->getTitle()->prepend(__('Allowed Promo Codes'));

        return $resultPage;
    }
}
