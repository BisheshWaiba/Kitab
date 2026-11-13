# Putting KITAB online (Vercel)

The Vercel project **kitab** builds the `Dockerfile` (Apache + PHP 8.3). Sessions (who is logged in) and uploaded pictures
are kept in the database, because Vercel's disk is thrown away between visits. Your own computer is unchanged: with no
environment variables set, the site still uses XAMPP, the `uploads/` folder and PHP's normal sessions.

## 1. A hosted MySQL database

Create one on a MySQL host (Aiven, TiDB Cloud, Railway, ...). It must be MySQL/MariaDB compatible and accept packets of
at least 4 MB (`max_allowed_packet`), because pictures are stored in it. Note its host, port, user, password and database name.

## 2. Copy your data into it

```powershell
# a copy of your local database (never commit this file: it holds emails and password hashes)
& "C:\xampp\mysql\bin\mysqldump.exe" -u root --skip-add-locks --skip-lock-tables --skip-comments --default-character-set=utf8mb4 --result-file=kitab.sql 25123794
```

**Before importing, look at the accounts in it.** Any account whose password is guessable (`admin123`, `password`,
`12345678`, ...) would be open to anyone once the site is public. Replace those password hashes in `kitab.sql` (not in
your local database) with new ones, or import without those users.

Import `kitab.sql` into the hosted database with the host's import tool, or HeidiSQL / DBeaver. Then delete `kitab.sql`.

## 3. Tell Vercel how to reach it

Project > Settings > Environment Variables (Production), or `vercel env add NAME production`:

| Name | Value |
| --- | --- |
| `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASS`, `DB_NAME` | from step 1 |
| `DB_SSL` | `1` (hosted databases almost always want an encrypted connection) |
| `SITE_URL` | the public address with a trailing slash (already set to `https://kitab-rho-three.vercel.app/`) |
| `MAIL_FROM` | optional, the "from" address of emails |

## 4. Copy the pictures you uploaded locally

Run once on your computer, pointed at the hosted database (book covers and profile photos in `uploads/`):

```powershell
$env:DB_HOST="..."; $env:DB_PORT="..."; $env:DB_USER="..."; $env:DB_PASS="..."; $env:DB_NAME="..."; $env:DB_SSL="1"
$env:UPLOAD_STORE="db"
php database/import_uploads.php
```

## 5. Deploy

Commit and push to GitHub: Vercel builds and deploys on every push. Then try: the home page, Log in, Add to cart,
Edit profile with a photo.

## Good to know

- **Password-reset emails do not arrive yet.** The image has no mail server. Add an email service (SMTP or an HTTP
  email API) before relying on "Forgot password".
- Never commit the development scripts (`make_admin.php`, `check_db.php`, `TEST_CREDENTIALS.txt`, the setup scripts in
  `database/`). They are in `.gitignore` and `.dockerignore`, and `.htaccess` hides them as a last resort.
- Logs: Vercel project > Logs. Database problems show up there as `KITAB: could not connect to the database`.
