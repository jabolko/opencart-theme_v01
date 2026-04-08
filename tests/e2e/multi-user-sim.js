/**
 * Multi-User Reservation + Checkout Simulation
 *
 * Simulates 10 concurrent users in real Chromium browsers:
 * - Users 1-5: race for the SAME product (only 1 should win)
 * - Users 6-8: buy different products, complete checkout
 * - Users 9-10: add to cart then abandon (test expiry)
 *
 * Run: cd tests/e2e && npm install && npm test
 * Headed mode: npm run test:headed
 *
 * Output: results/report.json + screenshots on failures
 */

var playwright = require('playwright');
var fs = require('fs');
var path = require('path');

var BASE = 'http://localhost:8080';
var RESULTS_DIR = path.join(__dirname, 'results');
var HEADED = process.env.HEADED === '1';

// Test products — must have quantity=1 and be active
var RACE_PRODUCT_ID = '66092';   // All 5 race users compete for this
var PRODUCTS = ['66093', '66094', '66095']; // Users 6-8 buy these
var ABANDON_PRODUCTS = ['66099', '66100'];  // Users 9-10 abandon these

// Ensure results dir
if (!fs.existsSync(RESULTS_DIR)) fs.mkdirSync(RESULTS_DIR, { recursive: true });

// Report collector
var report = {
  started: new Date().toISOString(),
  users: [],
  errors: [],
  stock_before: {},
  stock_after: {},
  summary: {}
};

function log(userId, msg) {
  var ts = new Date().toISOString().split('T')[1].split('.')[0];
  console.log('[' + ts + '] User ' + userId + ': ' + msg);
}

async function getStockFromDB(productIds) {
  // Use the getStockStatus endpoint
  var browser = await playwright.chromium.launch({ headless: true });
  var ctx = await browser.newContext();
  var page = await ctx.newPage();
  try {
    var resp = await page.request.post(BASE + '/index.php?route=checkout/cart/getStockStatus', {
      form: Object.fromEntries(productIds.map(function(id, i) { return ['product_ids[' + i + ']', id]; }))
    });
    var data = await resp.json();
    await browser.close();
    return data.products || {};
  } catch (e) {
    await browser.close();
    return {};
  }
}

async function runUser(userId, scenario) {
  var result = {
    userId: userId,
    scenario: scenario.type,
    productId: scenario.productId,
    steps: [],
    errors: [],
    success: false,
    timing: {}
  };

  var browser = await playwright.chromium.launch({
    headless: !HEADED,
    args: ['--no-sandbox']
  });

  var context = await browser.newContext({
    viewport: { width: 375, height: 812 },
    userAgent: 'E2E-User-' + userId
  });

  var page = await context.newPage();
  var startTime = Date.now();

  // Capture console errors
  page.on('console', function(msg) {
    if (msg.type() === 'error') {
      var err = { userId: userId, type: 'console', text: msg.text(), time: Date.now() - startTime };
      result.errors.push(err);
      report.errors.push(err);
    }
  });

  // Capture page errors
  page.on('pageerror', function(error) {
    var err = { userId: userId, type: 'pageerror', text: error.message, time: Date.now() - startTime };
    result.errors.push(err);
    report.errors.push(err);
  });

  // Capture failed network requests
  page.on('requestfailed', function(request) {
    var err = { userId: userId, type: 'network', url: request.url(), failure: request.failure().errorText, time: Date.now() - startTime };
    result.errors.push(err);
    report.errors.push(err);
  });

  try {
    // Step 1: Visit product page
    var t0 = Date.now();
    log(userId, 'Visiting product ' + scenario.productId);
    await page.goto(BASE + '/index.php?route=product/product&product_id=' + scenario.productId, { waitUntil: 'domcontentloaded', timeout: 15000 });
    result.steps.push({ name: 'product_page', ms: Date.now() - t0 });

    // Check if product is available
    var ctaDisabled = await page.$('button.pdp-cart-btn--disabled, button.pdp-cart-btn--in-cart');
    if (ctaDisabled) {
      log(userId, 'Product unavailable (reserved/sold/in-cart)');
      result.steps.push({ name: 'product_unavailable', ms: 0 });
      result.success = false;
      result.timing.total = Date.now() - startTime;
      report.users.push(result);
      await browser.close();
      return result;
    }

    // Step 2: Add to cart
    t0 = Date.now();
    log(userId, 'Adding to cart...');
    var addBtn = await page.$('#button-cart');
    if (addBtn) {
      await addBtn.click();
      // Wait for AJAX response
      await page.waitForTimeout(2000);
    }
    result.steps.push({ name: 'add_to_cart', ms: Date.now() - t0 });

    // Check if add succeeded (button changed to disabled/in-cart)
    var btnAfter = await page.$('button.pdp-cart-btn--in-cart');
    if (!btnAfter) {
      // Check for error (reserved/sold)
      var btnDisabled = await page.$('button.pdp-cart-btn--disabled');
      if (btnDisabled) {
        log(userId, 'Add failed — product reserved/sold by another user');
        result.steps.push({ name: 'add_failed_race', ms: 0 });
        result.success = false;
        result.timing.total = Date.now() - startTime;
        report.users.push(result);
        await browser.close();
        return result;
      }
    }

    log(userId, 'Product added successfully');
    result.steps.push({ name: 'add_success', ms: 0 });

    // Abandon scenario — stop here
    if (scenario.type === 'abandon') {
      log(userId, 'Abandoning cart (will expire in 30 min)');
      result.steps.push({ name: 'abandoned', ms: 0 });
      result.success = true;
      result.timing.total = Date.now() - startTime;
      report.users.push(result);
      await browser.close();
      return result;
    }

    // Step 3: Go to checkout
    t0 = Date.now();
    log(userId, 'Navigating to checkout...');
    await page.goto(BASE + '/index.php?route=checkout/checkout', { waitUntil: 'domcontentloaded', timeout: 15000 });
    result.steps.push({ name: 'checkout_page', ms: Date.now() - t0 });

    // Wait for guest form to load
    await page.waitForTimeout(2000);

    // Step 4: Fill guest form
    t0 = Date.now();
    log(userId, 'Filling guest form...');

    // Click on guest tab if visible
    var guestPill = await page.$('[data-pill="guest"]');
    if (guestPill) await guestPill.click();
    await page.waitForTimeout(500);

    // Wait for firstname field
    try {
      await page.waitForSelector('#input-payment-firstname', { timeout: 5000 });
    } catch (e) {
      // Try loading guest form directly
      log(userId, 'Guest form not auto-loaded, waiting...');
      await page.waitForTimeout(3000);
    }

    var firstname = await page.$('#input-payment-firstname');
    if (firstname) {
      await page.fill('#input-payment-firstname', 'Test');
      await page.fill('#input-payment-lastname', 'User' + userId);
      await page.fill('#input-payment-email', 'test' + userId + '@e2e.test');
      await page.fill('#input-payment-telephone', '04012345' + userId);
      await page.fill('#input-payment-address-1', 'Testna ulica ' + userId);
      await page.fill('#input-payment-postcode', '1000');
      await page.fill('#input-payment-city', 'Ljubljana');
      result.steps.push({ name: 'fill_form', ms: Date.now() - t0 });

      // Step 5: Submit guest form
      t0 = Date.now();
      log(userId, 'Submitting guest form...');
      var submitBtn = await page.$('#button-guest');
      if (submitBtn) {
        await submitBtn.click();
        await page.waitForTimeout(3000);
      }
      result.steps.push({ name: 'submit_guest', ms: Date.now() - t0 });
    } else {
      log(userId, 'Could not find guest form fields');
      result.steps.push({ name: 'form_not_found', ms: Date.now() - t0 });
    }

    // Step 6: Select shipping method
    t0 = Date.now();
    log(userId, 'Selecting shipping...');
    await page.waitForTimeout(1000);
    var shippingRadio = await page.$('input[name="shipping_method"]');
    if (shippingRadio) {
      await shippingRadio.click();
      var shippingBtn = await page.$('#button-shipping-method');
      if (shippingBtn) {
        await shippingBtn.click();
        await page.waitForTimeout(2000);
      }
    }
    result.steps.push({ name: 'shipping', ms: Date.now() - t0 });

    // Step 7: Select payment method
    t0 = Date.now();
    log(userId, 'Selecting payment...');
    var paymentRadio = await page.$('input[name="payment_method"]');
    if (paymentRadio) {
      await paymentRadio.click();
    }
    // Agree to terms
    var agreeCheck = await page.$('input[name="agree"]');
    if (agreeCheck) {
      await agreeCheck.check();
    }
    var paymentBtn = await page.$('#button-payment-method');
    if (paymentBtn) {
      await paymentBtn.click();
      await page.waitForTimeout(2000);
    }
    result.steps.push({ name: 'payment', ms: Date.now() - t0 });

    // Step 8: Confirm order
    t0 = Date.now();
    log(userId, 'Confirming order...');
    await page.waitForTimeout(1000);

    // Look for the confirm button (could be proxy or original)
    var confirmBtn = await page.$('#js-confirm-proxy, #button-confirm, .ck-confirm__btn');
    if (confirmBtn) {
      await confirmBtn.click();
      // Wait for redirect to success page
      try {
        await page.waitForURL('**/checkout/success**', { timeout: 15000 });
        log(userId, 'ORDER PLACED SUCCESSFULLY');
        result.steps.push({ name: 'order_success', ms: Date.now() - t0 });
        result.success = true;
      } catch (e) {
        log(userId, 'Order confirm did not redirect to success');
        result.steps.push({ name: 'order_no_redirect', ms: Date.now() - t0 });
        // Take screenshot
        await page.screenshot({ path: path.join(RESULTS_DIR, 'fail-user' + userId + '-confirm.png'), fullPage: true });
      }
    } else {
      log(userId, 'Confirm button not found');
      result.steps.push({ name: 'confirm_not_found', ms: Date.now() - t0 });
      await page.screenshot({ path: path.join(RESULTS_DIR, 'fail-user' + userId + '-noconfirm.png'), fullPage: true });
    }

  } catch (error) {
    log(userId, 'ERROR: ' + error.message);
    result.errors.push({ userId: userId, type: 'exception', text: error.message, time: Date.now() - startTime });
    report.errors.push({ userId: userId, type: 'exception', text: error.message });
    try {
      await page.screenshot({ path: path.join(RESULTS_DIR, 'fail-user' + userId + '-exception.png'), fullPage: true });
    } catch (e) {}
  }

  result.timing.total = Date.now() - startTime;
  report.users.push(result);
  await browser.close();
  return result;
}

async function main() {
  console.log('=== Multi-User Reservation + Checkout Simulation ===');
  console.log('Base URL: ' + BASE);
  console.log('Users: 10 (5 race + 3 checkout + 2 abandon)');
  console.log('');

  // Ensure products are available
  console.log('Resetting test products...');
  var allProducts = [RACE_PRODUCT_ID].concat(PRODUCTS).concat(ABANDON_PRODUCTS);

  // Record stock before
  report.stock_before = await getStockFromDB(allProducts);
  console.log('Stock before:', JSON.stringify(report.stock_before));
  console.log('');

  // Define scenarios
  var scenarios = [
    // Users 1-5: race for same product
    { type: 'race_checkout', productId: RACE_PRODUCT_ID },
    { type: 'race_checkout', productId: RACE_PRODUCT_ID },
    { type: 'race_checkout', productId: RACE_PRODUCT_ID },
    { type: 'race_checkout', productId: RACE_PRODUCT_ID },
    { type: 'race_checkout', productId: RACE_PRODUCT_ID },
    // Users 6-8: normal checkout with different products
    { type: 'checkout', productId: PRODUCTS[0] },
    { type: 'checkout', productId: PRODUCTS[1] },
    { type: 'checkout', productId: PRODUCTS[2] },
    // Users 9-10: abandon cart
    { type: 'abandon', productId: ABANDON_PRODUCTS[0] },
    { type: 'abandon', productId: ABANDON_PRODUCTS[1] }
  ];

  // Run all 10 users concurrently
  console.log('Launching 10 users...');
  console.log('');

  var promises = scenarios.map(function(scenario, i) {
    return runUser(i + 1, scenario);
  });

  await Promise.all(promises);

  // Record stock after
  console.log('');
  report.stock_after = await getStockFromDB(allProducts);
  console.log('Stock after:', JSON.stringify(report.stock_after));

  // Generate summary
  var raceWinners = report.users.filter(function(u) {
    return u.scenario === 'race_checkout' && u.steps.some(function(s) { return s.name === 'add_success'; });
  });
  var raceLosers = report.users.filter(function(u) {
    return u.scenario === 'race_checkout' && u.steps.some(function(s) { return s.name === 'add_failed_race' || s.name === 'product_unavailable'; });
  });
  var checkoutSuccess = report.users.filter(function(u) { return u.success && u.scenario !== 'abandon'; });
  var abandoned = report.users.filter(function(u) { return u.scenario === 'abandon'; });
  var totalErrors = report.errors.length;

  report.summary = {
    race_product: RACE_PRODUCT_ID,
    race_winners: raceWinners.length,
    race_losers: raceLosers.length,
    checkout_success: checkoutSuccess.length,
    abandoned: abandoned.length,
    total_errors: totalErrors,
    console_errors: report.errors.filter(function(e) { return e.type === 'console'; }).length,
    network_errors: report.errors.filter(function(e) { return e.type === 'network'; }).length,
    page_errors: report.errors.filter(function(e) { return e.type === 'pageerror'; }).length
  };

  // Print report
  console.log('');
  console.log('==========================================');
  console.log('SIMULATION REPORT');
  console.log('==========================================');
  console.log('');
  console.log('Race condition (5 users, 1 product):');
  console.log('  Winners (got product): ' + raceWinners.length + ' (expected: 1)');
  console.log('  Losers (blocked):      ' + raceLosers.length + ' (expected: 4)');
  console.log('');
  console.log('Normal checkout:');
  console.log('  Completed orders: ' + checkoutSuccess.length);
  console.log('');
  console.log('Abandoned carts: ' + abandoned.length);
  console.log('');
  console.log('Errors:');
  console.log('  Console errors:  ' + report.summary.console_errors);
  console.log('  Network errors:  ' + report.summary.network_errors);
  console.log('  Page errors:     ' + report.summary.page_errors);
  console.log('  Total:           ' + totalErrors);
  console.log('');

  // Timing
  console.log('Timing (total per user):');
  report.users.forEach(function(u) {
    var status = u.success ? 'OK' : 'FAIL';
    var steps = u.steps.map(function(s) { return s.name; }).join(' > ');
    console.log('  User ' + u.userId + ' [' + u.scenario + '] ' + status + ' (' + (u.timing.total / 1000).toFixed(1) + 's): ' + steps);
  });

  // Save report
  var reportPath = path.join(RESULTS_DIR, 'report.json');
  fs.writeFileSync(reportPath, JSON.stringify(report, null, 2));
  console.log('');
  console.log('Full report saved to: ' + reportPath);

  // Verdict
  console.log('');
  if (raceWinners.length === 1 && totalErrors === 0) {
    console.log('VERDICT: PASS — Race condition safe, zero errors');
  } else if (raceWinners.length > 1) {
    console.log('VERDICT: FAIL — Race condition broken! ' + raceWinners.length + ' users got the same product');
  } else if (totalErrors > 0) {
    console.log('VERDICT: WARNING — ' + totalErrors + ' errors detected. Check report.json and screenshots.');
  } else {
    console.log('VERDICT: CHECK — Review results manually');
  }
}

main().catch(function(e) {
  console.error('Simulation crashed:', e);
  process.exit(1);
});
