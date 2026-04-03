#!/bin/bash
# Reservation System — Test Runner
# Run after every code change: ./claude/pages/reservation/test-reservation.sh

PASS=0
FAIL=0
DB="docker exec opencart_db mysql -u root -proot opencart_dev -N"
WEB="docker exec opencart_web"

# ── Step 0: Clear OCMOD cache for ALL our modified files ──
echo "Clearing OCMOD cache..."
$WEB rm -f \
  /var/www/html/system/storage/modification/system/library/cart/cart.php \
  /var/www/html/system/storage/modification/catalog/controller/checkout/cart.php \
  /var/www/html/system/storage/modification/catalog/controller/checkout/checkout.php \
  /var/www/html/system/storage/modification/catalog/controller/checkout/success.php \
  /var/www/html/system/storage/modification/catalog/controller/api/order.php \
  /var/www/html/system/storage/modification/catalog/controller/product/product.php \
  /var/www/html/system/storage/modification/catalog/controller/product/category.php \
  /var/www/html/system/storage/modification/catalog/controller/extension/module/latest.php \
  /var/www/html/system/storage/modification/catalog/model/checkout/order.php \
  /var/www/html/system/storage/modification/catalog/model/catalog/product.php \
  /var/www/html/system/storage/modification/catalog/language/en-gb/checkout/cart.php \
  2>/dev/null
echo "Done."
echo ""

# ── Step 1: Reset test data ──
$DB -e "DELETE FROM oc_cart; UPDATE oc_product SET quantity = 1 WHERE product_id IN (1, 11, 21, 66087, 66089);" 2>&1 | grep -v "Warning"

assert() {
  local name="$1" expected="$2" actual="$3"
  if [ "$expected" = "$actual" ]; then
    echo "  PASS: $name"
    PASS=$((PASS + 1))
  else
    echo "  FAIL: $name (expected=$expected actual=$actual)"
    FAIL=$((FAIL + 1))
  fi
}

# ── T1: Add to cart (stock 1→0) ──
echo "T1: Add to cart"
R=$(curl -s -c /tmp/t_res -b /tmp/t_res -H "X-Forwarded-For: 10.5.0.1" 'http://localhost:8080/index.php?route=checkout/cart/add' -d 'product_id=1&quantity=1')
HAS_SUCCESS=$(echo "$R" | python3 -c "import sys,json; d=json.load(sys.stdin); print('yes' if 'success' in d else 'no')" 2>/dev/null)
QTY=$($DB -e "SELECT quantity FROM oc_product WHERE product_id = 1;" 2>&1 | grep -v "Warning")
assert "add returns success" "yes" "$HAS_SUCCESS"
assert "stock decremented to 0" "0" "$QTY"

# ── T2: Race condition (second user blocked) ──
echo "T2: Race condition"
R=$(curl -s -c /tmp/t_res2 -b /tmp/t_res2 -H "X-Forwarded-For: 10.5.0.2" 'http://localhost:8080/index.php?route=checkout/cart/add' -d 'product_id=1&quantity=1')
HAS_ERROR=$(echo "$R" | python3 -c "import sys,json; d=json.load(sys.stdin); print('yes' if 'error' in d else 'no')" 2>/dev/null)
QTY=$($DB -e "SELECT quantity FROM oc_product WHERE product_id = 1;" 2>&1 | grep -v "Warning")
assert "second add returns error" "yes" "$HAS_ERROR"
assert "stock still 0 (not -1)" "0" "$QTY"

# ── T3: Already in cart (same user) ──
echo "T3: Already in cart"
R=$(curl -s -c /tmp/t_res -b /tmp/t_res -H "X-Forwarded-For: 10.5.0.1" 'http://localhost:8080/index.php?route=checkout/cart/add' -d 'product_id=1&quantity=1')
HAS_ERROR=$(echo "$R" | python3 -c "import sys,json; d=json.load(sys.stdin); print('yes' if 'error' in d else 'no')" 2>/dev/null)
assert "duplicate add returns error" "yes" "$HAS_ERROR"

# ── T4: Cart page — no stock warning ──
echo "T4: Cart page"
CART=$(curl -s -c /tmp/t_res -b /tmp/t_res -H "X-Forwarded-For: 10.5.0.1" 'http://localhost:8080/index.php?route=checkout/cart')
HAS_WARNING=$(echo "$CART" | grep -c "niso dobavljivi" || true)
assert "no stock warning" "0" "$HAS_WARNING"

# ── T5: Checkout accessible ──
echo "T5: Checkout"
CODE=$(curl -s -c /tmp/t_res -b /tmp/t_res -H "X-Forwarded-For: 10.5.0.1" -o /dev/null -w "%{http_code}" 'http://localhost:8080/index.php?route=checkout/checkout')
assert "checkout HTTP 200" "200" "$CODE"

# ── T6: Remove from cart (stock restored) ──
echo "T6: Remove"
CID=$($DB -e "SELECT cart_id FROM oc_cart WHERE product_id=1 LIMIT 1;" 2>&1 | grep -v "Warning")
curl -s -c /tmp/t_res -b /tmp/t_res -H "X-Forwarded-For: 10.5.0.1" 'http://localhost:8080/index.php?route=checkout/cart/remove' -d "key=$CID" > /dev/null
QTY=$($DB -e "SELECT quantity FROM oc_product WHERE product_id = 1;" 2>&1 | grep -v "Warning")
assert "stock restored to 1" "1" "$QTY"

# ── T7: Heartbeat ──
echo "T7: Heartbeat"
curl -s -c /tmp/t_res -b /tmp/t_res -H "X-Forwarded-For: 10.5.0.1" 'http://localhost:8080/index.php?route=checkout/cart/add' -d 'product_id=11&quantity=1' > /dev/null
DA1=$($DB -e "SELECT date_added FROM oc_cart WHERE product_id=11 LIMIT 1;" 2>&1 | grep -v "Warning")
sleep 2
curl -s -c /tmp/t_res -b /tmp/t_res -H "X-Forwarded-For: 10.5.0.1" -X POST 'http://localhost:8080/index.php?route=checkout/checkout/updateCartTime' > /dev/null
DA2=$($DB -e "SELECT date_added FROM oc_cart WHERE product_id=11 LIMIT 1;" 2>&1 | grep -v "Warning")
if [ "$DA1" != "$DA2" ]; then assert "timer extended" "yes" "yes"; else assert "timer extended" "yes" "no"; fi

# ── T8: Labels ──
echo "T8: Labels"
$DB -e "UPDATE oc_product SET quantity = 0 WHERE product_id = 66089;" 2>&1 | grep -v "Warning"
curl -s -c /tmp/t_res3 -b /tmp/t_res3 -H "X-Forwarded-For: 10.5.0.3" 'http://localhost:8080/index.php?route=checkout/cart/add' -d 'product_id=66087&quantity=1' > /dev/null
CATHTML=$(curl -s -c /tmp/t_res4 -b /tmp/t_res4 -H "X-Forwarded-For: 10.5.0.4" 'http://localhost:8080/index.php?route=product/category&path=226')
R_COUNT=$(echo "$CATHTML" | grep -c 'product-label--reserved' || true)
S_COUNT=$(echo "$CATHTML" | grep -c 'product-label--sold' || true)
if [ "$R_COUNT" -ge 1 ]; then assert "REZERVIRANO label" "yes" "yes"; else assert "REZERVIRANO label" "yes" "no"; fi
if [ "$S_COUNT" -ge 1 ]; then assert "PRODANO label" "yes" "yes"; else assert "PRODANO label" "yes" "no"; fi

# ── T9: Server time endpoint ──
echo "T9: Server time"
R=$(curl -s 'http://localhost:8080/index.php?route=checkout/cart/currentTime')
HAS_TIME=$(echo "$R" | python3 -c "import sys,json; d=json.load(sys.stdin); print('yes' if 'server_time' in d else 'no')" 2>/dev/null)
assert "currentTime endpoint" "yes" "$HAS_TIME"

# ── T10: Homepage loads ──
echo "T10: Homepage"
CODE=$(curl -s -o /dev/null -w "%{http_code}" 'http://localhost:8080/')
assert "homepage HTTP 200" "200" "$CODE"

# ── Cleanup: restore all test data so dev environment is clean ──
$DB -e "UPDATE oc_product p INNER JOIN oc_cart c ON p.product_id = c.product_id SET p.quantity = p.quantity + c.quantity;" 2>&1 | grep -v "Warning"
$DB -e "DELETE FROM oc_cart;" 2>&1 | grep -v "Warning"
$DB -e "UPDATE oc_product SET quantity = 1 WHERE product_id IN (1, 11, 21, 66087, 66089, 66092);" 2>&1 | grep -v "Warning"

# ── Summary ──
echo ""
echo "================================"
echo "PASSED: $PASS  FAILED: $FAIL"
echo "================================"
[ "$FAIL" -eq 0 ] && exit 0 || exit 1
