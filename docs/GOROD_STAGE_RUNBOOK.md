# Gorod Magazine Stage Runbook

## 1. Prepare WordPress stage database

From `gorod-magazine.ru`:

```bash
./scripts/prepare-nota-stage-db.sh
```

This creates a dedicated WordPress-shaped stage database for `notamerularavel` and rewrites URLs to `http://127.0.0.1:8010`.

## 2. Prepare notamerularavel environment

```bash
cp .env.gorod-stage.example .env.gorod-stage
```

Then update:

- `DB_*` for the Laravel local database
- `WORDPRESS_DB_*` for the stage WordPress database
- `APP_URL`

## 3. Install stage data and admin access

```bash
./scripts/setup-gorod-stage.sh
```

This will:

- create the Laravel database if needed
- run migrations
- bootstrap a local admin based on WordPress user `kira`

## 4. Start the local stage

```bash
./scripts/start-gorod-stage.sh
```

Login URL:

- `http://127.0.0.1:8010/notaadmin`

## 5. Media strategy for stage

Stage 1 does not rewrite attachments into a new storage model.
It keeps WordPress paths canonical and serves media from the original `uploads` snapshot.

Recommended local symlink target:

- `public/uploads` -> `../../gorod-magazine.ru/snapshot/files/wp-content/uploads`

If frontend templates expect `/imgnews`, add a second compatibility symlink:

- `public/imgnews` -> `uploads`

## 6. Why this stage is safe

- Laravel admin uses the existing `wp_*` contour.
- Editor login can use the original WordPress user identity.
- Media remains in the original file layout during the first migration wave.
