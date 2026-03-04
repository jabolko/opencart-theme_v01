# OpenCart 3.x Theme Hooks & Reference

Fill this file as you discover OC-specific patterns during development.

## Theme Registration
A theme is recognized by OC when its folder exists at:
`catalog/view/theme/<theme-name>/`

To activate: Admin → Design → Theme → select theme name.

## Template Override Lookup Order
OC looks for templates in this order:
1. `catalog/view/theme/<active-theme>/template/`
2. `catalog/view/theme/default/template/` (fallback)

So you only need to create the templates you want to customize.

## Stylesheet & Script Injection
Stylesheets and scripts are registered in the controller, not the template.
To add your theme CSS, use the `catalog/view/theme/otroskikoticek/` autoloaded startup event
or override the common/header controller.

Alternatively, inject directly in `header.twig`:
```twig
<link rel="stylesheet" href="{{ base }}catalog/view/theme/otroskikoticek/stylesheet/dist/theme.min.css" type="text/css">
```

## Key Body IDs (used for page-scoped CSS)
| Page           | Body/wrapper ID       |
|----------------|-----------------------|
| Homepage       | `#common-home`        |
| Category       | `#product-category`   |
| Product        | `#product-product`    |
| Cart           | `#checkout-cart`      |
| Checkout       | `#checkout-checkout`  |
| Account login  | `#account-login`      |
| Account register | `#account-register` |
| Search results | `#product-search`     |
| Contact        | `#information-contact`|
| Information    | `#information-information` |

## jQuery Events (fired by OC)
| Event         | Fired when                              |
|---------------|-----------------------------------------|
| `cart.update` | Cart item added/removed/updated (AJAX)  |

## Common OC CSS Classes (Bootstrap 3 + OC extensions)
| Class               | Purpose                                |
|---------------------|----------------------------------------|
| `.product-thumb`    | Product card wrapper in grid           |
| `.product-grid`     | Grid view container                    |
| `.product-list`     | List view container                    |
| `.button-group`     | Wishlist/compare button wrapper        |
| `.price-new`        | Sale/special price                     |
| `.price-old`        | Original price (shown with sale)       |
| `.price-tax`        | Tax display                            |
| `.image-additional` | Product gallery thumbnails wrapper     |
| `.thumbnails`       | Image thumbnail list                   |
| `.rating`           | Star rating wrapper                    |

## Notes
- Add discoveries here as you encounter OC-specific hooks and classes
- Check `opencart/catalog/controller/` when unsure what variables a template receives
