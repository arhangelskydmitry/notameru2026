# Gorod Import And Cutover

## Import scope

Stage 1 import target is the WordPress contour used by `notamerularavel`.

Must be present:

- `wp_posts`
- `wp_postmeta`
- `wp_terms`
- `wp_term_taxonomy`
- `wp_term_relationships`
- `wp_users`
- `wp_usermeta`
- `wp_options`

Also required for current editor and analytics flows:

- Laravel tables from `php artisan migrate`
- `roles`
- `permissions`
- `user_roles`
- `activity_logs`
- `post_seo`
- menu and banner tables

## Media handling

For `gorod-magazine`, media should not be flattened into a single folder.

Required behavior:

- keep original `wp-content/uploads/YYYY/MM/...` paths
- preserve `_wp_attached_file`
- preserve `_wp_attachment_metadata`
- preserve `_thumbnail_id`
- preserve inline image URLs in `post_content`

## Frontend parity checklist

- homepage lists the same recent materials
- category pages open by the same slugs
- single article URLs preserve date-based structure
- featured images resolve from attachment metadata
- RSS keeps enclosure/media tags
- Open Graph and canonical data stay populated

## Local UAT

Run after stage is up:

- login to `/notaadmin`
- open dashboard
- open posts list
- open a heavy article with featured image
- edit title or excerpt and save
- verify article opens on the public frontend
- verify image URLs still resolve

## Production cutover outline

1. Freeze publishing on old WordPress.
2. Re-export the latest DB and uploads delta.
3. Rebuild the stage WordPress contour.
4. Run validation and visual parity checks.
5. Switch web root / app routing.
6. Re-check sitemap, RSS, canonical URLs, analytics and editor login.
