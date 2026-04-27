<?php
declare(strict_types=1);

namespace Merlin\MultiCoupon\Model;

use Magento\Quote\Api\Data\CartInterface;
use Magento\SalesRule\Model\ResourceModel\Coupon\CollectionFactory as CouponCollectionFactory;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Model\RuleFactory;
use Magento\Store\Model\StoreManagerInterface;

class RuleRepository
{
    /**
     * @param CouponCollectionFactory $couponCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param Config $config
     * @param RuleFactory $ruleFactory
     */
    public function __construct(
        private readonly CouponCollectionFactory $couponCollectionFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly Config $config,
        private readonly RuleFactory $ruleFactory
    ) {
    }

    /**
     * Load the active sales rule mapped to a coupon code for the current quote.
     *
     * @param CartInterface $quote
     * @param string $code
     * @return Rule|null
     */
    public function getRuleByCode(CartInterface $quote, string $code): ?Rule
    {
        $code = $this->config->normalizeCode($code);

        if ($code === '' || !$this->config->isAllowedCode($code)) {
            return null;
        }

        $coupon = $this->couponCollectionFactory->create()
            ->addFieldToFilter('code', $code)
            ->getFirstItem();

        if (!$coupon->getId() || !$coupon->getRuleId()) {
            return null;
        }

        /** @var Rule $rule */
        $rule = $this->ruleFactory->create()->load((int)$coupon->getRuleId());

        if (!(int)$rule->getId()) {
            return null;
        }

        if (!(bool)$rule->getIsActive()) {
            return null;
        }

        $websiteId = (int)$this->storeManager->getStore((int)$quote->getStoreId())->getWebsiteId();
        $websiteIds = array_map('intval', (array)$rule->getWebsiteIds());

        if ($websiteIds && !in_array($websiteId, $websiteIds, true)) {
            return null;
        }

        return $rule;
    }
}
