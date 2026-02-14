# ✅ Testing Checklist

Use this checklist to verify all components are working correctly before deploying to production.

## 🔧 Development Environment Setup

- [ ] Development server starts without errors (`npm run dev`)
- [ ] No console errors on page load
- [ ] Hot module replacement works (changes reflect immediately)
- [ ] Magento backend is in developer mode (`./magento.sh deploy:mode:show`)
- [ ] All caches are disabled (`./magento.sh cache:status`)

---

## 🖼️ OptimizedImage Component

### Functionality
- [ ] Images load correctly on page
- [ ] WebP images are served (check Network tab)
- [ ] Lazy loading works (images load as you scroll)
- [ ] Alt text is present for accessibility
- [ ] Images maintain aspect ratio
- [ ] No layout shift when images load (CLS)

### Test Cases
```jsx
// Test 1: Basic usage
<OptimizedImage
    src="/media/product.jpg"
    alt="Test Product"
    width={400}
    height={400}
/>

// Test 2: Lazy loading
<OptimizedImage
    src="/media/product.jpg"
    alt="Test Product"
    loading="lazy"
/>

// Test 3: Different object-fit
<OptimizedImage
    src="/media/product.jpg"
    alt="Test Product"
    objectFit="contain"
/>
```

### Browser Testing
- [ ] Chrome (WebP support)
- [ ] Firefox (WebP support)
- [ ] Safari (fallback to original)
- [ ] Mobile Chrome
- [ ] Mobile Safari

---

## 🔍 AutocompleteSearch Component

### Functionality
- [ ] Search input accepts text
- [ ] Dropdown appears after typing 3+ characters
- [ ] Debouncing works (300ms delay)
- [ ] Product suggestions show correctly
- [ ] Product images load in dropdown
- [ ] Product prices display correctly
- [ ] Ratings show when available
- [ ] "View all results" button appears
- [ ] Clicking product navigates to product page
- [ ] Keyboard navigation works (arrows, enter, escape)

### Test Cases
```jsx
// Test search queries
- "shirt" - should show relevant products
- "shoes" - should show relevant products
- "xyz123" - should show no results gracefully
- "a" - should not trigger search (< 3 chars)
```

### Keyboard Testing
- [ ] Arrow Down - selects next item
- [ ] Arrow Up - selects previous item
- [ ] Enter - navigates to selected product
- [ ] Escape - closes dropdown
- [ ] Tab - proper focus management

### Performance
- [ ] Search queries complete in < 500ms
- [ ] No duplicate API calls
- [ ] Dropdown smooth on scroll
- [ ] Mobile performance acceptable

---

## ✅ TrustBadges Component

### Functionality
- [ ] All 4 badges render correctly
- [ ] Icons display properly (emojis work)
- [ ] Text is readable
- [ ] Hover animations work
- [ ] Responsive on mobile (2x2 grid)
- [ ] Responsive on tablet (2x2 grid)
- [ ] Responsive on desktop (4x1 grid)

### Visual Testing
- [ ] Proper spacing between badges
- [ ] Colors match brand (if customized)
- [ ] Box shadows visible
- [ ] Border radius applied
- [ ] Background colors correct

### Integration Points
- [ ] Works on homepage
- [ ] Works on product pages
- [ ] Works on checkout page
- [ ] Works in footer
- [ ] No CSS conflicts

---

## 🔥 SocialProof Component

### Functionality
- [ ] Notification appears after 3 seconds
- [ ] Notification auto-hides after 8 seconds
- [ ] Close button works
- [ ] Animation plays smoothly
- [ ] Icon pulses
- [ ] Fixed positioning works
- [ ] Mobile responsive (full width)

### Test Cases
```jsx
// Test 1: Single purchase
<SocialProof
    productName="Cool Shirt"
    timeAgo={5}
    location="New York"
/>

// Test 2: Multiple purchases
<SocialProof
    productName="Cool Shirt"
    count={23}
/>
```

### Timing Tests
- [ ] Shows at correct time (3s)
- [ ] Hides at correct time (11s total)
- [ ] Multiple instances don't overlap
- [ ] Can be closed manually

---

## 📊 Analytics Integration

### Setup
- [ ] Google Analytics 4 tracking ID added
- [ ] `window.gtag` is defined in console
- [ ] No console errors from GA

### Page Tracking
- [ ] Page views tracked on navigation
- [ ] Page title captured correctly
- [ ] URL path captured correctly
- [ ] SPA navigation tracked

### Ecommerce Tracking
```javascript
// Test each function in console:

// Product view
trackProductView({
    sku: 'TEST-SKU',
    name: 'Test Product',
    price_range: {
        minimum_price: {
            final_price: { value: 29.99, currency: 'USD' }
        }
    },
    categories: [{ name: 'Test Category' }]
});

// Add to cart
trackAddToCart(product, 1);

// Purchase
trackPurchase({
    id: 'ORDER-123',
    total: 100,
    tax: 8,
    shipping: 10,
    currency: 'USD',
    items: [...]
});
```

### Verify in GA4
- [ ] Open GA4 DebugView
- [ ] See events in real-time
- [ ] Event parameters are correct
- [ ] User properties set correctly

### Adobe-Native Telemetry (First Drop)
- [ ] `ENABLE_ADOBE_EVENTS=true` mode tested
- [ ] `SEARCHBAR_REQUEST` is emitted from header autocomplete
- [ ] `SEARCH_RESPONSE` is emitted once per unique result set (no rapid duplicates)
- [ ] `CATEGORY_PAGE_VIEW` is emitted on PLP navigation
- [ ] Signed-in user stays `guest` context when signed-in personalization is disabled
- [ ] Signed-in user transitions to `logged-in` only when rollout eligibility is met

### KPI Probe Stream (Browser)
- [ ] `storefront-kpi-probe` event is observable in browser
- [ ] `search_request` probe payload includes query and source
- [ ] `search_response` probe payload includes totalCount and displayedCount
- [ ] `search_result_click` probe payload includes sku and position
- [ ] `product_page_view` probe payload includes sku and name
- [ ] `category_page_view` probe payload includes uid and urlPath
- [ ] `add_to_cart` probe payload includes sku and quantity when available
- [ ] `checkout_page_view` probe is emitted on checkout page visit
- [ ] `place_order_click` probe is emitted on place-order action
- [ ] `order_confirmation_page_view` probe is emitted after successful order

### Staged Rollout Commands
- [ ] `npm run smoke:rollout` passes
- [ ] `npm run dev:anonymous` works
- [ ] `npm run dev:signedin:10` works
- [ ] `npm run dev:signedin:25` works
- [ ] `npm run dev:signedin:50` works
- [ ] `npm run dev:signedin:100` works
- [ ] `/kpi-monitor` route loads and updates as probe events fire

---

## 🎯 Performance Testing

### Lighthouse Audit
Run in Chrome DevTools:
```bash
Lighthouse > Generate Report
```

**Target Scores:**
- [ ] Performance: 90+
- [ ] Accessibility: 95+
- [ ] Best Practices: 90+
- [ ] SEO: 90+

### Core Web Vitals
- [ ] LCP (Largest Contentful Paint) < 2.5s
- [ ] FID (First Input Delay) < 100ms
- [ ] CLS (Cumulative Layout Shift) < 0.1

### Bundle Analysis
```bash
npm run analyze
```

- [ ] Initial bundle size reduced
- [ ] Code splitting working (multiple chunks)
- [ ] Lazy-loaded components in separate chunks
- [ ] No unexpected large dependencies

### Network Tab
- [ ] GraphQL queries optimized
- [ ] Images lazy-loaded
- [ ] Minimal JavaScript on initial load
- [ ] Resources cached properly

---

## 📱 Mobile Testing

### Devices to Test
- [ ] iPhone (Safari)
- [ ] Android (Chrome)
- [ ] Tablet (iPad)
- [ ] Small screen (320px width)

### Mobile-Specific
- [ ] Touch interactions work
- [ ] Autocomplete dropdown usable
- [ ] Trust badges readable
- [ ] Social proof doesn't block content
- [ ] Images responsive
- [ ] No horizontal scrolling

### Performance on 3G
- [ ] Page loads in < 5s
- [ ] Interactive in < 7s
- [ ] Smooth scrolling

---

## ♿ Accessibility Testing

### Keyboard Navigation
- [ ] All interactive elements reachable by Tab
- [ ] Focus indicators visible
- [ ] Escape closes modals/dropdowns
- [ ] Enter activates buttons/links

### Screen Reader
Test with NVDA/JAWS/VoiceOver:
- [ ] All images have alt text
- [ ] ARIA labels present
- [ ] Form labels associated
- [ ] Button purposes clear
- [ ] Heading hierarchy correct

### Color Contrast
- [ ] Text meets WCAG AA (4.5:1)
- [ ] Interactive elements meet WCAG AA
- [ ] Error states visible

### Tools
- [ ] axe DevTools - no violations
- [ ] WAVE - no errors
- [ ] Lighthouse Accessibility - 95+

---

## 🔒 Security Testing

### Input Validation
- [ ] Search input sanitized
- [ ] XSS prevention working
- [ ] SQL injection not possible (GraphQL handles)
- [ ] CSRF tokens present

### Data Privacy
- [ ] No PII in analytics
- [ ] Cookie consent (if required)
- [ ] GDPR compliance
- [ ] IP anonymization enabled

---

## 🌐 Cross-Browser Testing

### Desktop Browsers
- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Edge (latest)

### Legacy Support
- [ ] Chrome (previous version)
- [ ] Firefox (previous version)
- [ ] Safari 14+

### Known Issues
Document any browser-specific issues:
```
- Safari iOS < 14: No WebP support (fallback works)
- IE11: Not supported (intentional)
```

---

## 🚀 Pre-Production Checklist

### Code Quality
- [ ] No console.log() statements
- [ ] No TODO comments
- [ ] Code formatted (Prettier)
- [ ] Linting passes (`npm run lint`)
- [ ] PropTypes defined for all components

### Documentation
- [ ] IMPLEMENTATION_GUIDE.md reviewed
- [ ] QUICKSTART.md reviewed
- [ ] Code comments present
- [ ] README updated

### Configuration
- [ ] Environment variables set
- [ ] GA4 tracking ID configured
- [ ] API endpoints correct
- [ ] CDN configured (if used)

### Deployment
- [ ] Staging environment tested
- [ ] Build succeeds (`npm run build`)
- [ ] Production bundle optimized
- [ ] Source maps generated
- [ ] Error monitoring setup (Sentry, etc.)

---

## 📈 Post-Launch Monitoring

### Week 1
- [ ] Monitor GA4 for events
- [ ] Check error rates
- [ ] Review Core Web Vitals
- [ ] Monitor conversion rate
- [ ] Check mobile performance

### Week 2-4
- [ ] A/B test results reviewed
- [ ] User feedback collected
- [ ] Performance benchmarks met
- [ ] No regression in metrics

### Ongoing
- [ ] Weekly analytics review
- [ ] Monthly performance audit
- [ ] Quarterly feature assessment

---

## 🐛 Common Issues & Solutions

### Issue: Search not working
**Solution:**
1. Check GraphQL endpoint
2. Verify network requests in DevTools
3. Check console for errors
4. Ensure product data exists

### Issue: Images not loading
**Solution:**
1. Check image paths
2. Verify media folder permissions
3. Clear cache (`./magento.sh cache:flush`)
4. Check CDN configuration

### Issue: Analytics not tracking
**Solution:**
1. Verify GA4 tracking ID
2. Check `window.gtag` in console
3. Use GA4 DebugView
4. Disable ad blockers during testing

### Issue: Performance degraded
**Solution:**
1. Run `npm run analyze`
2. Check for large dependencies
3. Verify lazy loading working
4. Check network waterfall

---

## ✅ Sign-Off

### Developer Checklist
- [ ] All components tested locally
- [ ] No console errors
- [ ] Code reviewed
- [ ] Documentation complete

### QA Checklist
- [ ] All test cases passed
- [ ] Cross-browser tested
- [ ] Mobile tested
- [ ] Accessibility verified

### Product Owner Checklist
- [ ] Features meet requirements
- [ ] UX approved
- [ ] Analytics configured
- [ ] Ready for production

---

**Testing Started:** ___________
**Testing Completed:** ___________
**Approved By:** ___________
**Deployed:** ___________

---

*Use this checklist for every major deployment to ensure quality and consistency.*
