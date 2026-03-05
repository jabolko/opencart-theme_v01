// =============================================================================
// theme.js — Custom theme JavaScript. ES5 only (no let/const/arrow functions).
// jQuery 3.7.1 is already loaded by OpenCart — do not load again.
// =============================================================================

(function ($) {
  'use strict';

  // ---------------------------------------------------------------------------
  // Cart icon HTML — OC's common.js replaces #cart > button innerHTML on every
  // cart add/edit/remove. We restore our SVG icon and desired layout afterward.
  // Structure: [.cart-icon-wrap [svg]] [.cart-price "3.06€"] [#cart-total.sr-only]
  // ---------------------------------------------------------------------------

  var CART_ICON_WRAP =
    '<span class="cart-icon-wrap">' +
    '<svg class="icon-cart" aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
    '<circle cx="9" cy="21" r="1"></circle>' +
    '<circle cx="20" cy="21" r="1"></circle>' +
    '<path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>' +
    '</svg>' +
    '</span>';

  // Guard against re-entrant calls when our DOM changes re-trigger the observer
  var cartReformatting = false;

  function reformatCart() {
    if (cartReformatting) { return; }
    cartReformatting = true;

    var $btn = $('#cart > .btn');
    if ($btn.length) {
      // OC sets: <span id="cart-total"><i class="fa fa-shopping-cart"></i> N text - price</span>
      // OR initial cart.twig render: <span class="cart-icon-wrap">...</span> <span id="cart-total" class="sr-only">N text - price</span>
      var totalText = ($('#cart-total').text() || '').trim();

      // Extract count (first integer) and price (everything after " - ")
      var countMatch = totalText.match(/^(\d+)/);
      var priceMatch = totalText.match(/[-\u2013]\s*(.+)$/);
      var count = countMatch ? parseInt(countMatch[1], 10) : 0;
      var price = priceMatch ? priceMatch[1].trim() : '';

      // Rebuild button content with our SVG icon and price label
      var html = CART_ICON_WRAP;
      if (count > 0 && price) {
        html += '<span class="cart-price">' + price + '</span>';
      }
      html += '<span id="cart-total" class="sr-only">' + totalText + '</span>';

      $btn.html(html);

      // Set badge count on the icon wrapper (not the whole button)
      var $wrap = $btn.find('.cart-icon-wrap');
      if (count > 0) {
        $wrap.attr('data-count', count);
      } else {
        $wrap.removeAttr('data-count');
      }
    }

    // Reset flag after all queued observer microtasks have fired
    setTimeout(function () { cartReformatting = false; }, 0);
  }

  // ---------------------------------------------------------------------------
  // Wishlist badge — OC's common.js does: $('#wishlist-total span').html(count)
  // We read that span and mirror the value as a data-count badge attribute.
  // ---------------------------------------------------------------------------

  function updateWishlistBadge() {
    var $el = $('#wishlist-total');
    if (!$el.length) { return; }
    var count = parseInt($el.find('span').text().trim(), 10) || 0;
    if (count > 0) {
      $el.attr('data-count', count);
    } else {
      $el.removeAttr('data-count');
    }
  }

  // ---------------------------------------------------------------------------
  // Init
  // ---------------------------------------------------------------------------

  $(document).ready(function () {
    reformatCart();
    updateWishlistBadge();

    if (window.MutationObserver) {
      var observer = new MutationObserver(function (mutations) {
        var i, target, inWishlist, inCart;
        inWishlist = false;
        inCart = false;

        for (i = 0; i < mutations.length; i++) {
          target = mutations[i].target;
          if (!inWishlist && ($(target).is('#wishlist-total') || $(target).closest('#wishlist-total').length)) {
            inWishlist = true;
          }
          if (!inCart && ($(target).is('#cart') || $(target).closest('#cart').length)) {
            inCart = true;
          }
        }

        if (inWishlist) { updateWishlistBadge(); }
        if (inCart) { reformatCart(); }
      });

      var wishlistEl = document.getElementById('wishlist-total');
      var cartEl = document.getElementById('cart');

      if (wishlistEl) {
        observer.observe(wishlistEl, { childList: true, subtree: true, characterData: true });
      }
      if (cartEl) {
        observer.observe(cartEl, { childList: true, subtree: true, characterData: true });
      }
    }
  });

}(jQuery));
