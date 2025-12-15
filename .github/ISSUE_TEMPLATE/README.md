# GitHub Issue Templates

This directory contains issue templates for the IdeAI Docker WordPress project.

## Available Templates

### Priority Templates

1. **🔴 P0: Critical - Uploads Handling** (`p0-uploads-handling.md`)
   - For critical upload handling issues
   - Pre-filled with comprehensive subtasks
   - Use when uploads are not working correctly

2. **🟠 P1: High Priority - Admin URL Generation** (`p1-admin-urls.md`)
   - For admin URL generation issues
   - Pre-filled with testing checklist
   - Use when admin URLs are incorrect

### General Templates

3. **🐛 Bug Report** (`bug-report.md`)
   - For general bug reports
   - Use for any bugs not covered by priority templates

4. **✨ Feature Request** (`feature-request.md`)
   - For new feature suggestions
   - Use when proposing new functionality

## How to Use

### In Cursor

1. Open the GitHub panel in Cursor
2. Click "New Issue"
3. Select the appropriate template
4. Fill in the details
5. Submit

### In GitHub Web UI

1. Go to the Issues tab
2. Click "New Issue"
3. Select the appropriate template
4. Fill in the details
5. Submit

### Via GitHub CLI

```bash
# Create P0 uploads issue
gh issue create --template .github/ISSUE_TEMPLATE/p0-uploads-handling.md

# Create P1 admin URLs issue
gh issue create --template .github/ISSUE_TEMPLATE/p1-admin-urls.md

# Create bug report
gh issue create --template .github/ISSUE_TEMPLATE/bug-report.md

# Create feature request
gh issue create --template .github/ISSUE_TEMPLATE/feature-request.md
```

## Template Structure

Each template includes:
- **Metadata** (YAML frontmatter) - labels, assignees, etc.
- **Problem Statement** - What's wrong or what's needed
- **Acceptance Criteria** - What needs to be done
- **Subtasks** - Detailed checklist of work items
- **Related Files** - Code files that need to be modified
- **Testing Checklist** - How to verify the fix works

## Customization

To customize templates:
1. Edit the `.md` files in this directory
2. Update `config.yml` to change the issue creation page
3. Add new templates as needed

## Labels

Templates automatically apply labels:
- **Priority:** `p0`, `p1`, `p2`, etc.
- **Type:** `bug`, `enhancement`, `documentation`, etc.
- **Area:** `uploads`, `admin`, `urls`, `multisite`, etc.

See `docs/GITHUB_TASKS.md` for full label reference.

