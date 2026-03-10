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

  // Mirrors main cart price, badge, and dropdown into the sticky nav cart
  function syncStickyCart() {
    var priceEl    = document.querySelector('.sticky-nav__cart-price');
    var stickyIconWrap = document.querySelector('#js-sticky-cart .cart-icon-wrap');
    var stickyCartWrap = document.getElementById('js-sticky-cart-wrap');

    // Sync price text
    if (priceEl) {
      var mainPrice = document.querySelector('#cart .cart-price');
      priceEl.textContent = mainPrice ? mainPrice.textContent : '';
    }

    // Sync badge count
    if (stickyIconWrap) {
      var mainIconWrap = document.querySelector('#cart .cart-icon-wrap');
      var count = mainIconWrap ? mainIconWrap.getAttribute('data-count') : null;
      if (count) {
        stickyIconWrap.setAttribute('data-count', count);
      } else {
        stickyIconWrap.removeAttribute('data-count');
      }
    }

    // Clone dropdown menu from main cart into sticky cart wrapper.
    // Update innerHTML in-place (never remove the element) so Bootstrap's
    // .open state on the parent is preserved while the cart refreshes.
    if (stickyCartWrap) {
      var mainDropdown = document.querySelector('#cart .dropdown-menu');
      var existing = stickyCartWrap.querySelector('.dropdown-menu');
      if (mainDropdown) {
        if (existing) {
          existing.innerHTML = mainDropdown.innerHTML;
        } else {
          var clone = mainDropdown.cloneNode(true);
          clone.classList.add('dropdown-menu-right');
          stickyCartWrap.appendChild(clone);
        }
      } else if (existing) {
        stickyCartWrap.removeChild(existing);
      }
    }
  }

  // ---------------------------------------------------------------------------
  // Toast notification — replaces OC's inline .alert-dismissible
  // ---------------------------------------------------------------------------

  var toastTimer = null;

  function showToast(message) {
    var toast = document.getElementById('oc-toast');
    if (!toast) { return; }
    var msgEl = toast.querySelector('.oc-toast__msg');
    if (msgEl) { msgEl.textContent = message; }
    if (toastTimer) { clearTimeout(toastTimer); }
    toast.classList.add('oc-toast--visible');
    toastTimer = setTimeout(function () { hideToast(); }, 3500);
  }

  function hideToast() {
    var toast = document.getElementById('oc-toast');
    if (toast) { toast.classList.remove('oc-toast--visible'); }
    if (toastTimer) { clearTimeout(toastTimer); toastTimer = null; }
  }

  // Guard against re-entrant calls when our DOM changes re-trigger the observer
  var cartReformatting = false;

  function reformatCart() {
    if (cartReformatting) { return; }
    cartReformatting = true;

    var $btn = $('#cart > .btn');
    // Skip while Bootstrap button-loading state is active (button is disabled)
    if ($btn.prop('disabled')) { cartReformatting = false; return; }
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

  // ---------------------------------------------------------------------------
  // Home arrivals — prev / next scroll buttons
  // Prev button starts hidden (CSS .home-arrivals__arrow--hidden).
  // It becomes visible on the first next-click.
  // ---------------------------------------------------------------------------

  function initArrivalsScroll() {
    var scroller = document.getElementById('js-arrivals-scroller');
    var btnNext = document.getElementById('js-arrivals-next');
    var btnPrev = document.getElementById('js-arrivals-prev');
    if (!scroller || !btnNext) { return; }

    function activatePrev() {
      if (!btnPrev) { return; }
      btnPrev.classList.remove('home-arrivals__arrow--hidden');
      btnPrev.removeAttribute('aria-hidden');
    }

    btnNext.addEventListener('click', function () {
      activatePrev();
      scroller.scrollBy({ left: 250 * 3, behavior: 'smooth' });
    });

    if (btnPrev) {
      btnPrev.addEventListener('click', function () {
        scroller.scrollBy({ left: -(250 * 3), behavior: 'smooth' });
      });
    }
  }

  // ---------------------------------------------------------------------------
  // Home reviews — prev / next scroll buttons (same pattern as arrivals)
  // ---------------------------------------------------------------------------

  function initReviewsScroll() {
    var scroller = document.getElementById('js-reviews-scroller');
    var btnNext = document.getElementById('js-reviews-next');
    var btnPrev = document.getElementById('js-reviews-prev');
    if (!scroller || !btnNext) { return; }

    function activatePrev() {
      if (!btnPrev) { return; }
      btnPrev.classList.remove('home-reviews__arrow--hidden');
      btnPrev.removeAttribute('aria-hidden');
    }

    btnNext.addEventListener('click', function () {
      activatePrev();
      scroller.scrollBy({ left: 300 * 3, behavior: 'smooth' });
    });

    if (btnPrev) {
      btnPrev.addEventListener('click', function () {
        scroller.scrollBy({ left: -(300 * 3), behavior: 'smooth' });
      });
    }
  }

  // ---------------------------------------------------------------------------
  // Sticky compact nav — clones category links from #menu, slides in on scroll
  // ---------------------------------------------------------------------------

  function initStickyNav() {
    var siteHeader = document.querySelector('.site-header');
    var stickyNav  = document.getElementById('js-sticky-nav');
    var stickyCats = document.getElementById('js-sticky-cats');
    if (!stickyNav || !siteHeader) { return; }

    // Clone top-level category <li> items from main nav
    var mainCats = document.querySelectorAll('#menu .navbar-nav:first-child > li');
    if (stickyCats && mainCats.length) {
      var i;
      for (i = 0; i < mainCats.length; i++) {
        stickyCats.appendChild(mainCats[i].cloneNode(true));
      }
    }

    // Show/hide based on scroll position
    var threshold = siteHeader.offsetHeight;
    function onScroll() {
      if (window.pageYOffset > threshold) {
        stickyNav.classList.add('sticky-nav--visible');
        stickyNav.removeAttribute('aria-hidden');
      } else {
        stickyNav.classList.remove('sticky-nav--visible');
        stickyNav.setAttribute('aria-hidden', 'true');
      }
    }

    window.addEventListener('scroll', onScroll);

    // Search button: scroll to top then focus main search
    var searchBtn = document.getElementById('js-sticky-search');
    if (searchBtn) {
      searchBtn.addEventListener('click', function () {
        window.scrollTo({ top: 0, behavior: 'smooth' });
        setTimeout(function () {
          var input = document.querySelector('#search .form-control');
          if (input) { input.focus(); }
        }, 400);
      });
    }

    // Cart button uses Bootstrap data-toggle="dropdown" — no custom handler needed
  }

  $(document).ready(function () {
    reformatCart();
    syncStickyCart();
    updateWishlistBadge();
    initArrivalsScroll();
    initReviewsScroll();
    initStickyNav();

    // Suppress OC's scroll-to-top animation on cart/wishlist/compare add
    var _origAnimate = $.fn.animate;
    $.fn.animate = function (props) {
      if (props && typeof props.scrollTop !== 'undefined' && props.scrollTop === 0 && $(this).is('html, body')) {
        return this;
      }
      return _origAnimate.apply(this, arguments);
    };

    // Intercept OC alert insertions at jQuery level — works even when #content
    // is absent (e.g. home page), where $(...).before() would silently no-op.
    var _origBefore = $.fn.before;
    $.fn.before = function (content) {
      if (typeof content === 'string' && content.indexOf('alert-dismissible') !== -1) {
        var text = $('<div>').html(content).text().replace(/\s*×\s*$/, '').trim();
        if (text) { showToast(text); }
        return this;
      }
      return _origBefore.apply(this, arguments);
    };

    // Keep cart dropdown open when clicking remove buttons.
    // Bootstrap 3 closes a dropdown on any click that bubbles to document.
    // Stopping propagation here prevents that without breaking the remove action.
    $(document).on('click', '#cart .cart-drop__remove, #js-sticky-cart-wrap .cart-drop__remove', function (e) {
      e.stopPropagation();
    });

    // Close toast on button click
    var toastCloseBtn = document.querySelector('.oc-toast__close');
    if (toastCloseBtn) {
      toastCloseBtn.addEventListener('click', function () { hideToast(); });
    }

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
        if (inCart) { reformatCart(); syncStickyCart(); }
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
