# Gorod Magazine Target Model

## Decision

For the first migration stage, `gorod-magazine` should use the existing WordPress-shaped data contour inside `notamerularavel`:

- `wp_posts`
- `wp_postmeta`
- `wp_terms`
- `wp_term_taxonomy`
- `wp_term_relationships`
- `wp_users`

This is the safest path because the current admin and frontend already rely on `App\\Models\\WordPress\\*`.

## Why not Laravel-native first

The project also contains Laravel-native tables such as:

- `posts`
- `laravel_categories`
- `laravel_tags`

However, the current admin routes and a large part of the frontend runtime still use the WordPress models and relationships.

Moving `gorod-magazine` directly to the Laravel-native contour would require a second wave of refactoring:

- frontend queries
- editor forms
- permissions and user mapping
- SEO data usage
- media and attachment handling

## Stage-1 goal

Stage 1 should deliver:

- local staging on `notamerularavel`
- live editing through `/notaadmin`
- frontend rendering from the imported `wp_*` contour
- media served from the local `gorod-magazine` uploads snapshot

## Stage-2 optional goal

Only after stage-1 is stable should we consider moving the project from `wp_*` into the Laravel-native tables.
