<?php
declare(strict_types=1);

namespace Merlin\MultiCoupon\Controller\Adminhtml\Code;

use Magento\Framework\Controller\Result\Forward;
use Magento\Framework\Controller\Result\ForwardFactory;

class NewAction extends AbstractCode
{
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        private readonly ForwardFactory $resultForwardFactory
    ) {
        parent::__construct($context);
    }

    public function execute(): Forward
    {
        $resultForward = $this->resultForwardFactory->create();
        return $resultForward->forward('edit');
    }
}
