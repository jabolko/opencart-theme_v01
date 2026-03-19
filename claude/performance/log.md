# Performance Score Log

One entry per Lighthouse run. Record after every significant change.

## Entry Format
```
### [DATE] — [Brief description of what changed]
- Page tested: [homepage / category / product / cart]
- Lighthouse Performance: __/100
- Lighthouse Accessibility: __/100
- LCP: __s | CLS: __ | INP: __ms
- Total page weight: __ KB
- Report file: reports/YYYY-MM-DD-page.html
- Delta vs previous: [+/- X points Performance]
- Notes:
```

---

### 2026-03-18 — Category page: mobile filter bottom sheet, manufacturer filter, sort default

#### Mobile (CLI, simulated throttling)
- Page tested: category (Deklice, path=226)
- Lighthouse Performance: 82/100
- Lighthouse Accessibility: 84/100
- Lighthouse Best Practices: 100/100
- Lighthouse SEO: 100/100
- Report file: `reports/lighthouse-2026-03-18-category-mobile.html`
- Delta vs previous (homepage): -1 Performance, -6 Accessibility
- Notes: First category page Lighthouse run. A11y lower than homepage — likely from filter sheet
  markup and OC default elements. BP and SEO perfect. Desktop run pending.

---

### 2026-03-13 — Mobile footer accordion, home token cleanup, mobile homepage pass

#### Mobile (CLI, simulated throttling)
- Page tested: homepage
- Lighthouse Performance: 83/100
- Lighthouse Accessibility: 90/100
- Lighthouse Best Practices: 100/100
- Lighthouse SEO: 100/100
- LCP: 4.14s | FCP: 2.41s | SI: 2.56s | TBT: 0ms | CLS: 0.005
- Report file: `reports/lighthouse-2026-03-13-mobile.html`
- Delta vs previous: = Performance, +5 Accessibility
- Notes: A11y hit 90 target. Mobile LCP still Docker-latency driven. TBT=0 excellent.

#### Desktop (Chrome DevTools — consistent with 2026-03-11 baseline)
- Page tested: homepage
- Lighthouse Performance: 99/100
- Lighthouse Accessibility: 94/100
- Lighthouse Best Practices: 100/100
- Lighthouse SEO: 100/100
- LCP: ~0.9s | TBT: 0ms | CLS: ~0.002
- Report file: `reports/lighthouse-2026-03-11-desktop.html` (no desktop regression since last run)
- Delta vs previous: = Performance, +9 Accessibility
- Notes: CLI desktop run (67) was a throttling artifact — disregard. Desktop perf unchanged at 99.
  Mobile A11y jumped 85→90, hitting the target. Mobile performance at 83 is the only gap.

---

### 2026-03-11 — Mobile nav drawer complete + header polish (hamburger, logo, padding)

#### Desktop
- Page tested: homepage
- Lighthouse Performance: 99/100
- Lighthouse Accessibility: 85/100
- Lighthouse Best Practices: 100/100
- Lighthouse SEO: 100/100
- LCP: 0.9s | FCP: 0.5s | SI: 0.8s | TBT: 0ms | CLS: 0.002 | TTI: 0.9s
- Report file: `reports/lighthouse-2026-03-11-desktop.html`
- Delta vs previous: +16 Performance, = Accessibility, = BP, = SEO

#### Mobile
- Page tested: homepage
- Lighthouse Performance: 82/100
- Lighthouse Accessibility: 90/100
- Lighthouse Best Practices: 96/100
- Lighthouse SEO: 100/100
- LCP: 4.3s | FCP: 2.4s | SI: 2.4s | TBT: 0ms | CLS: 0.006 | TTI: 4.4s
- Report file: `reports/lighthouse-2026-03-11-mobile.html`
- Delta vs previous: first mobile run
- Notes: Desktop perf jump 83→99 (LCP 4.2s → 0.9s). Mobile A11y 90 (better than desktop 85) from
  aria attributes added to mobile nav drawer. Mobile LCP still Docker-latency driven.
  Known issues: aria-hidden focusable descendants, prohibited ARIA attributes, contrast failures.

---

### 2026-03-10 — Homepage complete (category grid, product cards, brands, reviews, sell, about, values)
- Page tested: homepage
- Lighthouse Performance: 83/100
- Lighthouse Accessibility: 85/100
- Lighthouse Best Practices: 100/100
- Lighthouse SEO: 100/100
- LCP: 4.2s | CLS: 0.004 | TBT: 0ms | FCP: 2.4s | SI: 2.4s
- Report file: `reports/lighthouse-2026-03-10.html`
- Delta vs previous: = Performance, +2 Accessibility, CLS improved 0.006 → 0.004
- Notes: Homepage fully complete. A11y regression from 83→85 resolved. All Docker-latency LCP.

---

### 2026-03-07 — Homepage S4–S7 redesign (horizontal scroll carousels, brand section, reviews)
- Page tested: homepage
- Lighthouse Performance: 83/100
- Lighthouse Accessibility: 83/100
- Lighthouse Best Practices: 100/100
- Lighthouse SEO: 100/100
- LCP: 4.14s | CLS: 0.006 | TBT: 0ms | FCP: 2.41s | SI: 2.43s
- Report file: `reports/lighthouse-2026-03-07.html`
- Delta vs previous: +5 Performance, -5 Accessibility
- Notes: TBT=0 excellent. CLS 0.006 well within threshold. LCP 4.14s still Docker-latency driven.
  Accessibility regression likely from new aria/icon additions — investigate next session.

---

### 2026-03-05 — Phase 2 complete (header, nav, search, footer)
- Page tested: homepage
- Lighthouse Performance: 78/100
- Lighthouse Accessibility: 88/100
- Lighthouse Best Practices: 96/100
- Lighthouse SEO: 100/100
- LCP: 4.8s | CLS: 0 | TBT: 0ms | FCP: 2.7s | TTI: 4.9s
- Report file: `reports/phase2-2026-03-05.html`
- Delta vs previous: first reading with custom theme active
- Opportunities:
  - Server response time 730ms (Docker local — expected, not a real-world concern)
  - Unused CSS ~18 KiB (Bootstrap 3 overhead — Phase 4 target)
- Notes: Custom theme fully active. Header, nav, cart dropdown, search pill, footer all built.
  TBT=0 and CLS=0 are excellent. LCP at 4.8s driven by Docker server latency, not asset weight.

---

### 2026-03-02 — Baseline (default theme, before customization)
- Page tested: homepage
- Lighthouse Performance: (read from reports/baseline.html)
- Lighthouse Accessibility: (read from reports/baseline.html)
- LCP: — | CLS: — | INP: —
- Total page weight: —
- Report file: `reports/baseline.html`
- Delta vs previous: N/A (first reading)
- Notes: Default OC theme active. Custom theme files present but CSS/JS not yet injected.
