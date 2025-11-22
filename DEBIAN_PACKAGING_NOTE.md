# Debian Packaging Notes for SportOase IServ Module

## Important: How Assets Are Handled

### Problem: .gitignore vs. Package Build

The `public/build/` directory is **excluded from git** (via `.gitignore`) but **must be included in the Debian package**.

### Solution: Build-Time Asset Generation

Assets are **generated during Debian package build**, not committed to git:

```bash
# During dpkg-buildpackage:
1. composer install --no-dev          # Install PHP dependencies
2. npm ci --production=false           # Install Node.js build tools
3. NODE_ENV=production npm run build   # Generate app.css, app.js, runtime.js
4. Test assets exist                   # Verify build succeeded
5. rm -rf node_modules                 # Remove build tools (save space)
6. Package everything                  # Include vendor/ + public/build/
```

### Why This Works

**debian/install** ships the built assets:
```
public/build/*  →  usr/share/iserv/modules/sportoase/public/build/
```

Even though `public/build/` is gitignored, it exists in the build environment after step 3, so `debian/install` can package it.

## Asset Stability (No Versioning)

### webpack.config.js Configuration

```javascript
.enableVersioning(false)  // Produces stable filenames
```

**Result:**
- `app.css` (not `app.abc123.css`)
- `app.js` (not `app.def456.js`)
- `runtime.js` (not `runtime.ghi789.js`)

### Why No Versioning?

1. **Simplicity** - Templates reference `/build/app.css` directly
2. **No Encore Helper Needed** - No need for `encore_entry_link_tags()` Twig function
3. **IServ Compatibility** - Works standalone without Symfony Encore bundle at runtime

### Cache Busting Alternative

If browser caching becomes an issue in production, you can:
- Enable versioning: `.enableVersioning(true)`
- Install `symfony/webpack-encore-bundle` in runtime dependencies
- Update templates to use:
  ```twig
  {{ encore_entry_link_tags('app') }}
  {{ encore_entry_script_tags('app') }}
  ```

But for IServ modules, stable filenames are preferred for simplicity.

## Composer: Build-Time Only

### debian/control

```debian
Build-Depends: composer, php-cli, ...   # Build-time
Depends: php (>= 8.0), iserv-portal     # Runtime (NO composer!)
```

**Why?**
- Composer is needed to install `vendor/` during build
- `vendor/` is packaged and shipped with the .deb
- At runtime, no `composer install` is needed (vendor already there)
- Shipping composer in production is a security risk

### debian/rules

```makefile
composer install --no-dev --optimize-autoloader --no-interaction
```

This runs during build, creating `vendor/` which gets packaged.

## Node.js: Build-Time Only

### No Node.js at Runtime

**Build:**
```bash
npm ci --production=false   # Install Webpack, Tailwind, etc.
npm run build               # Compile CSS/JS
rm -rf node_modules         # Delete (not needed anymore!)
```

**Runtime:**
- Only compiled `public/build/*.css` and `*.js` are needed
- No `npm`, `webpack`, or `tailwindcss` required on IServ server

## CSP Compliance

### What Was Fixed

**Before (CSP Violations):**
```html
<script src="https://cdn.tailwindcss.com"></script>  <!-- External CDN blocked -->
<link href="https://fonts.googleapis.com/...">       <!-- External CDN blocked -->
<style>body { ... }</style>                          <!-- Inline styles blocked -->
```

**After (CSP Compliant):**
```html
<link rel="stylesheet" href="/build/app.css">  <!-- Self-hosted, no CSP issue -->
<script src="/build/app.js"></script>          <!-- Self-hosted, no CSP issue -->
```

All styles (including Tailwind + custom CSS) are compiled into one `app.css` file.

### Font Stack

**Before:**
```css
font-family: 'Inter', -apple-system, ...;  /* Requires Google Fonts CDN */
```

**After:**
```css
font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
```

System fonts only - no external CDN needed.

## Build Verification

### Test the Build Locally

```bash
# Simulate Debian build
rm -rf vendor/ node_modules/ public/build/
composer install --no-dev --optimize-autoloader
npm ci --production=false
NODE_ENV=production npm run build

# Verify assets exist
ls -lh public/build/
# Should show: app.css, app.js, runtime.js (stable names!)
```

### Common Build Issues

**Issue:** `app.abc123.css` generated instead of `app.css`

**Cause:** Versioning not disabled

**Fix:** Check `webpack.config.js`:
```javascript
.enableVersioning(false)  // Must be false!
```

**Issue:** Build succeeds but assets have wrong path in templates

**Cause:** Hardcoded hash in template

**Fix:** Ensure template uses:
```html
<link rel="stylesheet" href="/build/app.css">  <!-- No hash! -->
```

## Package Testing Checklist

Before deploying to production IServ:

- [ ] Build package on clean machine: `dpkg-buildpackage -us -uc -b`
- [ ] Verify `.deb` created: `ls -lh ../iserv-sportoase_*.deb`
- [ ] Install in test VM: `sudo dpkg -i iserv-sportoase_1.0.0_all.deb`
- [ ] Check assets exist: `ls /usr/share/iserv/modules/sportoase/public/build/`
- [ ] Should see: `app.css`, `app.js`, `runtime.js` (stable names)
- [ ] Verify vendor exists: `ls /usr/share/iserv/modules/sportoase/vendor/`
- [ ] Check no build tools: `ls /usr/share/iserv/modules/sportoase/node_modules/` (should NOT exist)
- [ ] Test in browser with strict CSP enabled

## Summary

| Aspect | Development | Debian Build | Production Runtime |
|--------|-------------|--------------|-------------------|
| **Assets** | Generated locally | Generated during build | Packaged in .deb |
| **Composer** | Installed locally | Runs during build | NOT needed |
| **Node.js** | Installed locally | Runs during build | NOT needed |
| **vendor/** | Gitignored | Created & packaged | Shipped with .deb |
| **public/build/** | Gitignored | Created & packaged | Shipped with .deb |
| **node_modules/** | Gitignored | Created then deleted | NOT in .deb |

The key insight: **Build artifacts (vendor/, public/build/) are NOT in git, but ARE in the Debian package.**
