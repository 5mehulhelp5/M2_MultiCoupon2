<?php
declare(strict_types=1);

namespace Merlin\MultiCoupon\Controller\Adminhtml\Code;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

abstract class AbstractCode extends Action
{
    public const ADMIN_RESOURCE = 'Merlin_MultiCoupon::codes';

    public function __construct(Context $context)
    {
        parent::__construct($context);
    }
}
