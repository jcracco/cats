# CATS — Candidate Application Tracking System

A self-hosted job search tracker built to replace a 470-row Google Sheet. Tracks applications, interview pipelines, and delivery metrics across a full job search lifecycle.

**[Live Demo →](https://demo.cracco.ch/cats)** — demo / demo

---

## Why I Built This

After my layoff in November 2025 I was tracking applications in a Google Sheet. By month six it had 470+ rows, nested interview columns, and was taking 10+ seconds to load. I built CATS in parallel with my job search to solve the problem and to demonstrate the kind of delivery infrastructure work I do professionally.

---

## Features

**Applications tab**
- Full application list with status badges, source pills, salary, rating, recruiting firm indicators
- Collapsible status groups: Active → Pending → Reached Recruiter → Did Not Progress → Withdrawn
- Right-click any row for instant status update (no modal needed)
- Filters: search, status, resume version, source, applied via, rating slider, salary slider, date range
- Stats bar: total, active, reached recruiter, reached final round, avg/week, avg/month

**Timeline tab**
- Visual interview pipeline — dots and bars across a time axis
- Shows recruiter contact, screening, interview rounds, rejection or ghosted state
- Gap detection: flags periods of inactivity
- Hover tooltips with full process details and durations

**Application modal**
- Two-tab layout: Application Info and Interview Info
- Process status controls: Active / Ghosted / Closed with rejection date
- Final Round checkbox on last interview round
- Job description stored and toggled inline
- Right-click → Interviewing auto-creates timeline entry with today's recruiter date

**Other**
- Dark / light theme toggle
- Cookie-based auth, 30-day session
- Demo mode: same codebase, mock API, in-memory data, resets on tab close
- Auto-close stale applications (60 days, no response → No Answer) via daily cron
- Daily DB backup with optional email delivery

---

## Tech Stack

| Layer | Technology |
|---|---|
| Frontend | React 18 (CDN + Babel standalone, no build step) |
| Backend | PHP 8, PDO |
| Database | MySQL / MariaDB |
| Hosting | Shared hosting (Plesk) |
| Auth | bcrypt password hash, server-side session tokens, 30-day cookie |

No npm, no webpack, no framework — intentional. This runs on any shared PHP host with zero build tooling.

---

## Project Structure

```
CATS/
├── public-frontend/            # Web root
│   ├── index.php               # Single page app shell, demo detection
│   ├── api.php                 # REST-ish API (all CRUD)
│   ├── pipeline.css            # Styles (dark + light theme)
│   ├── mock-api.js
│   └── bootstrap.example.php   # copy to bootstrap.php, set PRIVATE_PATH
│
├── private-backend/        # Above web root (never web-accessible)
│   ├── config.example.php  # Configuration template → copy to config.php
│   ├── db.php              # PDO connection function
│   ├── pipeline-core.php   # React components: timeline, applications tab
│   ├── pipeline-modal.php  # React components: modals
│   ├── setup.sql           # Database schema (fresh install)
│   ├── cron_auto_close.php # Daily cron: auto-close stale applications
│   └── backup_db.php       # Daily cron: DB backup + optional email
│
├── .gitignore
├── README.md
└── REQUIREMENTS.md         # Full product requirements document
```

---

## Setup (self-hosting)

> These steps are for reference. The demo at the link above requires no setup.

**1. Database**

Create a database and user, then run `private-backend/setup.sql` in phpMyAdmin.

```sql
CREATE DATABASE cats_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'cats_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON cats_db.* TO 'cats_user'@'localhost';
```

**2. Configuration**

Copy `private-backend/config.example.php` to `config.php` in the same folder and fill in your values.

Copy `public-frontend/bootstrap.example.php` to `bootstrap.php` in the same folder and set `PRIVATE_PATH` to the absolute path of your `private-backend/` directory.

To generate a hashed password, run once and copy the output:
```php
<?php echo password_hash('your_password', PASSWORD_DEFAULT);
```

**3. Deploy**

- Upload `private-backend/` above your web root
- Upload `public-frontend/` to your web root
- Set `PRIVATE_PATH` in `config.php` to the absolute path of `private-backend/`

**4. Cron jobs** (optional but recommended)

```
Daily 02:00 — php /path/to/private-backend/cron_auto_close.php
Daily 03:00 — php /path/to/private-backend/backup_db.php
```

---

## Demo Mode

The same codebase serves both production and demo. Detection is hostname-based:

```php
// in index.php
$IS_DEMO = strpos($_SERVER['HTTP_HOST'], IS_DEMO_DOMAIN) !== false;
```

When `IS_DEMO` is true:
- `mock-api.js` loads and overrides the real `api()` function
- All data is served from an in-memory JavaScript dataset
- Changes persist in `sessionStorage` — survive refresh, reset on tab close
- An amber banner identifies the demo
- Login modal shows `demo / demo` credentials

No separate codebase. No separate server. Update `pipeline-core.php` once — both production and demo reflect it immediately.

---

## Background

Built during an active job search following a November 2025 layoff from Payscale, where I was a Technical Delivery Manager. The app currently tracks 9 months of real pipeline data — 25+ interview processes, multiple final rounds, and the full arc from application to outcome.

The work in this repo mirrors what I do professionally: identifying a process problem, designing a solution, building the infrastructure, and iterating on it while using it every day.

---

## Author

**Jerome Cracco** — Technical Program Manager / Senior Product Owner  
[LinkedIn](https://www.linkedin.com/in/jeromecracco/) · Boston, MA
