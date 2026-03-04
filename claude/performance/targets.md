# Performance Targets

Non-negotiable minimums. Do not ship a change that breaks these.

## Lighthouse Targets (Mobile, simulated throttling)

| Metric             | Target | Priority |
|--------------------|--------|----------|
| Performance score  | >= 90  | Must     |
| Accessibility score| >= 90  | Must     |
| Best Practices     | >= 90  | Should   |
| SEO                | >= 90  | Should   |

## Core Web Vitals Targets

| Metric                         | Target   | Category |
|--------------------------------|----------|----------|
| LCP (Largest Contentful Paint) | < 2.5s   | Good     |
| CLS (Cumulative Layout Shift)  | < 0.1    | Good     |
| INP (Interaction to Next Paint)| < 200ms  | Good     |

## Page Weight Budget

| Page     | Max uncompressed | Max compressed |
|----------|-----------------|----------------|
| Homepage | 500 KB          | 150 KB         |
| Category | 500 KB          | 150 KB         |
| Product  | 600 KB          | 180 KB         |
| Cart     | 400 KB          | 120 KB         |

## How to Measure
1. Run Lighthouse: Chrome DevTools → Lighthouse tab → Mobile → Generate report
2. Save HTML report to `reports/` with format: `YYYY-MM-DD-page-name.html`
3. Record scores in `claude/performance/log.md`
4. Record key metrics in `performance-log.md` (root, legacy format)

## Baseline Reference
- Baseline report: `reports/baseline.html`
- Generated: 2026-03-02 on default OC theme
- Use as the "before" benchmark for all improvements
