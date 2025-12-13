# Documentation Index

Complete guide to all documentation, scripts, and resources for learning, replicating, and iterating.

## 🚀 Start Here

1. **`QUICK_START.md`** - 5-minute quick start guide
2. **`README.md`** - Project overview and current state
3. **`docs/COMPLETE_SETUP_GUIDE.md`** - Step-by-step setup (learn everything)

## 📚 Documentation by Topic

### Setup & Installation
- **`QUICK_START.md`** - Fast setup (5 minutes)
- **`docs/COMPLETE_SETUP_GUIDE.md`** - Complete walkthrough
- **`docs/LOCAL_DEV.md`** - Local development environment

### Architecture & Design
- **`REFACTOR_PLAN.md`** - Design decisions and architecture
- **`docs/WORKFLOW_DIAGRAM.md`** - Visual workflows and data flow
- **`docs/nested-tree-multisite.md`** - Feature documentation

### Testing
- **`TESTING_WORKFLOW.md`** - Complete testing workflow
- **`TESTING_STATUS.md`** - Current status and next steps
- **`tests/README.md`** - Test suite documentation

### Technical Details
- **`docs/nginx-nested-tree-subdir-multisite.md`** - Nginx configuration
- **`docs/troubleshooting/TROUBLESHOOTING.md`** - Common issues and fixes

## 🛠️ Scripts Reference

### Setup Scripts
```bash
./scripts/dev/setup-https-mkcert.sh          # HTTPS certificates
./scripts/dev/setup-flexible-multisite.sh    # /etc/hosts entries
./scripts/dev/setup-wordpress-multisite.sh   # WordPress setup check
./scripts/dev/setup-test-sites.sh            # Auto-create test sites
```

### Development Scripts
```bash
./scripts/dev/reset-databases.sh             # Clean database reset
./scripts/dev/create-nested-site.sh          # CLI nested site creation
./scripts/dev/quick-reset.sh                 # Full reset → setup → test
./scripts/dev/ensure-wordpress-ready.sh      # Environment verification
```

### Test Scripts
```bash
./tests/test-all.sh                          # Run all tests
./tests/test-nested-urls.php                  # URL generation test
./tests/test-nested-paths.php                # Path verification
./tests/test-nested-creation-flow.sh         # Complete test flow
./tests/test-browser-creation.sh             # Browser testing guide
```

## 📖 Learning Path

### Beginner (First Time)
1. Read `QUICK_START.md`
2. Follow `docs/COMPLETE_SETUP_GUIDE.md`
3. Create your first nested site
4. Run `./tests/test-all.sh`

### Intermediate (Understanding)
1. Read `REFACTOR_PLAN.md` (architecture)
2. Study `docs/WORKFLOW_DIAGRAM.md` (data flow)
3. Review `docs/nested-tree-multisite.md` (feature details)
4. Explore code in `wp-content/mu-plugins/ideai.wp.plugin.platform/`

### Advanced (Iteration)
1. Use `TESTING_WORKFLOW.md` for rapid iteration
2. Modify code and test with `./scripts/dev/quick-reset.sh`
3. Add new tests to `tests/`
4. Document learnings in code comments

## 🔄 Common Workflows

### Daily Development
```bash
# 1. Make code changes
vim wp-content/mu-plugins/ideai.wp.plugin.platform/includes/admin-ui.php

# 2. Test
./tests/test-all.sh

# 3. Create test site
./scripts/dev/create-nested-site.sh /parent1/ test123 "Test"

# 4. Verify
curl -k https://site3.localwp/parent1/test123/wp-admin/
```

### Full Reset
```bash
./scripts/dev/quick-reset.sh
```

### Troubleshooting
1. Check `docs/troubleshooting/TROUBLESHOOTING.md`
2. Run `./scripts/dev/doctor.sh`
3. Check logs: `docker-compose -f docker-compose.flexible.yml logs`

## 📁 File Structure

```
.
├── QUICK_START.md                    # Quick reference
├── README.md                         # Project overview
├── DOCUMENTATION_INDEX.md           # This file
├── TESTING_WORKFLOW.md              # Testing guide
├── TESTING_STATUS.md                # Current status
├── REFACTOR_PLAN.md                 # Architecture
│
├── docs/
│   ├── COMPLETE_SETUP_GUIDE.md     # Full setup walkthrough
│   ├── WORKFLOW_DIAGRAM.md         # Visual workflows
│   ├── LOCAL_DEV.md                # Local dev environment
│   ├── nested-tree-multisite.md    # Feature docs
│   ├── nginx-nested-tree-subdir-multisite.md
│   └── troubleshooting/
│       └── TROUBLESHOOTING.md
│
├── scripts/dev/
│   ├── setup-*.sh                   # Setup scripts
│   ├── reset-*.sh                   # Reset scripts
│   ├── create-*.sh                  # Creation scripts
│   └── quick-reset.sh               # One-command reset
│
├── tests/
│   ├── README.md                    # Test docs
│   ├── test-*.sh                     # Test scripts
│   └── test-*.php                    # PHP tests
│
└── wp-content/mu-plugins/ideai.wp.plugin.platform/
    └── includes/                     # Source code
```

## 🎯 Key Concepts

### Core Principles
1. **Database First**: Store nested paths directly in `wp_blogs.path`
2. **No `--`**: All paths use `/` from creation
3. **Mapping Table**: `ideai_nested_tree_paths` for routing
4. **URL Rewriting**: Safety net (minimal code)
5. **Request Routing**: `pre_get_site_by_path` resolves paths

### Semantic Naming
- Level 0: `/` (root)
- Level 1: `/parent1/`, `/parent2/`
- Level 2: `/parent1/child1/`, `/parent1/child2/`
- Level 3: `/parent1/child1/grandchild1/`

## ✅ Success Checklist

When everything works:
- [ ] All automated tests pass
- [ ] No `--` in any URLs
- [ ] All nested sites accessible
- [ ] URLs generate correctly
- [ ] Database paths correct
- [ ] UI works seamlessly

## 🔗 Quick Links

- **Admin**: `https://site3.localwp/wp-admin/`
- **Create Site**: `https://site3.localwp/wp-admin/network/site-new.php`
- **IdeAI Status**: `https://site3.localwp/wp-admin/network/admin.php?page=ideai-status`
- **Network Admin**: `https://site3.localwp/wp-admin/network/`

## 📝 Documentation Standards

All documentation follows these principles:
- **Step-by-step**: Clear, numbered steps
- **Verifiable**: Commands you can run
- **Complete**: No missing steps
- **Replicable**: Works on fresh setup
- **Learnable**: Explains why, not just how

## 🚀 Next Steps

1. **Read**: Start with `QUICK_START.md`
2. **Setup**: Follow `docs/COMPLETE_SETUP_GUIDE.md`
3. **Learn**: Study `docs/WORKFLOW_DIAGRAM.md`
4. **Test**: Use `TESTING_WORKFLOW.md`
5. **Iterate**: Make changes and test rapidly

---

**Remember**: This is a learning and iteration process. Document what works, what doesn't, and how you fix issues. This makes future iterations faster and more reliable.

