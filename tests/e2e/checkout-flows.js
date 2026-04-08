/**
 * Checkout Flow Tests — 5 Combinations
 * Matched to otroskikoticek V1 accordion checkout
 *
 * Run: cd tests/e2e && node checkout-flows.js
 * Headed: HEADED=1 node checkout-flows.js
 */

var playwright = require('playwright');
var fs = require('fs');
var path = require('path');

var BASE = 'http://localhost:8080';
var RESULTS_DIR = path.join(__dirname, 'results');
var HEADED = process.env.HEADED === '1';
var SLOW = process.env.SLOW === '1' ? 500 : 0;

var TEST_EMAIL = 'test@otroskikoticek.si';
var TEST_PASS = 'Test1234!';

var PRODUCTS = {
  guest: '66092',
  login_checkout: '66093',
  already_logged: '66094',
  login_new_addr: '66095',
  register: '66099'
};

if (!fs.existsSync(RESULTS_DIR)) fs.mkdirSync(RESULTS_DIR, { recursive: true });
var results = [];

function log(flow, msg) {
  var ts = new Date().toISOString().split('T')[1].split('.')[0];
  console.log('[' + ts + '] ' + flow + ': ' + msg);
}

async function screenshot(page, name) {
  try { await page.screenshot({ path: path.join(RESULTS_DIR, name + '.png'), fullPage: true }); } catch (e) {}
}

// Wait for panel content to load (AJAX populates .panel-body)
async function waitForPanelContent(page, panelId, timeout) {
  timeout = timeout || 15000;
  try {
    await page.waitForFunction(function(id) {
      var panel = document.getElementById(id);
      if (!panel) return false;
      var body = panel.querySelector('.panel-body');
      if (!body) return false;
      // Content loaded when panel-body has substantial HTML
      return body.innerHTML.trim().length > 100;
    }, panelId, { timeout: timeout });
    // Extra wait for JS to initialize
    await page.waitForTimeout(500);
    return true;
  } catch (e) {
    return false;
  }
}

async function addProductToCart(page, productId, flow) {
  log(flow, 'Adding product ' + productId);
  await page.goto(BASE + '/index.php?route=product/product&product_id=' + productId, { waitUntil: 'domcontentloaded', timeout: 15000 });
  await page.waitForTimeout(1500);

  var disabled = await page.$('button.pdp-cart-btn--disabled, button.pdp-cart-btn--in-cart');
  if (disabled) { log(flow, 'Product unavailable'); return false; }

  var btn = await page.$('#button-cart');
  if (!btn) { log(flow, 'No add button'); return false; }

  await btn.click();
  await page.waitForTimeout(3000);

  var inCart = await page.$('button.pdp-cart-btn--in-cart');
  if (inCart) { log(flow, 'Added OK'); return true; }

  log(flow, 'Add result unclear — continuing');
  return true;
}

async function doShippingPaymentConfirm(page, flow) {
  // SHIPPING METHOD
  // SHIPPING METHOD — wait for button to be visible (not panel class)
  log(flow, 'Shipping method...');
  try {
    await page.waitForSelector('#button-shipping-method', { state: 'visible', timeout: 15000 });
  } catch (e) {
    log(flow, 'Shipping button not visible');
    await screenshot(page, 'fail-' + flow + '-shipping');
    return false;
  }

  var shipRadio = await page.$('input[name="shipping_method"]:visible');
  if (!shipRadio) shipRadio = await page.$('input[name="shipping_method"]');
  if (shipRadio) await shipRadio.click();
  await page.waitForTimeout(300);

  await page.click('#button-shipping-method');
  log(flow, 'Shipping submitted');
  await page.waitForTimeout(3000);

  // PAYMENT METHOD — wait for button
  log(flow, 'Payment method...');
  try {
    await page.waitForSelector('#button-payment-method', { state: 'visible', timeout: 15000 });
  } catch (e) {
    log(flow, 'Payment button not visible');
    await screenshot(page, 'fail-' + flow + '-payment');
    return false;
  }

  var payRadio = await page.$('input[name="payment_method"]');
  if (payRadio) await payRadio.click();
  await page.waitForTimeout(300);

  var agree = await page.$('input[name="agree"]');
  if (agree) {
    var isChecked = await agree.isChecked();
    if (!isChecked) await agree.check();
  }

  await page.click('#button-payment-method');
  log(flow, 'Payment submitted');
  await page.waitForTimeout(3000);

  // CONFIRM
  log(flow, 'Confirm...');
  if (!await waitForPanelContent(page, 'collapse-checkout-confirm', 12000)) {
    log(flow, 'Confirm panel not loaded');
    await screenshot(page, 'fail-' + flow + '-confirm-panel');
    return false;
  }

  // Wait for proxy button to be created by JS
  await page.waitForTimeout(2000);

  // Try proxy confirm button first, then original
  var confirmBtn = null;
  try {
    confirmBtn = await page.waitForSelector('#ck-confirm-proxy', { state: 'visible', timeout: 5000 });
  } catch (e) {
    try {
      confirmBtn = await page.waitForSelector('#button-confirm', { state: 'visible', timeout: 3000 });
    } catch (e2) {}
  }

  if (confirmBtn) {
    await confirmBtn.click();
    log(flow, 'Confirm clicked');
    try {
      await page.waitForFunction(function() {
        return document.body.innerText.indexOf('oddano') !== -1 || document.body.innerText.indexOf('success') !== -1 || window.location.href.indexOf('success') !== -1;
      }, { timeout: 20000 });
      log(flow, 'ORDER SUCCESS');
      return true;
    } catch (e) {
      log(flow, 'No redirect to success');
      await screenshot(page, 'fail-' + flow + '-no-success');
      return false;
    }
  } else {
    log(flow, 'No confirm button found');
    await screenshot(page, 'fail-' + flow + '-no-confirm');
    return false;
  }
}

// ─── FLOW 1: GUEST ───
async function testGuest() {
  var flow = 'GUEST';
  var browser = await playwright.chromium.launch({ headless: !HEADED, args: ['--no-sandbox'], slowMo: SLOW });
  var ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
  var page = await ctx.newPage();
  var success = false;

  try {
    if (!await addProductToCart(page, PRODUCTS.guest, flow)) { await browser.close(); return { flow: flow, success: false, error: 'add_failed' }; }

    log(flow, 'Checkout...');
    await page.goto(BASE + '/index.php?route=checkout/checkout', { waitUntil: 'domcontentloaded', timeout: 15000 });

    // Wait for step 1 to load (guest/register/login)
    if (!await waitForPanelContent(page, 'collapse-payment-address', 10000)) {
      log(flow, 'Step 1 not loaded');
      await screenshot(page, 'fail-' + flow + '-step1');
      await browser.close();
      return { flow: flow, success: false, error: 'step1_not_loaded' };
    }

    // Click guest pill (should be active by default, but click to be safe)
    var guestPill = await page.$('#js-pill-guest');
    if (guestPill) await guestPill.click();
    await page.waitForTimeout(1000);

    // Fill form
    log(flow, 'Filling guest form...');
    await page.fill('#input-payment-firstname', 'Testni');
    await page.fill('#input-payment-lastname', 'Gost');
    await page.fill('#input-payment-email', 'gost@test.si');
    await page.fill('#input-payment-telephone', '040111222');
    await page.fill('#input-payment-address-1', 'Testna ulica 1');
    await page.fill('#input-payment-postcode', '1000');
    await page.fill('#input-payment-city', 'Ljubljana');

    var guestBtn = await page.$('#button-guest');
    if (guestBtn) {
      await guestBtn.click();
      log(flow, 'Guest form submitted');
    }
    await page.waitForTimeout(3000);

    success = await doShippingPaymentConfirm(page, flow);
  } catch (e) {
    log(flow, 'ERROR: ' + e.message.split('\n')[0]);
    await screenshot(page, 'fail-' + flow + '-error');
  }

  await browser.close();
  return { flow: flow, success: success, error: success ? null : 'incomplete' };
}

// ─── FLOW 2: LOGIN AT CHECKOUT ───
async function testLoginAtCheckout() {
  var flow = 'LOGIN_AT_CHECKOUT';
  var browser = await playwright.chromium.launch({ headless: !HEADED, args: ['--no-sandbox'], slowMo: SLOW });
  var ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
  var page = await ctx.newPage();
  var success = false;

  try {
    if (!await addProductToCart(page, PRODUCTS.login_checkout, flow)) { await browser.close(); return { flow: flow, success: false, error: 'add_failed' }; }

    log(flow, 'Checkout...');
    await page.goto(BASE + '/index.php?route=checkout/checkout', { waitUntil: 'domcontentloaded', timeout: 15000 });

    if (!await waitForPanelContent(page, 'collapse-payment-address', 10000)) {
      await browser.close(); return { flow: flow, success: false, error: 'step1_not_loaded' };
    }

    // Click login pill
    log(flow, 'Clicking login...');
    var loginPill = await page.$('#js-pill-login');
    if (loginPill) await loginPill.click();
    await page.waitForTimeout(1500);

    // Fill login form (inside collapse-checkout-option or collapse-payment-address)
    var emailInput = await page.$('input[name="email"]');
    var passInput = await page.$('input[name="password"]');
    if (emailInput && passInput) {
      await emailInput.fill(TEST_EMAIL);
      await passInput.fill(TEST_PASS);
      log(flow, 'Login credentials filled');
      var loginBtn = await page.$('#button-login');
      if (loginBtn) {
        await loginBtn.click();
        log(flow, 'Login submitted');
        await page.waitForTimeout(4000);
      }
    } else {
      log(flow, 'Login fields not found');
      await screenshot(page, 'fail-' + flow + '-no-login-fields');
      await browser.close();
      return { flow: flow, success: false, error: 'login_fields_not_found' };
    }

    // After login, handle address steps (logged-in users see address selection)
    success = await doLoggedInAddressAndCheckout(page, flow);
  } catch (e) {
    log(flow, 'ERROR: ' + e.message.split('\n')[0]);
    await screenshot(page, 'fail-' + flow + '-error');
  }

  await browser.close();
  return { flow: flow, success: success, error: success ? null : 'incomplete' };
}

// Shared helper: handle logged-in user address steps + shipping/payment/confirm
async function doLoggedInAddressAndCheckout(page, flow) {
  // Payment address
  log(flow, 'Payment address...');
  if (await waitForPanelContent(page, 'collapse-payment-address', 10000)) {
    // Select existing address radio if present and visible
    try {
      var existingRadio = await page.waitForSelector('#collapse-payment-address input[name="payment_address"][value="existing"]', { state: 'visible', timeout: 3000 });
      if (existingRadio) {
        await existingRadio.click();
        await page.waitForTimeout(500);
        log(flow, 'Selected existing address');
      }
    } catch (e) {
      log(flow, 'No existing address radio (guest-like form)');
    }
    // Submit — try payment_address button, then guest button
    try {
      var paBtn = await page.waitForSelector('#button-payment-address, #button-guest', { state: 'visible', timeout: 5000 });
      if (paBtn) {
        await paBtn.click();
        log(flow, 'Address submitted');
        await page.waitForTimeout(5000);
      }
    } catch (e) {
      log(flow, 'No address submit button visible');
    }
  }

  // Shipping address (may appear for logged-in users — often auto-skipped)
  try {
    var saBtn = await page.waitForSelector('#button-shipping-address', { state: 'visible', timeout: 5000 });
    if (saBtn) {
      var existingShip = await page.$('#collapse-shipping-address input[name="shipping_address"][value="existing"]');
      if (existingShip) { await existingShip.click(); await page.waitForTimeout(300); }
      await saBtn.click();
      log(flow, 'Shipping address submitted');
      await page.waitForTimeout(2000);
    }
  } catch (e) {
    log(flow, 'Shipping address skipped (auto-copied)');
  }

  return await doShippingPaymentConfirm(page, flow);
}

// ─── FLOW 3: ALREADY LOGGED IN ───
async function testAlreadyLoggedIn() {
  var flow = 'ALREADY_LOGGED';
  var browser = await playwright.chromium.launch({ headless: !HEADED, args: ['--no-sandbox'], slowMo: SLOW });
  var ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
  var page = await ctx.newPage();
  var success = false;

  try {
    // Login via account page
    log(flow, 'Logging in...');
    await page.goto(BASE + '/index.php?route=account/login', { waitUntil: 'domcontentloaded', timeout: 15000 });
    await page.waitForTimeout(1000);

    var emailField = await page.$('#input-email');
    var passField = await page.$('#input-password');
    if (emailField && passField) {
      await emailField.fill(TEST_EMAIL);
      await passField.fill(TEST_PASS);
      await page.click('input[type="submit"], button[type="submit"]');
      await page.waitForTimeout(3000);
    }

    if (!await addProductToCart(page, PRODUCTS.already_logged, flow)) { await browser.close(); return { flow: flow, success: false, error: 'add_failed' }; }

    log(flow, 'Checkout...');
    await page.goto(BASE + '/index.php?route=checkout/checkout', { waitUntil: 'domcontentloaded', timeout: 15000 });
    await page.waitForTimeout(2000);

    success = await doLoggedInAddressAndCheckout(page, flow);
  } catch (e) {
    log(flow, 'ERROR: ' + e.message.split('\n')[0]);
    await screenshot(page, 'fail-' + flow + '-error');
  }

  await browser.close();
  return { flow: flow, success: success, error: success ? null : 'incomplete' };
}

// ─── FLOW 4: LOGIN + NEW ADDRESS ───
async function testLoginNewAddress() {
  var flow = 'LOGIN_NEW_ADDR';
  var browser = await playwright.chromium.launch({ headless: !HEADED, args: ['--no-sandbox'], slowMo: SLOW });
  var ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
  var page = await ctx.newPage();
  var success = false;

  try {
    // Login
    log(flow, 'Logging in...');
    await page.goto(BASE + '/index.php?route=account/login', { waitUntil: 'domcontentloaded', timeout: 15000 });
    await page.waitForTimeout(1000);
    await page.fill('#input-email', TEST_EMAIL);
    await page.fill('#input-password', TEST_PASS);
    await page.click('input[type="submit"], button[type="submit"]');
    await page.waitForTimeout(3000);

    if (!await addProductToCart(page, PRODUCTS.login_new_addr, flow)) { await browser.close(); return { flow: flow, success: false, error: 'add_failed' }; }

    log(flow, 'Checkout...');
    await page.goto(BASE + '/index.php?route=checkout/checkout', { waitUntil: 'domcontentloaded', timeout: 15000 });
    await page.waitForTimeout(2000);

    if (await waitForPanelContent(page, 'collapse-payment-address', 10000)) {
      // Try to select "new address" radio
      var newAddr = await page.$('#collapse-payment-address input[value="new"]');
      if (newAddr) {
        await newAddr.click();
        await page.waitForTimeout(1500);
        log(flow, 'Selected new address');

        // Fill address
        await page.fill('#input-payment-firstname', 'Novi');
        await page.fill('#input-payment-lastname', 'Naslov');
        await page.fill('#input-payment-address-1', 'Nova ulica 99');
        await page.fill('#input-payment-postcode', '2000');
        await page.fill('#input-payment-city', 'Maribor');
      }

      try {
        var paBtn = await page.waitForSelector('#button-payment-address', { state: 'visible', timeout: 5000 });
        await paBtn.click();
        log(flow, 'Payment address submitted');
        await page.waitForTimeout(5000);
      } catch (e) {
        log(flow, 'Payment address button not clickable');
      }
    }

    // Shipping address (may auto-skip for new address)
    try {
      var saBtn = await page.waitForSelector('#button-shipping-address', { state: 'visible', timeout: 5000 });
      if (saBtn) { await saBtn.click(); log(flow, 'Shipping address submitted'); await page.waitForTimeout(2000); }
    } catch (e) {
      log(flow, 'Shipping address skipped');
    }

    success = await doShippingPaymentConfirm(page, flow);
  } catch (e) {
    log(flow, 'ERROR: ' + e.message.split('\n')[0]);
    await screenshot(page, 'fail-' + flow + '-error');
  }

  await browser.close();
  return { flow: flow, success: success, error: success ? null : 'incomplete' };
}

// ─── FLOW 5: REGISTER AT CHECKOUT ───
async function testRegister() {
  var flow = 'REGISTER';
  var browser = await playwright.chromium.launch({ headless: !HEADED, args: ['--no-sandbox'], slowMo: SLOW });
  var ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
  var page = await ctx.newPage();
  var success = false;

  try {
    if (!await addProductToCart(page, PRODUCTS.register, flow)) { await browser.close(); return { flow: flow, success: false, error: 'add_failed' }; }

    log(flow, 'Checkout...');
    await page.goto(BASE + '/index.php?route=checkout/checkout', { waitUntil: 'domcontentloaded', timeout: 15000 });

    if (!await waitForPanelContent(page, 'collapse-payment-address', 10000)) {
      await browser.close(); return { flow: flow, success: false, error: 'step1_not_loaded' };
    }

    // Click register pill
    log(flow, 'Register pill...');
    var regPill = await page.$('#js-pill-register');
    if (regPill) await regPill.click();
    await page.waitForTimeout(1500);

    // Wait for register form to load
    await page.waitForSelector('#input-payment-firstname', { timeout: 5000 }).catch(function() {});

    var fn = await page.$('#input-payment-firstname');
    if (fn) {
      var ts = Date.now();
      await page.fill('#input-payment-firstname', 'Registrirani');
      await page.fill('#input-payment-lastname', 'Kupec');
      await page.fill('#input-payment-email', 'reg' + ts + '@test.si');
      await page.fill('#input-payment-telephone', '040999888');
      await page.fill('#input-payment-address-1', 'Registracijska 5');
      await page.fill('#input-payment-postcode', '1000');
      await page.fill('#input-payment-city', 'Ljubljana');

      // Password
      var passField = await page.$('input[name="password"]');
      if (passField) {
        await passField.fill('Test1234!');
        var confirmField = await page.$('input[name="confirm"]');
        if (confirmField) await confirmField.fill('Test1234!');
      }

      // Privacy/agree
      var privacyCheck = await page.$('#collapse-payment-address input[name="agree"]');
      if (privacyCheck) {
        var isChecked = await privacyCheck.isChecked();
        if (!isChecked) await privacyCheck.check();
      }

      log(flow, 'Register form filled');
      var regBtn = await page.$('#button-register');
      if (regBtn) {
        await regBtn.click();
        log(flow, 'Register submitted');
        await page.waitForTimeout(4000);
      } else {
        log(flow, 'No register button');
        await screenshot(page, 'fail-' + flow + '-no-reg-btn');
      }
    } else {
      log(flow, 'Register form fields not found');
      await screenshot(page, 'fail-' + flow + '-no-fields');
    }

    success = await doShippingPaymentConfirm(page, flow);
  } catch (e) {
    log(flow, 'ERROR: ' + e.message.split('\n')[0]);
    await screenshot(page, 'fail-' + flow + '-error');
  }

  await browser.close();
  return { flow: flow, success: success, error: success ? null : 'incomplete' };
}

// ─── MAIN ───
async function main() {
  console.log('=== Checkout Flow Tests — 5 Combinations ===');
  console.log('');

  var flows = [
    { name: '1. Guest checkout', fn: testGuest },
    { name: '2. Login at checkout', fn: testLoginAtCheckout },
    { name: '3. Already logged in', fn: testAlreadyLoggedIn },
    { name: '4. Login + new address', fn: testLoginNewAddress },
    { name: '5. Register at checkout', fn: testRegister }
  ];

  for (var i = 0; i < flows.length; i++) {
    console.log('─── ' + flows[i].name + ' ───');
    var result = await flows[i].fn();
    results.push(result);
    console.log('');
  }

  console.log('==========================================');
  console.log('RESULTS');
  console.log('==========================================');
  var passed = 0, failed = 0;
  results.forEach(function(r) {
    var s = r.success ? 'PASS' : 'FAIL';
    if (r.success) passed++; else failed++;
    console.log('  ' + s + ': ' + r.flow + (r.error ? ' (' + r.error + ')' : ''));
  });
  console.log('');
  console.log('PASSED: ' + passed + '  FAILED: ' + failed);
  console.log('==========================================');

  fs.writeFileSync(path.join(RESULTS_DIR, 'checkout-flows.json'), JSON.stringify(results, null, 2));
}

main().catch(function(e) { console.error('Crashed:', e.message); process.exit(1); });
