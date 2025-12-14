# Workflow Diagram: Nested Site Creation

## Visual Workflow

```
┌─────────────────────────────────────────────────────────────┐
│                    INITIAL SETUP                             │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
        ┌───────────────────────────────────┐
        │  1. Setup HTTPS (mkcert)          │
        │  2. Add /etc/hosts entries        │
        │  3. Start Docker containers        │
        └───────────────────────────────────┘
                            │
                            ▼
        ┌───────────────────────────────────┐
        │  WORDPRESS MULTISITE SETUP         │
        │  • Install WordPress              │
        │  • Enable Multisite               │
        │  • Choose Sub-directories         │
        └───────────────────────────────────┘
                            │
                            ▼
        ┌───────────────────────────────────┐
        │  ENABLE NESTED TREE FEATURE       │
        │  Network Admin → IdeAI → Status   │
        │  Toggle: Nested tree multisite    │
        └───────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│              CREATE NESTED SITE (UI)                         │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
        ┌───────────────────────────────────┐
        │  USER ACTIONS:                    │
        │  1. Select parent site            │
        │  2. Enter child slug              │
        │  3. Preview full URL              │
        │  4. Click "Add Site"             │
        └───────────────────────────────────┘
                            │
                            ▼
        ┌───────────────────────────────────┐
        │  BACKEND PROCESSING:               │
        │  1. wpmu_create_blog()            │
        │     (creates site with temp slug)  │
        │  2. handle_wpmu_new_blog() hook  │
        │     (updates to nested path)      │
        │  3. upsert_blog_path()            │
        │     (saves mapping)               │
        └───────────────────────────────────┘
                            │
                            ▼
        ┌───────────────────────────────────┐
        │  DATABASE UPDATES:                 │
        │  • wp_blogs.path = /parent/child/ │
        │  • ideai_nested_tree_paths        │
        │    (mapping table)                │
        └───────────────────────────────────┘
                            │
                            ▼
        ┌───────────────────────────────────┐
        │  URL GENERATION:                   │
        │  • WordPress uses wp_blogs.path   │
        │  • URL rewriting (safety net)    │
        │  • Result: /parent/child/wp-admin/│
        └───────────────────────────────────┘
                            │
                            ▼
        ┌───────────────────────────────────┐
        │  REQUEST ROUTING:                  │
        │  • pre_get_site_by_path filter    │
        │  • Resolves /parent/child/        │
        │  • Returns correct blog_id        │
        └───────────────────────────────────┘
                            │
                            ▼
        ┌───────────────────────────────────┐
        │  VERIFICATION:                     │
        │  • Site accessible                │
        │  • URLs correct (no --)           │
        │  • Admin works                    │
        └───────────────────────────────────┘
```

## Data Flow

```
User Input
    │
    ├─> Parent: /parent1/
    └─> Child Slug: test123
         │
         ▼
    Full Path: /parent1/test123/
         │
         ├─> WordPress creates site (temp slug)
         │
         ├─> Hook updates wp_blogs.path
         │
         ├─> Mapping saved to ideai_nested_tree_paths
         │
         └─> URLs generated using nested path
```

## Component Interaction

```
┌──────────────┐
│  Admin UI    │  (admin-ui.php)
│  - Form      │
│  - JavaScript│
└──────┬───────┘
       │
       ▼
┌──────────────┐
│  Site        │  (WordPress core)
│  Creation    │
└──────┬───────┘
       │
       ▼
┌──────────────┐
│  Hook        │  (handle_wpmu_new_blog)
│  Processing  │
└──────┬───────┘
       │
       ├─> Database (wp_blogs)
       ├─> Mapping (ideai_nested_tree_paths)
       │
       ▼
┌──────────────┐
│  Routing     │  (nested-tree-routing.php)
│  - Resolve   │
│  - Match     │
└──────┬───────┘
       │
       ▼
┌──────────────┐
│  URL         │  (nested-tree-urls.php)
│  Rewriting   │
│  - home_url  │
│  - admin_url │
└──────────────┘
```

## Iteration Cycle

```
┌─────────────────┐
│  Make Changes   │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Test Locally   │
│  - Create site  │
│  - Verify URLs  │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Run Tests      │
│  - Automated    │
│  - Browser MCP  │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Verify Results │
│  - No -- in URLs│
│  - Paths correct│
└────────┬────────┘
         │
         └──> Iterate
```

## File Organization

```
wp-content/mu-plugins/ideai.wp.plugin.platform/
├── ideai.wp.plugin.platform.php  (Main loader)
└── includes/
    ├── admin-ui.php              (UI + Site creation)
    ├── nested-tree.php           (Database + Mapping)
    ├── nested-tree-routing.php   (Request routing)
    ├── nested-tree-urls.php      (URL rewriting)
    ├── nested-tree-canonical.php (Canonical redirects)
    └── nested-tree-collisions.php (Collision prevention)
```

## Key Concepts

1. **Database First**: `wp_blogs.path` stores nested path directly
2. **Mapping Table**: `ideai_nested_tree_paths` for routing lookup
3. **URL Rewriting**: Safety net (should rarely fire if DB is correct)
4. **Request Routing**: `pre_get_site_by_path` resolves nested paths
5. **No `--`**: All paths use `/` from creation


