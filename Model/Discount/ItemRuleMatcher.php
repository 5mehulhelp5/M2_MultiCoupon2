<?php
declare(strict_types=1);

namespace Merlin\MultiCoupon\Model\Discount;

use Magento\Catalog\Model\Product;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\Rule\Model\Condition\AbstractCondition;
use Magento\Rule\Model\Condition\Combine;
use Magento\SalesRule\Model\Rule;

class ItemRuleMatcher
{
    /**
     * Determine whether the quote item matches the provided sales rule.
     *
     * Matching order:
     * 1. Use rule actions when present, because actions are item-scoped in Magento.
     * 2. If no actions exist, evaluate only product-level rule conditions against the item.
     * 3. Never treat an item as matching purely because of cart/address-level conditions.
     *
     * @param AbstractItem $item
     * @param Rule $rule
     * @return bool
     */
    public function isMatch(AbstractItem $item, Rule $rule): bool
    {
        $actions = $rule->getActions();
        if ($actions && $actions->getConditions()) {
            return (bool)$actions->validate($item);
        }

        $conditions = $rule->getConditions();
        if ($conditions && $conditions->getConditions()) {
            return $this->validateProductScopedConditions($conditions, $item);
        }

        return false;
    }

    /**
     * Recursively validate only product-scoped rule conditions against the item.
     *
     * @param AbstractCondition $condition
     * @param AbstractItem $item
     * @return bool
     */
    private function validateProductScopedConditions(AbstractCondition $condition, AbstractItem $item): bool
    {
        if ($condition instanceof Combine) {
            $children = $condition->getConditions() ?: [];

            $results = [];
            foreach ($children as $child) {
                if (!$child instanceof AbstractCondition) {
                    continue;
                }

                if (!$this->isProductScopedConditionTree($child)) {
                    continue;
                }

                $results[] = $this->validateProductScopedConditions($child, $item);
            }

            if ($results === []) {
                return false;
            }

            $aggregator = strtolower((string)$condition->getAggregator()); // all / any
            $value = (string)$condition->getValue(); // 1 / 0

            if ($aggregator === 'any') {
                $matched = in_array(true, $results, true);
            } else {
                $matched = !in_array(false, $results, true);
            }

            return $value === '0' ? !$matched : $matched;
        }

        return $this->validateProductScopedLeaf($condition, $item);
    }

    /**
     * Determine whether a condition tree contains product-scoped logic.
     *
     * @param AbstractCondition $condition
     * @return bool
     */
    private function isProductScopedConditionTree(AbstractCondition $condition): bool
    {
        if ($condition instanceof Combine) {
            foreach (($condition->getConditions() ?: []) as $child) {
                if ($child instanceof AbstractCondition && $this->isProductScopedConditionTree($child)) {
                    return true;
                }
            }

            return false;
        }

        return $this->isProductScopedLeaf($condition);
    }

    /**
     * Determine whether a single leaf condition is product-scoped.
     *
     * @param AbstractCondition $condition
     * @return bool
     */
    private function isProductScopedLeaf(AbstractCondition $condition): bool
    {
        $attribute = (string)$condition->getAttribute();

        return in_array($attribute, [
            'sku',
            'quote_item_sku',
            'product_id',
            'category_ids',
            'attribute_set_id',
            'manufacturer',
            'google_promo_code',
        ], true);
    }

    /**
     * Evaluate a supported product-scoped leaf condition directly against the item/product.
     *
     * @param AbstractCondition $condition
     * @param AbstractItem $item
     * @return bool
     */
    private function validateProductScopedLeaf(AbstractCondition $condition, AbstractItem $item): bool
    {
        if (!$this->isProductScopedLeaf($condition)) {
            return false;
        }

        $attribute = (string)$condition->getAttribute();
        $operator = (string)$condition->getOperator();
        $ruleValue = $condition->getValue();

        $product = $item->getProduct();
        if (!$product instanceof Product) {
            return false;
        }

        $itemValue = match ($attribute) {
            'sku', 'quote_item_sku' => (string)$item->getSku(),
            'product_id' => (string)(int)$item->getProductId(),
            'category_ids' => array_map('strval', (array)$product->getCategoryIds()),
            'attribute_set_id' => (string)(int)$product->getAttributeSetId(),
            'manufacturer' => (string)$product->getData('manufacturer'),
            'google_promo_code' => (string)$product->getData('google_promo_code'),
            default => null,
        };

        if ($itemValue === null) {
            return false;
        }

        return $this->compareValues($itemValue, $operator, $ruleValue);
    }

    /**
     * Compare item value to rule value using common Magento rule operators.
     *
     * @param string|array $itemValue
     * @param string $operator
     * @param mixed $ruleValue
     * @return bool
     */
    private function compareValues(string|array $itemValue, string $operator, mixed $ruleValue): bool
    {
        $ruleValues = is_array($ruleValue)
            ? array_map('strval', $ruleValue)
            : array_map('trim', explode(',', (string)$ruleValue));

        if (is_array($itemValue)) {
            return match ($operator) {
                '()', '==' => count(array_intersect($itemValue, $ruleValues)) > 0,
                '!()', '!=' => count(array_intersect($itemValue, $ruleValues)) === 0,
                default => false,
            };
        }

        $itemValue = trim((string)$itemValue);

        return match ($operator) {
            '==', '=' => in_array($itemValue, $ruleValues, true),
            '!=' => !in_array($itemValue, $ruleValues, true),
            '()' => in_array($itemValue, $ruleValues, true),
            '!()' => !in_array($itemValue, $ruleValues, true),
            '{}' => $this->containsAny($itemValue, $ruleValues),
            '!{}' => !$this->containsAny($itemValue, $ruleValues),
            default => false,
        };
    }

    /**
     * Return true when the haystack contains any of the supplied needles.
     *
     * @param string $haystack
     * @param string[] $needles
     * @return bool
     */
    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            $needle = trim((string)$needle);
            if ($needle !== '' && str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }
}
