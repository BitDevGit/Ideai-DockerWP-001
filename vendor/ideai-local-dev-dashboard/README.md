# Ideai-LocalDevDashboard (subtree)

This folder is intended to become a **git subtree** pointing at a standalone repo:

- `Ideai-LocalDevDashboard`

In this WordPress project we mount:

- `vendor/ideai-local-dev-dashboard/dashboard/` → `/var/www/dashboard` in nginx

So the dashboard is served at `https://localhost`.

## Why subtree

- No special clone steps (unlike submodules)
- Dashboard code ships with the project (good onboarding)
- You can later split it into its own repo and keep pulling updates


