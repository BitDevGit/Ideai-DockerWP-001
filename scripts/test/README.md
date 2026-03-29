# Vanilla WordPress Test Suite

Test environment for isolating issues between WordPress core and nested tree code.

## Quick Start

```bash
# Setup vanilla WordPress multisite
./scripts/test/vanilla-wp-setup.sh

# Test wp-admin access
./scripts/test/vanilla-wp-test.sh

# Access in browser (add to /etc/hosts):
# 127.0.0.1 vanilla.localwp
# Then visit: http://vanilla.localwp:8080/wp-admin/
```

## Purpose

This test environment helps determine if issues are:
- **WordPress core issues**: Will appear in vanilla setup
- **Nested tree code issues**: Will NOT appear in vanilla setup
- **Environment issues**: Will appear in both

## What It Tests

1. **wp-admin redirects**: Does vanilla WP have redirect loops?
2. **Multisite functionality**: Does subdirectory multisite work?
3. **URL generation**: Are admin URLs correct?
4. **Canonical redirects**: Do they work properly?

## Cleanup

```bash
# Stop and remove containers
docker-compose -f docker-compose.vanilla-test.yml down

# Remove volumes (fresh start)
docker-compose -f docker-compose.vanilla-test.yml down -v
```

## Comparison

| Feature | Vanilla WP | With Nested Tree |
|---------|-----------|------------------|
| Root site wp-admin | ✅ Should work | ❓ Test |
| Nested site wp-admin | N/A | ❓ Test |
| URL generation | ✅ Standard | ❓ Custom paths |
| Canonical redirects | ✅ Standard | ❓ Custom logic |

