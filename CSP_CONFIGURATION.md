# Content Security Policy (CSP) Configuration

## Overview

SportOase is designed to be CSP-compliant with no inline scripts or styles. All assets are bundled and served from `/build/`.

## Required CSP Directives

For production deployment on IServ, configure the following CSP directives:

```
Content-Security-Policy:
  default-src 'self';
  script-src 'self';
  style-src 'self';
  img-src 'self' data:;
  font-src 'self';
  connect-src 'self';
  frame-ancestors 'none';
  base-uri 'self';
  form-action 'self';
```

## IServ Integration

IServ automatically manages CSP headers for modules. No additional configuration needed if following CSP best practices:

✅ **Compliant:**
- All JavaScript bundled in `/build/app.js` and `/build/runtime.js`
- All CSS bundled in `/build/app.css`
- No inline `<script>` tags
- No inline `<style>` tags
- No external CDN dependencies

❌ **Non-Compliant:**
- Inline scripts (e.g., `<script>alert('hello')</script>`)
- Inline styles (e.g., `<div style="color: red">`)
- External CDNs (e.g., `https://cdn.tailwindcss.com`)
- Inline event handlers (e.g., `onclick="..."`)

## Symfony CSP Configuration (Optional)

If deploying outside IServ or need custom CSP headers, use a Kernel Response Listener:

### 1. Create Event Listener

```php
// src/EventListener/CspHeaderListener.php
namespace SportOase\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class CspHeaderListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();
        
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self'",
            "style-src 'self'",
            "img-src 'self' data:",
            "font-src 'self'",
            "connect-src 'self'",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'"
        ]);
        
        $response->headers->set('Content-Security-Policy', $csp);
    }
}
```

### 2. Register in services.yaml

```yaml
services:
    SportOase\EventListener\CspHeaderListener:
        tags:
            - { name: kernel.event_subscriber }
```

## Alternative: Nelmio Security Bundle

Install Nelmio Security Bundle for advanced CSP management:

```bash
composer require nelmio/security-bundle
```

Configure in `config/packages/nelmio_security.yaml`:

```yaml
nelmio_security:
    content_security_policy:
        enabled: true
        report_endpoint: null
        directives:
            default-src: ['self']
            script-src: ['self']
            style-src: ['self']
            img-src: ['self', 'data:']
            font-src: ['self']
            connect-src: ['self']
            frame-ancestors: ['none']
            base-uri: ['self']
            form-action: ['self']
```

## Verification

### 1. Check Headers

```bash
curl -I https://your-iserv.de/sportoase
```

Look for:
```
Content-Security-Policy: default-src 'self'; script-src 'self'; ...
```

### 2. Browser DevTools

1. Open SportOase in browser
2. Open DevTools (F12)
3. Go to Console tab
4. Look for CSP violation warnings (there should be none)

### 3. CSP Validator

Use online tools:
- https://csp-evaluator.withgoogle.com/
- https://csper.io/evaluator

## Troubleshooting

### SVG Icons Show CSP Warnings

Inline SVGs are allowed by CSP. If warnings appear, verify `img-src 'self' data:` is configured.

### Service Worker Registration Fails

Ensure `script-src 'self'` allows service worker registration. Service workers must be served from same origin.

### OAuth Redirect Issues

Ensure `form-action 'self'` allows OAuth form submissions. IServ OAuth endpoints are on same domain.

## Production Checklist

- [ ] All JavaScript bundled (no inline scripts)
- [ ] All CSS bundled (no inline styles)
- [ ] No external CDN dependencies
- [ ] CSP headers configured (via IServ or custom listener)
- [ ] No CSP violations in browser console
- [ ] Service worker registers successfully
- [ ] OAuth flow works without CSP blocks

## References

- [MDN: Content Security Policy](https://developer.mozilla.org/en-US/docs/Web/HTTP/CSP)
- [CSP Best Practices](https://cheatsheetseries.owasp.org/cheatsheets/Content_Security_Policy_Cheat_Sheet.html)
- [Symfony Security Bundle](https://symfony.com/doc/current/security.html)
