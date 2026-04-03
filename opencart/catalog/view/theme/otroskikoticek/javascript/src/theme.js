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

  window.showToast = showToast;

  function showToast(message) {
    // Suppress toast on checkout — errors shown inline
    if (document.querySelector('.ck-page')) { return; }
    var toast = document.getElementById('oc-toast');
    if (!toast) { return; }
    var msgEl = toast.querySelector('.oc-toast__msg');
    if (msgEl) {
      // Strip HTML tags, show plain text only
      var tmp = document.createElement('div');
      tmp.innerHTML = message;
      msgEl.textContent = tmp.textContent || tmp.innerText || message;
    }
    if (toastTimer) { clearTimeout(toastTimer); }
    // Reset animation
    toast.classList.remove('oc-toast--visible', 'oc-toast--dismissing');
    // Force reflow to restart animation
    void toast.offsetWidth;
    toast.classList.add('oc-toast--visible');
    toastTimer = setTimeout(function () { hideToast(); }, 2500);
  }

  function hideToast() {
    var toast = document.getElementById('oc-toast');
    if (toast) {
      toast.classList.add('oc-toast--dismissing');
      setTimeout(function() {
        toast.classList.remove('oc-toast--visible', 'oc-toast--dismissing');
      }, 350);
    }
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
  // Mobile cart sheet — refresh body + footer + shipping bar from AJAX
  // ---------------------------------------------------------------------------

  function updateCartSheetFromHtml($html) {
    var sheetBody = document.getElementById('js-cart-sheet-body');
    var sheet = document.getElementById('js-cart-sheet');
    if (!sheetBody || !sheet) return;

    var $items = $html.find('.cart-drop__items');
    var $empty = $html.find('.cart-drop__empty');
    var $newFooter = $html.find('.cart-drop__footer');

    // Update items
    if ($items.length) {
      $(sheetBody).html($items[0].outerHTML);
    } else if ($empty.length) {
      $(sheetBody).html($empty[0].outerHTML);
    }

    // Update footer
    var $footer = $(sheet).find('.cart-sheet__footer');
    if ($newFooter.length) {
      if ($footer.length) {
        $footer.html($newFooter.html());
      } else {
        $(sheet).append('<div class="cart-sheet__footer">' + $newFooter.html() + '</div>');
      }
    } else if ($footer.length) {
      $footer.remove();
    }

    // Update shipping progress on both mobile + desktop
    if (typeof window.updateCartShipping === 'function') {
      window.updateCartShipping();
    }
  }


  // Hook into OC's cart AJAX — fires when common/cart/info completes
  $(document).ajaxComplete(function(e, xhr, settings) {
    if (settings.url && settings.url.indexOf('common/cart/info') !== -1) {
      var html = xhr.responseText || '';
      if (html) {
        updateCartSheetFromHtml($(html));
      }
      // Restart reservation timers after cart reload
      if (typeof window.tickCartDropTimers === 'function') {
        setTimeout(window.tickCartDropTimers, 100);
      }
    }
  });

  // Instant product card update after add-to-cart
  $(document).ajaxComplete(function(e, xhr, settings) {
    if (!settings.url || settings.url.indexOf('checkout/cart/add') === -1) return;
    try {
      var json = JSON.parse(xhr.responseText);
    } catch (ex) { return; }
    if (!json || !settings.data) return;

    // Extract product_id from POST data
    var match = settings.data.match(/product_id=(\d+)/);
    if (!match) return;
    var pid = match[1];

    if (json.success) {
      // Find all product cards with this product_id — swap to checkmark + V KOŠARICI
      var checkSvg = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
      var allCards = document.querySelectorAll('.product-card');
      for (var i = 0; i < allCards.length; i++) {
        var btn = allCards[i].querySelector('.product-card__cart');
        if (btn && btn.getAttribute('onclick') && btn.getAttribute('onclick').indexOf("'" + pid + "'") !== -1) {
          // Swap to green checkmark
          btn.disabled = true;
          btn.removeAttribute('onclick');
          btn.className = 'product-card__cart product-card__cart--in-cart';
          btn.innerHTML = checkSvg;
          // Swap label to V KOŠARICI
          var labels = allCards[i].querySelector('.product-card__labels');
          if (labels) {
            labels.innerHTML = '<span class="product-label product-label--in-cart">V KOŠARICI</span>';
          }
        }
      }
    }

    if (json.error && json.error.warning) {
      // Show error toast for reservation failed / already in cart
      if (typeof showToast === 'function') {
        showToast(json.error.warning);
      }
    }
  });

  // Keep for MutationObserver compatibility (reformatCart still needs it)
  function refreshCartSheet() {}

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
  // Mobile nav drawer — hamburger → slide-in panel with accordion categories
  // ---------------------------------------------------------------------------

  function initMobileMenu() {
    var toggle   = document.getElementById('js-mobile-menu-toggle');
    var drawer   = document.getElementById('js-mobile-nav');
    var overlay  = document.getElementById('js-mobile-overlay');
    var closeBtn = document.getElementById('js-mobile-nav-close');
    var listEl   = document.getElementById('js-mobile-nav-list');
    if (!toggle || !drawer) { return; }

    // Helper: build a circle image element from a src URL
    function makeCatImg(src) {
      var img = document.createElement('img');
      img.src = src;
      img.className = 'mobile-nav__cat-img';
      img.alt = '';
      img.setAttribute('aria-hidden', 'true');
      return img;
    }

    // Clone top-level category links from #menu into the drawer nav list
    if (listEl) {
      var mainItems = document.querySelectorAll('#menu .navbar-nav:first-child > li');
      var i, li, a, row, toggleBtn, subList, subLinks, subLi, subA, j, imgSrc;

      for (i = 0; i < mainItems.length; i++) {
        var origLi = mainItems[i];
        var origA  = origLi.querySelector('a');
        if (!origA) { continue; }
        // Skip items with href="#" — these are hardcoded in the drawer HTML
        if (origA.getAttribute('href') === '#') { continue; }

        imgSrc = origA.getAttribute('data-img') || '';
        var name = (origA.textContent || '').replace(/\s+/g, ' ').trim();

        li = document.createElement('li');
        li.className = 'mobile-nav__item';

        var subMenu = origLi.querySelector('.dropdown-menu');
        if (subMenu) {
          // Category has children — pure accordion: single button row (no separate link)
          row = document.createElement('button');
          row.type = 'button';
          row.className = 'mobile-nav__item-row';
          row.setAttribute('aria-expanded', 'false');
          row.setAttribute('aria-label', name);

          if (imgSrc) { row.appendChild(makeCatImg(imgSrc)); }

          var catInfo = document.createElement('span');
          catInfo.className = 'mobile-nav__cat-info';

          var nameSpan = document.createElement('span');
          nameSpan.className = 'mobile-nav__cat-name';
          nameSpan.textContent = name;
          catInfo.appendChild(nameSpan);

          var count = origA.getAttribute('data-count') || '';
          if (count) {
            var countSpan = document.createElement('span');
            countSpan.className = 'mobile-nav__cat-count';
            countSpan.textContent = count + ' kosov';
            catInfo.appendChild(countSpan);
          }

          row.appendChild(catInfo);

          var chevron = document.createElement('span');
          chevron.className = 'mobile-nav__chevron';
          chevron.setAttribute('aria-hidden', 'true');
          chevron.innerHTML =
            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">' +
            '<polyline points="9 18 15 12 9 6"></polyline></svg>';
          row.appendChild(chevron);

          subList = document.createElement('ul');
          subList.className = 'mobile-nav__sub-list';

          // First item: "Vse za [category name]" → category page
          subLi = document.createElement('li');
          subA  = document.createElement('a');
          subA.href = origA.href;
          subA.className = 'mobile-nav__sub-all';
          subA.textContent = 'Vse za ' + name.toLowerCase();
          subLi.appendChild(subA);
          subList.appendChild(subLi);

          // Subcategories from the desktop dropdown
          subLinks = subMenu.querySelectorAll('.dropdown-inner a');
          for (j = 0; j < subLinks.length; j++) {
            subLi = document.createElement('li');
            subA  = document.createElement('a');
            subA.href = subLinks[j].href;
            subA.textContent = (subLinks[j].textContent || '').trim();
            subLi.appendChild(subA);
            subList.appendChild(subLi);
          }

          li.appendChild(row);
          li.appendChild(subList);

        } else {
          // Simple link — no children
          a = document.createElement('a');
          a.href = origA.href;
          a.className = 'mobile-nav__link';
          if (imgSrc) { a.appendChild(makeCatImg(imgSrc)); }
          a.appendChild(document.createTextNode(name));
          li.appendChild(a);
        }

        listEl.appendChild(li);
      }
    }

    // Inject counts into the hardcoded Paket presenečenja drawer item
    (function () {
      var paketData = document.getElementById('js-paket-data');
      var paketLi   = document.getElementById('js-mobile-paket');
      if (!paketData || !paketLi) { return; }

      function appendCount(el, cnt) {
        if (!el || !cnt || parseInt(cnt, 10) <= 0) { return; }
        var sp = document.createElement('span');
        sp.className = 'mobile-nav__cat-count';
        sp.textContent = cnt + ' kosov';
        el.appendChild(sp);
      }

      // Total count on the accordion button — styled span below the name
      var catInfo = paketLi.querySelector('.mobile-nav__cat-info');
      appendCount(catInfo, paketData.getAttribute('data-total'));

      // Sub-item counts — inline "(N)" format, same as regular category sub-items
      var dekliceCnt = paketData.getAttribute('data-deklice');
      var deckeCnt   = paketData.getAttribute('data-decke');
      var linkD = document.getElementById('js-paket-link-deklice');
      var linkK = document.getElementById('js-paket-link-decke');
      if (linkD && dekliceCnt && parseInt(dekliceCnt, 10) > 0) {
        linkD.textContent = linkD.textContent + ' (' + dekliceCnt + ')';
      }
      if (linkK && deckeCnt && parseInt(deckeCnt, 10) > 0) {
        linkK.textContent = linkK.textContent + ' (' + deckeCnt + ')';
      }
    }());

    // Populate INFO section from .nav-item-secondary dropdown (if present)
    var infoList  = document.getElementById('js-mobile-info-list');
    if (infoList) {
      var infoLinks = document.querySelectorAll('#menu .nav-item-secondary .dropdown-inner a');
      for (var k = 0; k < infoLinks.length; k++) {
        var iLi = document.createElement('li');
        iLi.className = 'mobile-nav__item';
        var iA = document.createElement('a');
        iA.href = infoLinks[k].href;
        iA.className = 'mobile-nav__link';
        iA.textContent = (infoLinks[k].textContent || '').trim();
        iLi.appendChild(iA);
        infoList.appendChild(iLi);
      }
    }

    function openDrawer() {
      drawer.classList.add('mobile-nav--open');
      drawer.setAttribute('aria-hidden', 'false');
      toggle.setAttribute('aria-expanded', 'true');
      document.body.classList.add('mobile-nav-open');
    }

    function closeDrawer() {
      drawer.classList.remove('mobile-nav--open');
      drawer.setAttribute('aria-hidden', 'true');
      toggle.setAttribute('aria-expanded', 'false');
      document.body.classList.remove('mobile-nav-open');
    }

    toggle.addEventListener('click', function () {
      if (drawer.classList.contains('mobile-nav--open')) {
        closeDrawer();
      } else {
        openDrawer();
      }
    });

    if (closeBtn) { closeBtn.addEventListener('click', closeDrawer); }
    if (overlay)  { overlay.addEventListener('click', closeDrawer); }

    document.addEventListener('keydown', function (e) {
      if ((e.key === 'Escape' || e.keyCode === 27) &&
          drawer.classList.contains('mobile-nav--open')) {
        closeDrawer();
      }
    });

    // Delegated handler: accordion toggles + close-on-link-click
    drawer.addEventListener('click', function (e) {
      var target = e.target;
      while (target && target !== drawer) {
        // Accordion toggle — expand/collapse sub-list
        if (target.classList && target.classList.contains('mobile-nav__item-row')) {
          var li  = target.parentElement;           // .mobile-nav__item
          var sub = li ? li.querySelector('.mobile-nav__sub-list') : null;
          if (sub) {
            var isOpen = sub.classList.contains('mobile-nav__sub-list--open');
            sub.classList.toggle('mobile-nav__sub-list--open', !isOpen);
            target.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
          }
          return;
        }
        // Real link — close drawer and navigate
        if (target.tagName === 'A' &&
            target.getAttribute('href') &&
            target.getAttribute('href') !== '#') {
          closeDrawer();
          return;
        }
        target = target.parentNode;
      }
    });

    // Mobile search button — scroll to top and focus the main search input
    // Mobile search bottom sheet
    var searchBtn = document.getElementById('js-mobile-search-btn');
    var stickySearchBtn = document.getElementById('js-sticky-search');
    var searchSheet = document.getElementById('js-search-sheet');
    var searchOverlay = document.getElementById('js-search-overlay');
    var searchInput = document.getElementById('js-search-input');
    var searchClear = document.getElementById('js-search-clear');

    function openSearch() {
      if (!searchSheet) return;
      searchSheet.classList.add('is-open');
      if (searchOverlay) searchOverlay.classList.add('is-open');
      document.body.style.overflow = 'hidden';
      if (searchInput) setTimeout(function() { searchInput.focus(); }, 300);
    }

    function closeSearch() {
      if (!searchSheet) return;
      searchSheet.classList.remove('is-open');
      if (searchOverlay) searchOverlay.classList.remove('is-open');
      document.body.style.overflow = '';
    }

    if (searchBtn) searchBtn.addEventListener('click', openSearch);
    if (stickySearchBtn) stickySearchBtn.addEventListener('click', openSearch);
    if (searchOverlay) searchOverlay.addEventListener('click', closeSearch);

    // Clear button
    if (searchClear && searchInput) {
      searchInput.addEventListener('input', function() {
        searchClear.style.display = this.value ? 'block' : 'none';
      });
      searchClear.addEventListener('click', function() {
        searchInput.value = '';
        searchClear.style.display = 'none';
        searchInput.focus();
      });
    }

    // Submit on Enter
    if (searchInput) {
      searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.keyCode === 13) {
          var q = this.value.trim();
          if (q) {
            window.location = 'index.php?route=product/search&search=' + encodeURIComponent(q);
          }
        }
      });
    }

    // Close on Escape
    document.addEventListener('keydown', function(e) {
      if ((e.key === 'Escape' || e.keyCode === 27) && searchSheet && searchSheet.classList.contains('is-open')) {
        closeSearch();
      }
    });
  }

  // ---------------------------------------------------------------------------
  // Footer accordion — collapsible link groups on mobile (<768px)
  // Toggles aria-expanded on .site-footer__col-title--toggle <p> elements;
  // CSS adjacent-sibling (+) selector shows/hides the following <ul>.
  // ---------------------------------------------------------------------------

  function initFooterAccordion() {
    var toggles = document.querySelectorAll('.site-footer__col-title--toggle');
    if (!toggles.length) { return; }

    function handleToggle(el) {
      var isOpen = el.getAttribute('aria-expanded') === 'true';
      el.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
    }

    var i;
    for (i = 0; i < toggles.length; i++) {
      (function (el) {
        el.addEventListener('click', function () { handleToggle(el); });
        el.addEventListener('keydown', function (e) {
          if (e.key === 'Enter' || e.keyCode === 13 ||
              e.key === ' '     || e.keyCode === 32) {
            e.preventDefault();
            handleToggle(el);
          }
        });
      }(toggles[i]));
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

  // ---------------------------------------------------------------------------
  // Recently viewed — save product data to localStorage on product page
  // ---------------------------------------------------------------------------
  function trackRecentlyViewed() {
    var el = document.getElementById('product-product');
    if (!el) return;
    var KEY = 'recently_viewed';
    var MAX = 20;

    // Read product data from the page
    var pid = el.getAttribute('data-product-id');
    if (!pid) return;
    var nameEl = el.querySelector('.pdp-info__title');
    var brandEl = el.querySelector('.pdp-info__brand');
    var priceEl = el.querySelector('.pdp-info__price-current');
    var imgEl = el.querySelector('.pdp-gallery__slide img');
    var linkEl = el.querySelector('.pdp-info__title');

    var item = {
      id: pid,
      name: nameEl ? nameEl.textContent.trim() : '',
      brand: brandEl ? brandEl.textContent.trim() : '',
      price: priceEl ? priceEl.textContent.trim() : '',
      thumb: imgEl ? imgEl.getAttribute('src') : '',
      href: window.location.href
    };

    var stored = [];
    try { stored = JSON.parse(localStorage.getItem(KEY)) || []; } catch (e) { stored = []; }

    // Remove duplicate
    stored = stored.filter(function (p) { return p.id !== pid; });
    // Add to front
    stored.unshift(item);
    // Cap
    if (stored.length > MAX) stored = stored.slice(0, MAX);

    localStorage.setItem(KEY, JSON.stringify(stored));
  }

  // ---------------------------------------------------------------------------
  // Cart page — reservation timer, shipping bar, coupon, recently viewed
  // Only runs when .cart-page is present in the DOM.
  // ---------------------------------------------------------------------------
  function initCartPage() {
    var page = document.querySelector('.cart-page');
    if (!page) return;

    // -- Server-synced reservation timer --
    var RESERVE_MS = 30 * 60 * 1000; // 30 min

    // Calculate clock offset: server time - client time
    var serverTimeAttr = page.getAttribute('data-server-time');
    var serverNow = serverTimeAttr ? new Date(serverTimeAttr.replace(' ', 'T')).getTime() : Date.now();
    var clockOffset = serverNow - Date.now();

    var items = document.querySelectorAll('.cart-item[data-time-added]');
    var globalTimerEl = document.getElementById('js-cart-timer');
    var reserveBanner = document.getElementById('js-cart-reserve');

    function formatTime(ms) {
      if (ms <= 0) return '0:00';
      var totalSec = Math.floor(ms / 1000);
      var min = Math.floor(totalSec / 60);
      var sec = totalSec % 60;
      return min + ':' + (sec < 10 ? '0' : '') + sec;
    }

    function tickTimers() {
      var now = Date.now() + clockOffset;
      var minRemaining = Infinity;

      for (var i = 0; i < items.length; i++) {
        var addedStr = items[i].getAttribute('data-time-added');
        var added = new Date(addedStr.replace(' ', 'T')).getTime();
        var remaining = RESERVE_MS - (now - added);
        if (remaining < minRemaining) minRemaining = remaining;

        var timerEl = items[i].querySelector('.cart-item__timer-val');
        if (timerEl) timerEl.textContent = formatTime(remaining);
      }

      if (globalTimerEl) globalTimerEl.textContent = formatTime(minRemaining);

      if (minRemaining <= 0) {
        if (reserveBanner) reserveBanner.classList.add('cart-reserve--expired');
        return;
      }
      if (minRemaining < 5 * 60 * 1000) {
        if (reserveBanner) reserveBanner.classList.add('cart-reserve--urgent');
      }

      setTimeout(tickTimers, 1000);
    }

    if (globalTimerEl && items.length) tickTimers();

    // -- Remove item --
    var removeButtons = document.querySelectorAll('.cart-item__remove');
    for (var r = 0; r < removeButtons.length; r++) {
      removeButtons[r].addEventListener('click', function () {
        var cartId = this.getAttribute('data-cart-id');
        if (cartId) {
          cart.remove(cartId);
          setTimeout(function () { location.reload(); }, 600);
        }
      });
    }

    // -- Free shipping bar --
    function updatePageShipping() {
      var el = document.getElementById('js-cart-page-shipping');
      if (!el) return;
      var threshold = 55;
      var totalEl = document.querySelector('.cart-summary__row--total span:last-child');
      if (!totalEl) return;
      var totalText = totalEl.textContent.replace(/[^\d,.]/g, '').replace(',', '.');
      var cartTotal = parseFloat(totalText) || 0;
      var remaining = Math.max(0, threshold - cartTotal);
      var pct = Math.min(100, (cartTotal / threshold) * 100);

      var truckSvg = '<svg class="cart-shipping__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>';
      var checkSvg = '<svg class="cart-shipping__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>';

      if (cartTotal > 0 && remaining > 0) {
        el.className = 'cart-shipping cart-shipping--progress';
        el.innerHTML = '<div class="cart-shipping__header">' + truckSvg + '<span class="cart-shipping__text">Do brezplačne poštnine ti manjka še <strong>' + remaining.toFixed(2).replace('.', ',') + ' €</strong></span></div><div class="cart-shipping__bar"><div class="cart-shipping__fill" style="width:' + pct + '%"></div></div>';
      } else if (cartTotal >= threshold) {
        el.className = 'cart-shipping cart-shipping--free';
        el.innerHTML = '<div class="cart-shipping__header">' + checkSvg + '<span class="cart-shipping__text cart-shipping__text--free">Poštnina je brezplačna!</span></div>';
      } else {
        el.innerHTML = '';
        el.className = 'cart-shipping';
      }
    }

    updatePageShipping();

    // -- Coupon code --
    var couponBtn = document.getElementById('js-coupon-btn');
    var couponInput = document.getElementById('js-coupon-input');
    if (couponBtn && couponInput) {
      couponBtn.addEventListener('click', function () {
        var code = couponInput.value.trim();
        if (!code) return;
        $.ajax({
          url: 'index.php?route=extension/total/coupon/coupon',
          type: 'post',
          data: 'coupon=' + encodeURIComponent(code),
          dataType: 'json',
          success: function (json) {
            if (json.redirect) {
              location = json.redirect;
            } else if (json.error) {
              $('.cart-coupon .alert').remove();
              $('.cart-coupon').prepend('<div class="alert alert-danger alert-dismissible"><i class="fa fa-exclamation-circle"></i> ' + json.error + ' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');
            }
          }
        });
      });
      couponInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.keyCode === 13) { couponBtn.click(); }
      });
    }

    // -- Hide timer when cart empty --
    var cartItemsEl = document.getElementById('js-cart-items');
    if (cartItemsEl && cartItemsEl.children.length === 0 && reserveBanner) {
      reserveBanner.style.display = 'none';
    }

    // -- Recently viewed --
    var recentWrap = document.getElementById('js-cart-recent');
    var recentScroller = document.getElementById('js-cart-recent-scroller');
    if (recentWrap && recentScroller) {
      var viewed = [];
      try { viewed = JSON.parse(localStorage.getItem('recently_viewed')) || []; } catch (e) { viewed = []; }

      var cartIds = {};
      var cartEls = document.querySelectorAll('.cart-item[data-product-id]');
      for (var ci = 0; ci < cartEls.length; ci++) {
        cartIds[cartEls[ci].getAttribute('data-product-id')] = true;
      }
      viewed = viewed.filter(function (p) { return !cartIds[p.id]; });

      if (viewed.length > 0) {
        // Fetch stock status first, then render (filtering out sold)
        var allPids = [];
        for (var vi = 0; vi < Math.min(viewed.length, 15); vi++) { allPids.push(viewed[vi].id); }

        function esc(str) {
          if (!str) return '';
          var d = document.createElement('div');
          d.appendChild(document.createTextNode(str));
          return d.innerHTML;
        }

        function renderRecentCards(statuses) {
          var checkSvg = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
          var cartSvg = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>';
          var labelMap = {
            'in_cart':  '<span class="product-label product-label--in-cart">V KOŠARICI</span>',
            'reserved': '<span class="product-label product-label--reserved">REZERVIRANO</span>'
          };
          var html = '';
          var count = 0;
          for (var v = 0; v < viewed.length && count < 10; v++) {
            var p = viewed[v];
            var st = statuses[p.id] || 'available';
            // Filter out sold products
            if (st === 'sold') continue;
            count++;
            var labelHtml = labelMap[st] || '';
            var btnHtml;
            if (st === 'in_cart') {
              btnHtml = '<button class="product-card__cart product-card__cart--in-cart" type="button" disabled aria-label="V košarici">' + checkSvg + '</button>';
            } else if (st === 'reserved') {
              btnHtml = '<button class="product-card__cart product-card__cart--disabled" type="button" disabled aria-label="Rezerviran">' + cartSvg + '</button>';
            } else {
              btnHtml = '<button class="product-card__cart" type="button" onclick="cart.add(\'' + safeId + '\', \'1\');" aria-label="Dodaj v košarico">' + cartSvg + '</button>';
            }
            var safeName = esc(p.name);
            var safeBrand = esc(p.brand);
            var safePrice = esc(p.price);
            var safeHref = esc(p.href);
            var safeThumb = esc(p.thumb);
            var safeId = parseInt(p.id, 10) || 0;
            html += '<article class="product-card product-card--scroll" data-product-id="' + safeId + '">' +
              '<div class="product-card__media">' +
                '<a href="' + safeHref + '" class="product-card__img-wrap">' +
                  '<div class="product-card__img">' +
                    '<div class="product-card__labels">' + labelHtml + '</div>' +
                    (safeThumb ? '<img src="' + safeThumb + '" alt="' + safeName + '" loading="lazy" />' : '') +
                  '</div>' +
                '</a>' +
                '<button class="product-card__fav" type="button" onclick="wishlist.add(\'' + safeId + '\');" aria-label="Dodaj med priljubljene">' +
                  '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>' +
                '</button>' +
              '</div>' +
              '<div class="product-card__body">' +
                (safeBrand ? '<p class="product-card__manufacturer">' + safeBrand + '</p>' : '') +
                '<a href="' + safeHref + '" class="product-card__name">' + safeName + '</a>' +
                '<div class="product-card__footer">' +
                  '<p class="product-card__price">' + safePrice + '</p>' +
                  btnHtml +
                '</div>' +
              '</div>' +
            '</article>';
          }
          if (count > 0) {
            html += '<div class="cart-recent__spacer"></div>';
            recentScroller.innerHTML = html;
            recentWrap.style.display = '';
          }
        }

        // Fetch status then render
        $.post('index.php?route=checkout/cart/getStockStatus', { product_ids: allPids }, function(json) {
          renderRecentCards(json && json.products ? json.products : {});
        }, 'json').fail(function() {
          // Fallback: render without status data if AJAX fails
          renderRecentCards({});
        });
      }
    }
  }

  $(document).ready(function () {
    reformatCart();
    syncStickyCart();
    updateWishlistBadge();
    initMobileMenu();
    initFooterAccordion();
    initArrivalsScroll();
    initReviewsScroll();
    initStickyNav();
    trackRecentlyViewed();
    initCartPage();

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
    // Toast auto-dismisses — no close button needed

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
        if (inCart) { reformatCart(); syncStickyCart(); refreshCartSheet(); }
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
