/* ============================================
   OTROŠKI KOTIČEK — Main JavaScript
   ============================================ */

document.addEventListener('DOMContentLoaded', () => {

  // ---- Header Scroll Effect ----
  const header = document.getElementById('header');
  if (header) {
    let lastScroll = 0;
    window.addEventListener('scroll', () => {
      const currentScroll = window.pageYOffset;
      if (currentScroll > 50) {
        header.classList.add('scrolled');
      } else {
        header.classList.remove('scrolled');
      }
      lastScroll = currentScroll;
    }, { passive: true });
  }

  // ---- Mobile Menu Toggle ----
  const mobileToggle = document.getElementById('mobileMenuToggle');
  const navList = document.getElementById('navList');
  if (mobileToggle && navList) {
    mobileToggle.addEventListener('click', () => {
      mobileToggle.classList.toggle('active');
      navList.classList.toggle('active');
      document.body.style.overflow = navList.classList.contains('active') ? 'hidden' : '';
    });
  }

  // ---- Product Gallery Thumbs ----
  const thumbs = document.querySelectorAll('.gallery-thumb');
  const mainImage = document.getElementById('mainImage');
  if (thumbs.length && mainImage) {
    const gradients = [
      'linear-gradient(135deg,#fce5b0,#f5e0df)',
      'linear-gradient(135deg,#f5e0df,#e8f0e6)',
      'linear-gradient(135deg,#e8f0e6,#fce5b0)',
      'linear-gradient(135deg,#e0e8f5,#f5e0df)',
    ];
    const emojis = ['👗', '🏷', '📐', '✨'];

    thumbs.forEach((thumb, index) => {
      thumb.addEventListener('click', () => {
        thumbs.forEach(t => t.classList.remove('active'));
        thumb.classList.add('active');
        mainImage.style.background = gradients[index] || gradients[0];
        mainImage.textContent = emojis[index] || emojis[0];
      });
    });
  }

  // ---- Quantity Selector ----
  const quantitySelectors = document.querySelectorAll('.quantity-selector');
  quantitySelectors.forEach(selector => {
    const btns = selector.querySelectorAll('button');
    const input = selector.querySelector('input');
    if (btns.length === 2 && input) {
      btns[0].addEventListener('click', () => {
        const val = parseInt(input.value) || 1;
        if (val > 1) input.value = val - 1;
      });
      btns[1].addEventListener('click', () => {
        const val = parseInt(input.value) || 1;
        input.value = val + 1;
      });
    }
  });

  // ---- Filter Size Toggle ----
  const sizeOptions = document.querySelectorAll('.size-option');
  sizeOptions.forEach(option => {
    option.addEventListener('click', () => {
      option.classList.toggle('active');
    });
  });

  // ---- Filter Options Toggle ----
  const filterOptions = document.querySelectorAll('.filter-option');
  filterOptions.forEach(option => {
    option.addEventListener('click', () => {
      option.classList.toggle('active');
    });
  });

  // ---- Scroll Animations (Intersection Observer) ----
  const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
  };

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.style.opacity = '1';
        entry.target.style.transform = 'translateY(0)';
        observer.unobserve(entry.target);
      }
    });
  }, observerOptions);

  // Animate sections on scroll
  const animateSections = document.querySelectorAll('.section-header, .product-card, .category-card, .testimonial-card, .value-card, .feature-item');
  animateSections.forEach((el, index) => {
    el.style.opacity = '0';
    el.style.transform = 'translateY(20px)';
    el.style.transition = `opacity 0.6s ease ${index % 4 * 0.1}s, transform 0.6s ease ${index % 4 * 0.1}s`;
    observer.observe(el);
  });

  // ---- Wishlist Heart Toggle ----
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.product-action-btn, .product-wishlist-btn');
    if (btn && (btn.textContent.trim() === '♡' || btn.textContent.trim() === '♥')) {
      e.preventDefault();
      e.stopPropagation();
      btn.textContent = btn.textContent.trim() === '♡' ? '♥' : '♡';
      btn.style.color = btn.textContent.trim() === '♥' ? 'var(--rose)' : '';
    }
  });

  // ---- Add to Cart Animation ----
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.add-to-cart-btn');
    if (btn) {
      e.preventDefault();
      e.stopPropagation();
      const original = btn.textContent;
      btn.textContent = 'Dodano ✓';
      btn.style.background = 'var(--sage)';
      btn.style.color = 'var(--white)';
      setTimeout(() => {
        btn.textContent = original;
        btn.style.background = '';
        btn.style.color = '';
      }, 1500);
    }
  });

  // ---- Cart Remove Animation ----
  const cartRemoveBtns = document.querySelectorAll('.cart-remove');
  cartRemoveBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const item = btn.closest('.cart-item');
      if (item) {
        item.style.opacity = '0';
        item.style.transform = 'translateX(20px)';
        item.style.transition = 'all 0.3s ease';
        setTimeout(() => {
          item.style.height = '0';
          item.style.padding = '0';
          item.style.overflow = 'hidden';
        }, 300);
      }
    });
  });

  // ---- Smooth scroll for anchor links ----
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', (e) => {
      const href = anchor.getAttribute('href');
      if (href !== '#') {
        e.preventDefault();
        const target = document.querySelector(href);
        if (target) {
          target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      }
    });
  });

});
