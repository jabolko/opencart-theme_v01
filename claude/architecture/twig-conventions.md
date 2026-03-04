# Twig Template Conventions

## OpenCart 3.x Twig Version
OC 3.0.5.0 ships Twig 1.x. Do not use Twig 3 features:
- No arrow functions in filters
- No `filter()` with lambdas
- Available: `|batch()`, `|slice()`, `|keys()`, `|merge()`, `|default()`

## Template Locations
All templates in: `opencart/catalog/view/theme/otroskikoticek/template/`

| File                         | OC Page          | Body wrapper ID     |
|------------------------------|------------------|---------------------|
| `common/header.twig`         | All pages        | `#top`, `<header>`  |
| `common/footer.twig`         | All pages        | `<footer>`          |
| `common/home.twig`           | Homepage         | `#common-home`      |
| `product/category.twig`      | Category listing | `#product-category` |
| `product/product.twig`       | Single product   | `#product-product`  |
| `checkout/cart.twig`         | Cart page        | `#checkout-cart`    |

## Available Variables by Template

### All Templates
- `{{ title }}` — page title (for `<title>` tag)
- `{{ base }}` — base URL for assets (e.g., `http://localhost:8080/`)
- `{{ lang }}` — language code (e.g., `sl`)
- `{{ direction }}` — `ltr` or `rtl`
- `{{ styles }}` — array of stylesheet objects to inject
- `{{ scripts }}` — array of script objects to inject

### header.twig
- `{{ logo }}` — logo image URL
- `{{ name }}` — store name
- `{{ home }}` — homepage URL
- `{{ currency }}` — rendered currency switcher HTML
- `{{ language }}` — rendered language switcher HTML
- `{{ search }}` — rendered search box HTML
- `{{ cart }}` — rendered mini-cart HTML
- `{{ menu }}` — rendered navigation menu HTML
- `{{ logged }}` — boolean: is user logged in
- `{{ account }}`, `{{ logout }}`, `{{ register }}`, `{{ login }}` — account URLs
- `{{ wishlist }}`, `{{ shopping_cart }}`, `{{ checkout }}` — navigation URLs

### product/category.twig
- `{{ heading_title }}` — category name
- `{{ breadcrumbs }}` — array of `{text, href}`
- `{{ thumb }}` — category image URL
- `{{ description }}` — category description (raw HTML — do not escape)
- `{{ categories }}` — sub-category array `{name, href, column}`
- `{{ products }}` — product objects array (see Product Object below)
- `{{ sorts }}` — sort options `{text, value, href}`
- `{{ limits }}` — per-page options `{text, value, href}`
- `{{ sort }}`, `{{ order }}` — current sort field and direction
- `{{ limit }}` — current limit value
- `{{ pagination }}` — rendered pagination HTML
- `{{ results }}` — "Showing X to Y of Z" string

### Product Object (in category loop)
```twig
{% for product in products %}
  {{ product.product_id }}
  {{ product.name }}
  {{ product.href }}
  {{ product.thumb }}
  {{ product.description }}  {# short description #}
  {{ product.price }}        {# formatted: "€19,99" #}
  {{ product.special }}      {# formatted sale price or empty #}
  {{ product.tax }}
  {{ product.rating }}       {# integer 0-5 #}
  {{ product.minimum }}      {# min order qty #}
{% endfor %}
```

### product/product.twig
- `{{ heading_title }}` — product name
- `{{ thumb }}` — main image URL
- `{{ images }}` — gallery array `{popup, thumb}`
- `{{ price }}` — formatted regular price
- `{{ special }}` — sale price (empty if none)
- `{{ description }}` — full HTML description (raw — do not escape)
- `{{ options }}` — options array (size, color, etc.)
- `{{ minimum }}` — minimum order quantity
- `{{ reviews }}` — rendered reviews section HTML
- `{{ tags }}` — array of tag link objects

### checkout/cart.twig
- `{{ products }}` — cart items: `{key, thumb, name, model, option, quantity, price, total, href, remove}`
- `{{ totals }}` — totals array `{title, text}`
- `{{ checkout }}` — checkout URL
- `{{ continue }}` — continue shopping URL

## Rules
- Never add PHP logic to templates — controllers provide all data
- Use `{{ variable|e }}` (escape) for user-generated text displayed as raw string
- Render asset URLs as: `{{ base }}catalog/view/theme/otroskikoticek/stylesheet/dist/theme.min.css`
- Do not hardcode language strings — use the `{{ text_* }}` variables provided by the controller
- Keep the wrapping container ID intact (e.g., `id="product-category"`) — used by OC JS
- `{{ description }}` fields come pre-sanitized from the admin — output them with `|raw` if needed
