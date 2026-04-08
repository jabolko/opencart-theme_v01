#!/bin/bash
# Checkout System — Test Runner
# Run after every code change: bash claude/pages/checkout/test-checkout.sh

PASS=0
FAIL=0
WEB="docker exec opencart_web"

# ── Step 0: Clear OCMOD cache for checkout files ──
echo "Clearing OCMOD cache..."
$WEB rm -f \
  /var/www/html/system/storage/modification/catalog/controller/checkout/checkout.php \
  /var/www/html/system/storage/modification/catalog/controller/checkout/confirm.php \
  /var/www/html/system/storage/modification/catalog/controller/checkout/guest.php \
  /var/www/html/system/storage/modification/catalog/controller/checkout/payment_address.php \
  /var/www/html/system/storage/modification/catalog/model/account/address.php \
  2>/dev/null
echo "Done."
echo ""

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

# ── T1: Checkout page loads ──
echo "T1: Checkout page accessible"
# Add a product first so checkout has items
curl -s -c /tmp/t_ck -b /tmp/t_ck -H "X-Forwarded-For: 10.50.0.1" 'http://localhost:8080/index.php?route=checkout/cart/add' -d 'product_id=66092&quantity=1' > /dev/null 2>&1
CODE=$(curl -s -c /tmp/t_ck -b /tmp/t_ck -H "X-Forwarded-For: 10.50.0.1" -o /dev/null -w "%{http_code}" 'http://localhost:8080/index.php?route=checkout/checkout')
assert "checkout HTTP 200" "200" "$CODE"

# ── T2: Checkout has cart summary ──
echo "T2: Cart summary in checkout"
HTML=$(curl -s -c /tmp/t_ck -b /tmp/t_ck -H "X-Forwarded-For: 10.50.0.1" 'http://localhost:8080/index.php?route=checkout/checkout')
HAS_TOTAL=$(echo "$HTML" | grep -c 'ck-total-bar' || true)
assert "total bar present" "1" "$([ $HAS_TOTAL -ge 1 ] && echo 1 || echo 0)"

# ── T3: Step headers without numbers ──
echo "T3: Step headers"
HAS_STEP_NUM=$(echo "$HTML" | grep -c 'Korak [0-9]' || true)
assert "no step numbers in headers" "0" "$HAS_STEP_NUM"

# ── T4: Heartbeat endpoint ──
echo "T4: Heartbeat"
R=$(curl -s -c /tmp/t_ck -b /tmp/t_ck -H "X-Forwarded-For: 10.50.0.1" -X POST 'http://localhost:8080/index.php?route=checkout/checkout/updateCartTime')
HAS_SUCCESS=$(echo "$R" | python3 -c "import sys,json; d=json.load(sys.stdin); print('yes' if d.get('success') else 'no')" 2>/dev/null)
assert "heartbeat returns success" "yes" "$HAS_SUCCESS"

# ── T5: Guest checkout form loads ──
echo "T5: Guest form"
GUEST=$(curl -s -c /tmp/t_ck -b /tmp/t_ck -H "X-Forwarded-For: 10.50.0.1" 'http://localhost:8080/index.php?route=checkout/guest')
HAS_FIRSTNAME=$(echo "$GUEST" | grep -c 'input-payment-firstname' || true)
assert "guest form has firstname" "1" "$([ $HAS_FIRSTNAME -ge 1 ] && echo 1 || echo 0)"

# ── T6: Payment address form loads ──
echo "T6: Payment address form"
PA=$(curl -s -c /tmp/t_ck -b /tmp/t_ck -H "X-Forwarded-For: 10.50.0.1" 'http://localhost:8080/index.php?route=checkout/payment_address')
HAS_ADDRESS=$(echo "$PA" | grep -c 'input-payment-address-1' || true)
assert "payment address form has address" "1" "$([ $HAS_ADDRESS -ge 1 ] && echo 1 || echo 0)"

# ── T7: Shipping method loads ──
echo "T7: Shipping method"
SM=$(curl -s -c /tmp/t_ck -b /tmp/t_ck -H "X-Forwarded-For: 10.50.0.1" 'http://localhost:8080/index.php?route=checkout/shipping_method')
assert "shipping method response" "1" "$([ ${#SM} -gt 10 ] && echo 1 || echo 0)"

# ── T8: Payment method loads ──
echo "T8: Payment method"
PM=$(curl -s -c /tmp/t_ck -b /tmp/t_ck -H "X-Forwarded-For: 10.50.0.1" 'http://localhost:8080/index.php?route=checkout/payment_method')
assert "payment method response" "1" "$([ ${#PM} -gt 10 ] && echo 1 || echo 0)"

# ── T9: Homepage still loads ──
echo "T9: Homepage"
CODE=$(curl -s -o /dev/null -w "%{http_code}" 'http://localhost:8080/')
assert "homepage HTTP 200" "200" "$CODE"

# ── Cleanup ──
DB="docker exec opencart_db mysql -u root -proot opencart_dev -N"
$DB -e "UPDATE oc_product p INNER JOIN oc_cart c ON p.product_id = c.product_id SET p.quantity = p.quantity + c.quantity;" 2>&1 | grep -v "Warning"
$DB -e "DELETE FROM oc_cart;" 2>&1 | grep -v "Warning"
$DB -e "UPDATE oc_product SET quantity = 1 WHERE product_id = 66092;" 2>&1 | grep -v "Warning"

# ── Summary ──
echo ""
echo "================================"
echo "PASSED: $PASS  FAILED: $FAIL"
echo "================================"
[ "$FAIL" -eq 0 ] && exit 0 || exit 1
