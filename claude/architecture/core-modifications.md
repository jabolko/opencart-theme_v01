# Core PHP Modifications (convert to OCMOD before launch)

These are OC core files we modified directly. They will be overwritten by OC updates.
Before going live, bundle all changes into a single OCMOD XML extension.

Status: ❌ = direct edit (at risk) | ✅ = converted to OCMOD

---

## 1. catalog/controller/checkout/checkout.php ❌

**What:** Added cart products + totals data for checkout sidebar
**Why:** OC default doesn't pass cart data to checkout template — sidebar needs it
**Lines changed:** After `$data['shipping_required']`, added ~60 lines
**Changes:**
- Load `model/catalog/product` and `model/tool/image`
- Loop `$this->cart->getProducts()` → build `$data['cart_products'][]` with image, manufacturer, price
- Calculate totals via OC total extensions → build `$data['cart_totals'][]`

---

## 2. catalog/controller/checkout/confirm.php ❌

**What:** Added product images, manufacturer, shipping address summary, payment title
**Why:** Default confirm.twig only has product name/model/price — no images for our V1 card design
**Lines changed:** ~15 lines added in the product loop + 10 lines for address/payment data
**Changes:**
- Load `model/tool/image` and `model/catalog/product`
- Add `image`, `manufacturer` to each `$data['products'][]`
- Add `$data['shipping_address_summary']` (formatted string from session)
- Add `$data['shipping_title']` and `$data['payment_title']`

---

## 3. catalog/model/account/customer.php ❌

**What:** PHP 8.4 compatibility fix for undefined `approval` array key
**Why:** `$customer_group_info['approval']` throws warning on PHP 8.4 when key doesn't exist
**Lines changed:** Lines 14, 18 — no structural change, just null safety
**Changes:**
- This is a PHP version compatibility fix, not a feature
- May be fixed in future OC versions — check before converting

---

## 4. admin/controller/marketplace/install.php ❌

**What:** Relaxed allowed installation paths for extensions
**Why:** Third-party extensions write to paths OC 3.0.5.0 doesn't whitelist (e.g. `admin/controller/module/`)
**Lines changed:** Replaced specific path whitelist with broader `admin/`, `catalog/`, `system/`, `image/`
**Changes:**
- `$allowed` array simplified to 4 top-level paths
- Security note: this is less restrictive — consider tightening for production

---

## 5. catalog/controller/product/product.php ❌

**What:** Added similar products query, `price_raw`/`special_raw` for schema.org, manufacturer data
**Why:** Product page needs brand-matched similar products, schema.org needs numeric prices
**Lines changed:** ~80 lines added
**Changes:**
- Two-step similar products query (brand+size+gender, then backfill)
- `price_raw` and `special_raw` via `number_format()` for JSON-LD
- Product attributes lookup (72=Velikost, 73=Spol, 75=Znamka)

---

## 6. catalog/controller/product/category.php ❌

**What:** 3-level category tree, filter groups with counts, manufacturer counts, sort options
**Why:** Custom category page sidebar needs this data
**Lines changed:** ~100 lines added
**Changes:**
- Category tree with `html_entity_decode()` on URLs
- Filter groups with product counts
- Manufacturer counts for sidebar
- Sort options: Zadnje dodano, Najnižji ceni, Najvišji ceni
- Active filter chips with decoded remove URLs

---

## 7. catalog/controller/checkout/cart.php ❌

**What:** Added manufacturer, product_id, shipping estimate, empty cart handling
**Why:** Custom cart page needs brand names, product IDs for recently viewed, shipping estimate
**Lines changed:** ~30 lines added
**Changes:**
- Added `manufacturer` and `product_id` to product data array
- Shipping estimate from flat rate config
- Empty cart data handling

---

## 8. catalog/controller/extension/module/latest.php ❌

**What:** Added manufacturer and minimum to product data
**Why:** Product card component needs brand name display
**Lines changed:** ~5 lines
**Changes:**
- Added `manufacturer` and `minimum` to `$data['products'][]`

---

## 9. catalog/controller/checkout/guest.php ❌

**What:** PHP 8.4 fix — `isset()` for `company` and `address_2` keys
**Why:** We hide these fields (CSS), so they're not submitted. PHP 8.4 throws warnings on undefined keys.
**Lines changed:** Lines 256, 258, 307, 309
**Changes:**
- `$this->request->post['company']` → `isset(...) ? ... : ''`
- `$this->request->post['address_2']` → `isset(...) ? ... : ''`

---

## 10. catalog/model/account/address.php ❌

**What:** PHP 8.4 fix — `isset()` for `company` and `address_2` in addAddress + editAddress
**Why:** Same as above — hidden fields not submitted
**Lines changed:** Lines 4, 16
**Changes:**
- `$data['company']` → `isset($data['company']) ? $data['company'] : ''`
- `$data['address_2']` → `isset($data['address_2']) ? $data['address_2'] : ''`

---

## 11. catalog/controller/checkout/payment_address.php ❌

**What:** Prevent duplicate address creation on re-submit
**Why:** OC calls addAddress() every time user submits "new address" form, even if identical address exists. Clicking "Uredi" and re-submitting creates duplicates.
**Lines changed:** Around line 152 — added duplicate check before addAddress()
**Changes:**
- Check existing addresses for matching firstname, lastname, address_1, city, postcode, country_id
- Only call addAddress() if no match found
- Use existing address_id if match found

---

## How to verify this list is complete

Run this command to find all modified PHP files vs the OC default:
```bash
git diff --name-only HEAD -- '*.php' | grep -v 'view/theme/otroskikoticek'
```

Any `.php` file that's NOT inside our theme folder is a core modification.

---

## OCMOD conversion checklist (pre-launch)

- [ ] Create `otroskikoticek-core-mods.ocmod.xml`
- [ ] Test: install OCMOD, clear OC modification cache, verify all pages work
- [ ] Test: revert all direct PHP edits to OC defaults
- [ ] Test: full checkout flow (guest + logged in)
- [ ] Test: product page, category page, cart page
- [ ] Test: extension installer
