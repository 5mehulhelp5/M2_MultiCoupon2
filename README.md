# Merlin_MultiCoupon

Magento 2 extension that allows multiple coupon codes to be stored on the same quote and applied safely across different products in one basket.

This module was built for mixed-product baskets where different products may carry different approved discount codes, such as:

- `DEAL5`
- `DEAL10`
- `DEAL15`
- `DEAL20`
- `DEAL25`
- `OFFER-XXXX-XXXX-XXXX-XXXX`

It supports both standard fixed deal codes and accepted-offer coupon codes, while preventing unintended stacking on the same product.

---

## Features

- Apply multiple approved coupon codes to a single quote
- Support fixed deal codes:
  - `DEAL5`
  - `DEAL10`
  - `DEAL15`
  - `DEAL20`
  - `DEAL25`
- Support accepted-offer coupon codes using the `OFFER-...` pattern
- Allow different codes to apply to different products in the same basket
- Prevent double-discounting of the same product
- Automatically retain only winning/active codes after totals recollection
- Automatically remove stale codes when the related product is removed from the basket
- Allow product-page code application
- Allow cart-page multi-code management
- Remove Magento’s default single-coupon cart box and replace it with the multi-code interface
- Show available discount code on eligible product pages
- Allow accepted-offer codes and deal codes to coexist in one basket when they apply to different products

---

## Business Rules

### Supported coupon types

The module supports two classes of coupons:

#### 1. Deal codes
These are fixed, approved codes:

- `DEAL5`
- `DEAL10`
- `DEAL15`
- `DEAL20`
- `DEAL25`

These are typically tied to products using the `google_promo_code` product attribute.

#### 2. Offer codes
These are generated accepted-offer coupons in the format:

- `OFFER-XYM2-YV3T-YUIA-9CN0`

These are validated through Magento sales rules and can apply to specific accepted-offer products.

---

## How the discount logic works

### Per-item best-code resolution

The module does **not** apply every stored code to every matching item.

Instead, for each quote item it:

1. finds all currently stored codes that match that item
2. chooses the single best winner for that item
3. applies only that one code to that item

This prevents stacked discounts on a single product.

### Offer-code priority

If both an `OFFER-...` code and a `DEAL...` code match the same item:

- the `OFFER-...` code takes priority
- the `DEAL...` code does not stack on top of it

This allows accepted offers to override standard deal pricing on the specific accepted-offer item.

### Mixed-basket support

Different products can still use different codes in the same basket.

Example:

- Product A uses `OFFER-XYM2-YV3T-YUIA-9CN0`
- Product B uses `DEAL20`

Both can exist in the same basket at the same time, provided they apply to different items.

### Automatic stale-code cleanup

After cart changes, the extension automatically removes codes that are no longer winners on any current basket item.

This prevents cases where:

- a code was once valid
- the related product was removed
- the code would otherwise remain stored on the quote

---

## Product page behaviour

On eligible product pages, the module shows a product-level discount code block such as:

**Discount Code**  
`DEAL10 - For an Extra 10% OFF`

Customers can click:

**Apply Discount Code**

The product-page flow:

- adds the product to basket if it is not already present
- does not add the product again if it is already in basket
- applies the relevant code
- redirects the customer to the cart page

### Product-page validation

#### DEAL codes
A `DEAL...` code is only accepted on a product page if it matches the product’s configured `google_promo_code`.

#### OFFER codes
An `OFFER-...` code is validated using Magento sales-rule matching against the product/quote item.

---

## Cart page behaviour

The default Magento single-coupon block is removed.

It is replaced with the Merlin multi-code interface, which allows customers to:

- enter multiple coupon codes
- view currently stored codes
- remove individual codes
- clear all codes

The cart page can store more than one code at a time, but the discount engine still ensures only the correct code wins per item.

---

## Code-retention rules

A stored code is kept only if it is currently the winning code for at least one quote item.

That means:

- if a code is no longer relevant, it is removed automatically
- if a code is still winning on at least one item, it remains stored
- overlapping codes do not both continue to survive unless they each win on at least one separate item

This is especially important when:

- products are removed from basket
- quantities change
- an accepted-offer item overlaps with a standard deal code
- two products share the same deal code and one is later removed

---

## Example scenarios

### Scenario 1: Mixed deal codes
Basket contains:

- Product A with `DEAL10`
- Product B with `DEAL20`
- Product C with `DEAL25`

Result:

- all three codes can be stored
- each item uses only its own matching best code
- totals are calculated per item

### Scenario 2: Offer code and deal code on different products
Basket contains:

- Product A with accepted offer code `OFFER-...`
- Product B with `DEAL20`

Result:

- both codes can be stored
- `OFFER-...` applies to Product A
- `DEAL20` applies to Product B

### Scenario 3: Offer and deal code overlap on one product
Basket contains:

- Product A with accepted offer code `OFFER-...`
- Product A also has native `DEAL20`

Result:

- `OFFER-...` wins on Product A
- `DEAL20` does not stack on that same item

### Scenario 4: Removing a second shared-code product
Basket contains:

- Product A with `OFFER-...` and native `DEAL20`
- Product B with `DEAL20`

Result:

- `OFFER-...` wins on Product A
- `DEAL20` wins on Product B

If Product B is removed:

- `DEAL20` is no longer a winning code on any item
- `DEAL20` is removed automatically
- only `OFFER-...` remains

---

## Discount calculation rules

Discounts are calculated per item using the matching Magento sales rule.

Supported rule action types:

- `by_percent`
- `by_fixed`
- `cart_fixed`

For each item, the module calculates the best applicable discount and applies that one only.

### VAT handling

The module works with VAT-inclusive product pricing and recalculates totals correctly after discount application.

Expected calculation model:

- product discount is applied at item level
- VAT reduces accordingly
- order totals reflect the discounted price correctly
- grand totals are not double-discounted

---

## Magento compatibility

Designed for Magento 2.4.x.

This module was built specifically for a Magento installation where:

- products use the `google_promo_code` product attribute
- deal codes are represented as dropdown attribute option values
- accepted-offer codes are real Magento sales-rule coupons
- Jet Theme / custom frontend product layouts are in use

---

## Module structure

Typical structure:

```text
app/code/Merlin/MultiCoupon/
├── Block/
│   └── Cart/
│       └── Coupons.php
├── Controller/
│   └── Cart/
│       ├── AddCoupon.php
│       ├── ClearCoupons.php
│       └── RemoveCoupon.php
├── Model/
│   ├── Config.php
│   ├── PromoCodeResolver.php
│   ├── QuoteCouponStorage.php
│   ├── RuleRepository.php
│   └── Discount/
│       ├── Calculator.php
│       ├── ItemRuleMatcher.php
│       └── MultiCoupon.php
├── view/
│   └── frontend/
│       ├── layout/
│       │   ├── catalog_product_view.xml
│       │   └── checkout_cart_index.xml
│       ├── templates/
│       │   ├── cart/
│       │   │   └── coupons.phtml
│       │   └── product/
│       │       └── deal_code.phtml
│       └── web/
│           └── css/
│               └── source/
│                   └── _module.less
├── etc/
│   ├── module.xml
│   ├── frontend/
│   └── sales.xml
└── registration.php
