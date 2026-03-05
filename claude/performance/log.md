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
