# Job Board Platform

A job board where companies post openings and job seekers search, apply, and track
their applications. Built with a custom PHP MVC architecture (no framework) on MySQL.

## Features

**Public**

- Job search with keyword, category, job type, location and salary filters, plus
  sorting by newest, oldest, job title A-Z or highest salary
- Paginated results with the applied/saved state visible on each card
- Job detail page with requirements, salary range, deadline and apply form
- Public company pages with logo, description, website and their open listings
- A listing past its deadline is marked expired on the search results and in the
  employer's own list

**Seeker**

- Register, sign in, sign out, and reset a forgotten password
- Profile with headline, skills, phone, location and CV
- Upload, replace and delete a resume (PDF or DOCX)
- Apply with an optional cover letter; the CV is snapshotted per application
- Application history with withdraw
- Save and unsave jobs

**Employer**

- Register as a company, with a company profile created alongside the account
- Dashboard with live counts of listings, applications and shortlisted candidates
- Post and edit listings; new or substantively edited listings go to a review queue
- Close a listing and reopen it later without a second review
- Applicant list filtered by job and status, with the applicant's CV download
- Move an applicant to under review, shortlisted, rejected or hired, notifying them
- Edit company details, website and logo (PNG, JPG, WebP, up to 1 MB)

**Admin**

- Platform overview with counts and the pending review queue
- Account search and filtering by role and status
- Deactivate an account, with a guard against deactivating yourself or an employer
  who still has live listings
- Approve or close any listing; the employer is notified either way
- Manage job categories, including rename, and delete only when unused

**Shared**

- Notification inbox for every role, with per-item and mark-all-read
- Role-aware navigation, so a seeker never sees employer or admin links
- Flash messages, form repopulation, and inline validation errors

## Requirements

- PHP 7.4 or newer (tested on 7.4 and 8.2)
- MySQL / MariaDB 5.7+
- PDO with the `mysql` driver

No Composer dependencies. There is no `vendor/` directory to install.

## Setup

1. **Point a web server at `public/`.** Under XAMPP, copy or symlink the project
   into `htdocs` and browse to `http://localhost/<project>/public/`. The front
   controller is the only reachable PHP entry point; everything outside `public/`
   is deliberately unreachable over HTTP.

   The built-in server works too:

   ```bash
   php -S localhost:8000 -t public
   ```

2. **Create the configuration.**

   ```bash
   cp config/config.example.php config/config.php
   ```

   Then edit `config/config.php` with your database credentials. The real file is
   ignored by git.

3. **Create the database.** `schema.sql` creates the database and all tables, and
   `seed.sql` fills it with demo data.

   ```bash
   mysql -u root < database/schema.sql
   mysql -u root < database/seed.sql
   ```

   The seed is optional; skip it for an empty platform.

4. **Make sure the writable directories exist** and deny web access to them. Both
   ship with an `.htaccess` that blocks direct requests, so Apache handles it
   automatically.

   ```
   storage/resumes/        # private CV files, never served directly
   public/uploads/logos/   # company logos, served as images only
   ```

5. **Set the environment** in `config/config.php`. Leave it as `local` while you
   are developing: password reset links are printed on screen and errors are
   detailed. Set it to `production` before the app is publicly reachable, and give
   the database user its own password rather than relying on `root`.

### Demo accounts

Created by `seed.sql`:

| Role     | Email                      | Password       |
| -------- | -------------------------- | -------------- |
| Admin    | `admin@jobboard.test`      | `Admin@12345`  |
| Seeker   | `sara@jobboard.test`       | `Password@123` |
| Seeker   | `daniel@jobboard.test`     | `Password@123` |
| Employer | `maya@northwind.test`      | `Password@123` |
| Employer | `omar@brightlabs.test`     | `Password@123` |
| Seeker   | `inactive@jobboard.test`   | `Password@123` |

The `inactive@jobboard.test` account is deactivated, so it is useful for checking
that a deactivated sign-in is refused.

### Upgrading an existing install

`database/migrations/` holds the statements needed to bring an older database up
to date. They are idempotent, so running one twice is harmless.

```
003_listing_approval_stamp.sql   adds jobs.approved_at
```

`schema.sql` already contains everything the migrations add, so a fresh install
needs none of them.

## How it works

```
public/index.php     front controller: parses the URL, dispatches, renders
app/core/            Router, Controller, Model, View, Database, Validator, Session, Csrf, App
app/models/          one class per table, all queries prepared
app/views/           plain PHP templates
app/controllers/     request handling and validation per area
app/helpers/         small shared utilities (redirects, uploads, formatting)
config/              application configuration
database/            schema, seed, migrations
tools/               check-classes.php, the CSS class audit
storage/             private file storage
```

Routes live in `app/routes.php` and use `{id:\d+}` patterns, so a malformed id
never reaches a controller. Views escape output through `e()`, which is why the
templates are plain PHP rather than a template language.

### Listings and the review queue

A listing is `pending`, `open` or `closed`. New listings start as `pending`, and
so does a listing whose substantive fields an employer edits, so a job title or
salary cannot be changed into something misleading while staying visible. Only an
admin can publish.

`approved_at` records when that happened. An employer who closes their own
approved listing can reopen it directly; one that was never approved has to go
back through review.

### Security

- Prepared statements throughout, including `IN` and `ORDER BY` lists built from
  whitelisted values
- CSRF token on every state-changing form, verified before the handler runs
- Role checks on each route, plus ownership checks on every employer query, so
  one employer cannot read or write another's listings, applicants or CVs
- Resumes served through a controller that checks ownership, never from a
  guessable path; the applicant CV is preferred over the seeker's current one
- Uploads checked by extension, sniffed MIME type, magic bytes, and `getimagesize`
  for images, then stored under a random name
- Redirects confined to known local paths, so a crafted `return_to` cannot send a
  user off-site
- Output escaped at render time; LIKE wildcards in admin search terms escaped
- `htmlspecialchars` with `ENT_QUOTES` everywhere, including in attributes

## Tests

The test suite drives the running app over HTTP, so start the server first:

```bash
php -S 127.0.0.1:8099 -t public
```

Then run each file. They live outside the repository because they are
development tooling, not part of the deliverable:

```bash
php test_register.php            # 41 checks  auth, roles, duplicates, resets
php test_password_reset.php      # 35 checks  token lifecycle, single use, expiry
php test_seeker_area.php         # 34 checks  profile, resume, saved jobs
php test_jobs.php                # 43 checks  search, detail, apply, withdraw
php test_notifications.php       # 23 checks  inbox, read state, ownership
php test_employer_area.php      # 108 checks  employer and admin areas
```

284 checks in total, all passing. Set `JBP_BASE` to test a different host.

These tests create and modify rows, so reseed afterwards:

```bash
mysql -u root < database/schema.sql
mysql -u root < database/seed.sql
```

There is also a CSS audit, which reports any class used in a view that no
stylesheet defines, including prefixes a view completes at runtime:

```bash
php tools/check-classes.php
```

## Screenshots

_Coming soon._
