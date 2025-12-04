# CSP Compliance Guide

## Overview

This document outlines the Content Security Policy (CSP) compliance strategy for the Demolition Traders frontend.

## Current CSP Policy

The system is now configured to support both permissive (development) and strict (production) CSP policies.

### Permissive Policy (Report-Only Mode)

```
default-src 'self';
script-src 'self' https://cdnjs.cloudflare.com;
style-src 'self' https://cdnjs.cloudflare.com 'unsafe-inline';
img-src 'self' data: https:;
font-src 'self' data: https://cdnjs.cloudflare.com;
connect-src 'self' https:;
frame-src 'self' https://www.google.com;
object-src 'none';
base-uri 'self';
form-action 'self';
```

### Strict Policy (Target)

```
default-src 'self';
script-src 'self';
style-src 'self' 'unsafe-inline';
img-src 'self' data:;
font-src 'self' data:;
connect-src 'self';
object-src 'none';
base-uri 'self';
form-action 'self';
```

## Migration Checklist

### Phase 1 - Remove Inline JS ✅ DONE
- [x] Remove `onclick=""` handlers
- [x] Remove `onchange=""` handlers
- [x] Remove `onerror=""` handlers
- [x] Create external event handler files
- [x] Use event delegation instead

### Phase 2 - Extract Inline CSS ✅ DONE
- [x] Create `csp-fixes.css` for common styles
- [x] Remove inline `style=""` attributes
- [x] Use CSS classes instead

### Phase 3 - Handle External Resources 🔄 IN PROGRESS
- [x] Document CDN dependencies
- [ ] Download Font Awesome locally OR convert to SVG
- [ ] Download tiny-slider locally
- [ ] Download noUiSlider locally
- [ ] Update script/link tags

### Phase 4 - Final Strict CSP Enforcement 📋 TODO
- [ ] Remove `'unsafe-inline'` from style-src
- [ ] Enable strict CSP header enforcement
- [ ] Test in production environment
- [ ] Monitor CSP violation reports

## External Dependencies

### Current CDN Resources

1. **Font Awesome 6.4.0** - Icons
   - CSS: https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css
   - Status: Can be replaced with inline SVG or downloaded locally

2. **tiny-slider 2.9.4** - Carousel
   - CSS: https://cdnjs.cloudflare.com/ajax/libs/tiny-slider/2.9.4/tiny-slider.css
   - JS: https://cdnjs.cloudflare.com/ajax/libs/tiny-slider/2.9.4/min/tiny-slider.js
   - Status: Available locally at `/frontend/assets/js/nouislider.min.js`

3. **noUiSlider 15.7.1** - Range Slider
   - CSS: https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.7.1/nouislider.min.css
   - Status: Available locally at `/frontend/assets/css/nouislider.min.css`

4. **Google Maps Embed**
   - Source: https://www.google.com/maps/embed?...
   - Status: Embedded in `<iframe>` - allowed in CSP

## Implementation Files

### New Files Created

1. **bootstrap-csp.php** - CSP header configuration
2. **assets/js/header-events.js** - Header event handlers
3. **assets/js/shop-events.js** - Shop page event handlers
4. **assets/js/admin-events.js** - Admin event handlers
5. **assets/js/cart-events.js** - Cart page event handlers
6. **assets/js/product-events.js** - Product event handlers
7. **assets/js/toast-events.js** - Toast/Modal event handlers
8. **assets/css/csp-fixes.css** - Extracted inline styles

### Modified Files

- frontend/user/index.php
- frontend/user/shop.php
- frontend/user/product-detail.php
- frontend/user/cart.php
- frontend/components/header.php
- frontend/components/toast-notification.php

## Testing & Monitoring

### Browser Console Check
1. Open browser DevTools (F12)
2. Check Console tab for CSP violations
3. Check Network tab for blocked resources

### CSP Report-Only Mode
- Currently using `Content-Security-Policy-Report-Only` header
- Violations are logged but not blocked
- Allows testing before enforcement

### Enforcement
- Change to `Content-Security-Policy` header when ready
- Will block resources that violate policy

## Best Practices Going Forward

1. **No Inline JavaScript**
   - ❌ `<button onclick="foo()">` 
   - ✅ `<button data-action="foo">` + event listener in external JS

2. **No Inline CSS**
   - ❌ `<div style="color: red;">`
   - ✅ `<div class="text-red">` in external CSS

3. **No Dynamic Script Generation**
   - ❌ `eval()`, `new Function()`, template literals in script tags
   - ✅ Use data attributes and event delegation

4. **External Resources**
   - ✅ Download and host locally when possible
   - ✅ Use HTTPS for any external resources
   - ✅ Update CSP policy if third-party services required

## Rollback Plan

If CSP causes issues:

1. Revert to report-only mode:
   ```php
   header("Content-Security-Policy-Report-Only: $csp");
   ```

2. Remove strict CSP temporarily:
   ```php
   // Comment out enforcement line
   // header("Content-Security-Policy: $csp");
   ```

3. Check CSP violations in browser console
4. Create issue in GitHub to track
5. Fix underlying issue before re-enabling

## References

- [MDN: Content Security Policy](https://developer.mozilla.org/en-US/docs/Web/HTTP/CSP)
- [OWASP: CSP Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Content_Security_Policy_Cheat_Sheet.html)
- [CSP Evaluator](https://csp-evaluator.withgoogle.com/)
