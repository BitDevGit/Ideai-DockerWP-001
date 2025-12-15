# GitHub Workflow Guide

This guide explains how to use the GitHub issue templates and task management system in Cursor.

---

## Quick Start

### 1. Create an Issue from Template

**In Cursor:**
1. Open the GitHub panel (usually in sidebar)
2. Click "New Issue" or use command palette: `GitHub: Create Issue`
3. Select a template:
   - **P0: Uploads Handling** - For critical upload issues
   - **P1: Admin URLs** - For admin URL issues
   - **Bug Report** - For general bugs
   - **Feature Request** - For new features

**Via Command Line:**
```bash
# Create P0 uploads issue
gh issue create --template .github/ISSUE_TEMPLATE/p0-uploads-handling.md --title "[P0] Uploads Handling" --label "bug,uploads,multisite,critical,p0"

# Create P1 admin URLs issue
gh issue create --template .github/ISSUE_TEMPLATE/p1-admin-urls.md --title "[P1] Admin URLs" --label "bug,admin,urls,high-priority,p1"
```

---

## Workflow: Working on Uploads (P0)

### Step 1: Create Issue
```bash
gh issue create --template .github/ISSUE_TEMPLATE/p0-uploads-handling.md
```

### Step 2: Assign to Yourself
```bash
gh issue assign <issue-number> @me
```

### Step 3: Create Branch
```bash
git checkout -b fix/uploads-handling
```

### Step 4: Work Through Subtasks
- Open the issue in Cursor/GitHub
- Check off subtasks as you complete them
- Commit frequently with descriptive messages

### Step 5: Test
- Run through the testing checklist in the issue
- Document any issues found

### Step 6: Create Pull Request
```bash
gh pr create --title "Fix: Uploads handling for nested sites" --body "Fixes #<issue-number>"
```

### Step 7: Close Issue
- Once PR is merged, close the issue
- All acceptance criteria should be checked

---

## Managing Tasks

### View All Tasks
```bash
# Open in browser
gh browse docs/GITHUB_TASKS.md

# Or read locally
cat docs/GITHUB_TASKS.md
```

### Filter Issues by Label
```bash
# List all P0 issues
gh issue list --label "p0"

# List all upload-related issues
gh issue list --label "uploads"

# List all bugs
gh issue list --label "bug"
```

### Update Issue Status
```bash
# Add "in-progress" label
gh issue edit <issue-number> --add-label "in-progress"

# Remove "in-progress" label, add "ready-for-review"
gh issue edit <issue-number> --remove-label "in-progress" --add-label "ready-for-review"
```

---

## Semantic Tags Reference

### Priority Tags
- `p0` - Critical (must fix immediately)
- `p1` - High priority (fix soon)
- `p2` - Medium priority
- `p3-p7` - Lower priority

### Type Tags
- `bug` - Bug fix
- `enhancement` - New feature
- `documentation` - Docs update
- `testing` - Testing related
- `performance` - Performance optimization

### Area Tags
- `uploads` - Upload handling
- `admin` - Admin area
- `urls` - URL generation
- `multisite` - Multisite functionality
- `routing` - Site routing
- `ui` - User interface

### Status Tags
- `critical` - Critical issue
- `high-priority` - High priority
- `in-progress` - Currently working
- `ready-for-review` - Ready for review
- `done` - Completed

---

## Best Practices

### 1. Always Use Templates
- Don't create blank issues
- Use the appropriate template
- Fill in all relevant sections

### 2. Update Progress Regularly
- Check off subtasks as you complete them
- Update labels (e.g., `in-progress`, `ready-for-review`)
- Add comments with progress updates

### 3. Link Related Issues
- Reference related issues in descriptions
- Use `Fixes #<issue-number>` in PR descriptions
- Link to documentation when relevant

### 4. Test Before Closing
- Complete all acceptance criteria
- Run through testing checklist
- Document any edge cases found

### 5. Keep Issues Focused
- One issue = one problem/feature
- Break large tasks into smaller issues
- Use subtasks for detailed work items

---

## Common Commands

```bash
# List all open issues
gh issue list

# View specific issue
gh issue view <issue-number>

# Create issue from template
gh issue create --template .github/ISSUE_TEMPLATE/p0-uploads-handling.md

# Assign issue
gh issue assign <issue-number> @me

# Add label
gh issue edit <issue-number> --add-label "in-progress"

# Add comment
gh issue comment <issue-number> --body "Working on this now..."

# Close issue
gh issue close <issue-number>
```

---

## Integration with Cursor

### Using GitHub Panel
1. Open GitHub panel in sidebar
2. View issues, create new ones
3. Link issues to branches automatically

### Using Command Palette
- `GitHub: Create Issue` - Create new issue
- `GitHub: View Issues` - List all issues
- `GitHub: Open Issue` - Open specific issue

### Using Git Integration
- Create branch from issue: `gh issue develop <issue-number>`
- PR automatically links to issue
- Close issue when PR is merged

---

## Troubleshooting

### Issue template not showing
- Ensure `.github/ISSUE_TEMPLATE/` directory exists
- Check that template files have `.md` extension
- Verify YAML frontmatter is correct

### Labels not applying
- Check label exists in repository
- Verify label name matches exactly
- Create labels if missing: `gh label create "p0" --description "Critical priority"`

### Can't assign issue
- Verify you have write access to repository
- Check if issue is already assigned
- Try assigning via GitHub web UI

---

## Next Steps

1. **Set up labels** - Run label creation commands from `docs/GITHUB_ISSUES_TEMPLATE.md`
2. **Create first issue** - Use P0 uploads template
3. **Start working** - Follow the workflow guide above
4. **Track progress** - Update issues regularly

---

**See Also:**
- `docs/GITHUB_TASKS.md` - Complete task list
- `docs/PROJECT_STATUS.md` - Project status
- `.github/ISSUE_TEMPLATE/README.md` - Template documentation

