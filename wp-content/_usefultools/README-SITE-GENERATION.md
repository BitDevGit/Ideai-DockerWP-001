# Site Generation Scripts

This folder contains scripts for creating and managing nested multisite structures.

## create-perfect-nested-structure.php

Creates a perfect 4-level nested structure:
- **Level 1**: Parent sites (e.g., `/parent1/`, `/parent2/`, `/parent3/`)
- **Level 2**: Child1 and Child2 for each parent (e.g., `/parent1/child1/`, `/parent1/child2/`)
- **Level 3**: Grandchild1 for each child (e.g., `/parent1/child1/grandchild1/`)

### What it does:

1. **Creates sites** with correct nested paths
2. **Registers paths** in the `ideai_nested_sites` table
3. **Sets up homepages** with level-specific content
4. **Creates sample posts** named after each blog
5. **Updates siteurl/home** options to use nested paths
6. **Updates blogname** to reflect hierarchy

### Usage:

```bash
wp eval-file wp-content/_usefultools/create-perfect-nested-structure.php
```

### Output:

- Creates 3 parent sites
- Creates 6 child sites (2 per parent)
- Creates 6 grandchild sites (1 per child)
- **Total: 15 sites** with perfect 4-level structure

### Each site gets:

- ✅ Correct nested path in `wp_blogs.path`
- ✅ Registered in `ideai_nested_sites` table
- ✅ Homepage with level-specific content
- ✅ Sample post with blog name
- ✅ Correct `siteurl` and `home` options
- ✅ Blog name reflecting hierarchy

### Verification:

After running, check:
- Homepages show correct level info
- Sample posts exist with correct names
- URLs work correctly
- Admin URLs use correct paths

## Future Refactoring

Once this structure is verified, we will:
1. Refactor all site generation code
2. Consolidate into a single, clean module
3. Use database-driven content population
4. Add UI for site creation

