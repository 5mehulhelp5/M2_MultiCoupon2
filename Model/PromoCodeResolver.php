<?php
declare(strict_types=1);

namespace Merlin\MultiCoupon\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Eav\Model\Config as EavConfig;

class PromoCodeResolver
{
    /**
     * @param ProductResource $productResource
     * @param EavConfig $eavConfig
     */
    public function __construct(
        private readonly ProductResource $productResource,
        private readonly EavConfig $eavConfig
    ) {
    }

    /**
     * Return the raw coupon code from the product.
     *
     * Supports select/dropdown attributes storing option IDs.
     *
     * @param ProductInterface|null $product
     * @return string|null
     */
    public function resolveCodeFromProduct(?ProductInterface $product): ?string
    {
        if (!$product || !(int)$product->getId()) {
            return null;
        }

        $storeId = (int)$product->getStoreId();
        $rawValue = $this->productResource->getAttributeRawValue(
            (int)$product->getId(),
            'google_promo_code',
            $storeId
        );

        if ($rawValue === false || $rawValue === null || $rawValue === '') {
            return null;
        }

        $rawValue = trim((string)$rawValue);
        if ($rawValue === '') {
            return null;
        }

        $normalized = strtoupper($rawValue);

        // If the attribute already stores the code directly
        if (in_array($normalized, ['DEAL5', 'DEAL10', 'DEAL15', 'DEAL20', 'DEAL25'], true)) {
            return $normalized;
        }

        $attribute = $this->eavConfig->getAttribute('catalog_product', 'google_promo_code');
        if (!$attribute || !(int)$attribute->getId() || !$attribute->usesSource()) {
            return null;
        }

        $label = $attribute->getSource()->getOptionText($rawValue);

        if (is_array($label)) {
            $label = reset($label);
        }

        if (!is_string($label) || trim($label) === '') {
            return null;
        }

        $label = strtoupper(trim($label));

        return in_array($label, ['DEAL5', 'DEAL10', 'DEAL15', 'DEAL20', 'DEAL25'], true)
            ? $label
            : null;
    }

    /**
     * Return the display label for the product page.
     *
     * @param ProductInterface|null $product
     * @return string|null
     */
    public function resolveLabelFromProduct(?ProductInterface $product): ?string
    {
        return match ($this->resolveCodeFromProduct($product)) {
            'DEAL5' => 'Extra 5% OFF',
            'DEAL10' => 'Extra 10% OFF',
            'DEAL15' => 'Extra 15% OFF',
            'DEAL20' => 'Extra 20% OFF',
            'DEAL25' => 'Extra 25% OFF',
            default => null,
        };
    }
}
