# Component Spec: Footer

## Files
- Template: `template/common/footer.twig`
- SCSS: `stylesheet/src/layout/_footer.scss`

## DOM Structure
```
<footer class="site-footer">
  <div class="container">

    <!-- Founder banner -->
    <div class="footer__banner">
      <img class="footer__photo">
      <div class="footer__quote">
        "quote text..."
        <cite class="footer__cite">— Meta</cite>
      </div>
      <a class="footer__review">
        <img class="footer__review-badge">
        <span class="footer__review-count"><strong>207</strong> ocen</span>
      </a>
    </div>

    <!-- 4-column grid -->
    <div class="row footer__grid">
      <div class="col-sm-3 footer__col">   ← Col 1: Store info
      <div class="col-sm-3 footer__col">   ← Col 2: Informacije
      <div class="col-sm-3 footer__col">   ← Col 3: Odkrijte
      <div class="col-sm-3 footer__col">   ← Col 4: Moj račun + social
    </div>

    <!-- Bottom bar -->
    <div class="footer__bottom">
      <p class="footer__copy">...</p>
    </div>
  </div>
</footer>
```

## Founder Banner
| Property | Value |
|----------|-------|
| Layout | `display: flex; align-items: center` |
| Padding | `1.76rem 5.29rem` (30px 90px) |
| Bottom border | `1px solid rgba(249, 185, 74, 0.15)` |
| Background | none (inherits dark footer bg) |
| Photo size | 108px circle, `border: 3px solid $color-primary`, `object-position: center top` |
| Quote | `font-size: 0.88rem (15px)`, italic, `color: rgba(245,245,245,.82)`, `max-width: ~400px` |
| Quote marks | Literal `&#8220;` / `&#8221;` in HTML (not CSS pseudo-elements) |
| Cite | `$color-primary` (gold), 600 weight, 13px |
| Review card | `background: rgba(0,0,0,.22)`, `border-radius: $radius-lg`, `padding: 18px 22px`, `margin-left: auto` |
| Badge height | 58px |
| Count | `<strong>207</strong> ocen` — strong in `$color-primary`, base font size |

## 4-Column Grid Content

### Col 1 — Naša trgovina
- Store name + address: Šolska ulica 2, 3330 Mozirje
- Opening hours (Tue–Fri 10–18, Sat 9–12, Mon/Sun closed)
- Phone: `tel:+38640611233`
- Email: `{{ contact }}`

### Col 2 — Informacije
- Varnost podatkov → `information_id=16`
- Pogoji sodelovanja → `information_id=5`
- Vračila → `{{ return }}`
- Zemljevid strani → `{{ sitemap }}`
- Darilni bon → `{{ voucher }}`
- Dostava → `#` (placeholder)
- Pomoč pri nakupu → `#` (placeholder)

### Col 3 — Odkrijte
- Prodaj oblačila → `information_id=9`
- Mnenja strank → `information_id=11`
- O nas → `#` (placeholder)
- Kontakt → `{{ contact }}`

### Col 4 — Moj račun + social
- `{{ account }}`, `{{ order }}`, `{{ wishlist }}`
- Social: Facebook, Instagram, X (Twitter)

## Social Buttons
- `background: rgba(245,245,245,.08)`, `color: $color-primary` (gold icons)
- Hover: `background: $color-primary`, `color: $color-surface-dark`, `translateY(-2px)`

## OC Variables Used
| Variable | Use |
|----------|-----|
| `{{ base }}` | Asset URL prefix |
| `{{ contact }}` | Contact page URL |
| `{{ return }}` | Returns page URL |
| `{{ sitemap }}` | Sitemap URL |
| `{{ voucher }}` | Gift voucher URL |
| `{{ account }}` | My account URL |
| `{{ order }}` | Order history URL |
| `{{ wishlist }}` | Wishlist URL |

## Color Scheme
| Element | Value |
|---------|-------|
| Background | `$color-surface-dark` (#3a3a3a) |
| Text on dark | `$color-text-on-dark` (#f5f5f5) |
| Grid headings | `$color-primary` (gold), uppercase, 10px |
| Link default | `rgba(245,245,245,.55)` |
| Link hover | `$color-primary` + `padding-left: $space-1` |
| Hours — day label | `rgba(245,245,245,.8)` |
| Hours — time | `rgba(245,245,245,.5)` |
| Copyright | `rgba(245,245,245,.28)` |

## Responsive (mobile — below sm)
- `.site-footer` top margin reduced to `$space-8`
- `.footer__banner` wraps (`flex-wrap: wrap`, `gap: $space-4`)
- `.footer__review` becomes horizontal row, full width
- `.footer__col` gets `margin-bottom: $space-6`

## Pending / Placeholders
- Dostava link → needs `information_id`
- Pomoč pri nakupu link → needs `information_id`
- O nas link → needs URL
