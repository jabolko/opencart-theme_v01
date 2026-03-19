# Project Overview

## Store Identity
- **Store name:** Otroški Kotiček
- **Language:** Slovenian (sl)
- **Market:** Slovenia
- **Audience:** Parents shopping for children's products (ages 0–12)

## Project Goal
Build a custom OpenCart 3.0.5.0 theme that replaces the default theme with a visually
distinct, fast, accessible, and conversion-optimized storefront.

## Scope — In
- Custom SCSS styles overriding and replacing default OpenCart CSS
- Custom Twig templates for: header, footer, home, category, product, cart
- Custom JavaScript for UI interactions
- Responsive design (mobile-first)
- Performance optimization (target: Lighthouse 90+)

## Scope — Out
- Admin panel UI (left untouched)
- Payment gateway integrations
- Custom OpenCart extensions/modules

## Scope — Modified (controllers/models touched for theme needs)
- `catalog/controller/product/category.php` — default sort, manufacturer filter, active filter chips
- `catalog/model/catalog/product.php` — manufacturer multi-select filter support
- `catalog/controller/extension/module/latest.php` — added manufacturer + minimum fields

## Success Criteria
- [ ] Lighthouse Performance score >= 90 on mobile
- [x] Lighthouse Accessibility score >= 90 (hit on homepage 2026-03-13)
- [ ] All pages render correctly on Chrome, Firefox, Safari, Edge
- [ ] Mobile breakpoint: 375px (iPhone SE) — no horizontal scroll
- [ ] Cart and checkout flow works end-to-end
- [ ] Page weight under 500KB on category page (uncompressed)
- [x] Lighthouse Best Practices 100 (homepage + category)
- [x] Lighthouse SEO 100 (homepage + category)