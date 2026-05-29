# CATS — Candidate Application Tracking System
## Requirements Document v1.0
**Status**: Final
**Last Updated**: May 20, 2026
**Author**: Jerome Cracco (with Claude AI)

---

## 1. Executive Summary

| | |
|---|---|
| **Problem** | Track 470+ job applications with status, interview details, and statistics in a dedicated web app, replacing an overloaded Google Sheet |
| **Solution** | Single-page web app hosted on existing PHP/MySQL server, extending the current interview timeline tracker |
| **Leading** | Jerome Cracco, assisted by Claude AI |
| **Related** | Current pipeline interview timeline — becomes the Timeline tab of CATS |

---

## 2. Use Case

Applications are currently tracked in a Google Sheet. At ~470 entries with nested interview data, the sheet loads slowly and grows exponentially with each new column. Information is also duplicated between the sheet and the separate interview timeline tracker.

CATS replaces the Google Sheet with a dedicated web app that:
- Shows a compact application list with details surfaced on demand
- Serves as the single source of truth for both application tracking and interview tracking
- Updates the interview timeline automatically when interview information is added

---

## 3. Overall Flow

1. Candidate rates a JD (with AI assistance, outside this app) and decides to apply
2. Candidate applies externally, then opens CATS and adds the application — status defaults to **Applied**
3. As the process progresses, candidate updates status and interview details
4. Interview details automatically populate the Timeline tab
5. Candidate closes the application by setting a terminal status

Deletion is reserved for mistakenly added entries, requires typing "DELETE", and cascades to linked timeline entry and all interview rounds.

---

## 4. Technical Architecture

### 4.1 Backend
- **Language**: PHP (existing server)
- **Database**: MySQL — chosen over JSON for scale (470+ entries, nested data, filtered queries)
- **Auth**: Single username + password, session-based
- **Endpoint**: `api.php` handles all CRUD. `save.php` and `data.json` retired post-migration.

### 4.2 Frontend
- React via CDN + Babel standalone (no build step)
- Existing `pipeline.css` extended
- Dark/light theme toggle preserved

### 4.3 Single page, role-aware

| Element | Unauthenticated | Authenticated |
|---|---|---|
| Both tabs | Read-only | Editable |
| Add button | Hidden | Visible |
| Edit/delete in modal | Hidden | Visible |
| Interviewer names/notes | Hidden | Visible |
| Login link | Visible (top right) | Replaced by Logout |

Login via "Admin Login" link → modal. Session persists in sessionStorage.

### 4.4 File structure
```
index.php                   — single page, role-aware
pipeline.css                — shared styles (extended)
api.php                     — all MySQL CRUD
_pipeline-core.php          — shared React components
_pipeline-modal.php         — all modals (application, interview, login)
```

Private (above web root):
```
/home/httpd/vhosts/cracco.ch/subdomains/jerome/private/
  _pipeline-core.php
  _pipeline-modal.php
  db.php
```

### 4.5 Database

#### Connection (`db.php`)
```php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'cats_db');
define('DB_USER', 'cats_user');
define('DB_PASS', 'cats_pass');  // replace before deploy
```
```sql
CREATE DATABASE cats_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'cats_user'@'localhost' IDENTIFIED BY 'cats_pass';
GRANT ALL PRIVILEGES ON cats_db.* TO 'cats_user'@'localhost';
FLUSH PRIVILEGES;
```

#### `applications` table
| Column | Type | Notes |
|---|---|---|
| `id` | INT AUTO_INCREMENT PK | |
| `date_applied` | DATE | Required |
| `company` | VARCHAR(255) | Optional if via_recruiting_firm = 1 |
| `via_recruiting_firm` | TINYINT(1) | Default 0 |
| `recruiting_firm` | VARCHAR(255) | Required if via_recruiting_firm = 1 |
| `job_title` | VARCHAR(255) | Required |
| `location_type` | ENUM('Remote','Hybrid') | Default: Remote |
| `hybrid_location` | VARCHAR(255) | Optional |
| `days_onsite` | VARCHAR(50) | Optional |
| `source` | VARCHAR(100) | See 6.1 |
| `applied_through` | VARCHAR(100) | See 6.2 |
| `resume_version` | ENUM('TPM','SPO','TIM','Custom') NULL | Optional |
| `rating` | TINYINT UNSIGNED | 0–100 |
| `status` | ENUM('Applied','Interviewing','Offer','Accepted','Rejected','Ghosted','Not Selected','No Answer','Withdrawn') | Default: Applied |
| `job_id` | VARCHAR(100) | Optional |
| `job_link` | TEXT | Optional |
| `dashboard_link` | TEXT | Optional |
| `salary_requested` | VARCHAR(50) | Optional |
| `salary_listed` | VARCHAR(50) | Optional — salary as posted by company |
| `salary_type` | ENUM('Yearly','Hourly') | Default: Yearly |
| `contacts` | VARCHAR(500) | Optional |
| `notes` | TEXT | Optional |
| `job_description` | LONGTEXT | Optional |
| `timeline_id` | INT NULL | FK → timeline_entries.id |
| `created_at` | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | |
| `updated_at` | TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | |

#### `timeline_entries` table
| Column | Type | Notes |
|---|---|---|
| `id` | INT AUTO_INCREMENT PK | |
| `company` | VARCHAR(255) | |
| `position` | VARCHAR(255) | |
| `rating` | TINYINT UNSIGNED | |
| `date_applied` | DATE | |
| `date_recruiter` | DATE NULL | Date of first recruiter response |
| `recruiter_name` | VARCHAR(255) NULL | |
| `date_screening` | DATE NULL | Date screening actually occurred |
| `screener_name` | VARCHAR(255) NULL | Often same as recruiter; may differ for recruiting firm roles |
| `screening_type` | ENUM('Phone','Async','Home Assessment','Zoom','MS Teams','Google Meet','On Site') NULL | |
| `pending` | TINYINT(1) | Default 1 |
| `date_rejected` | DATE NULL | |
| `application_id` | INT NULL | FK → applications.id |
| `created_at` | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | |
| `updated_at` | TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | |

#### `interview_rounds` table
| Column | Type | Notes |
|---|---|---|
| `id` | INT AUTO_INCREMENT PK | |
| `timeline_id` | INT | FK → timeline_entries.id |
| `round_order` | TINYINT | 1 = 1st round, 2 = 2nd… Screening is on timeline_entries, not here |
| `interview_date` | DATE | |
| `interview_type` | ENUM('Phone','Async','Home Assessment','Zoom','MS Teams','Google Meet','On Site') | |
| `interviewer` | VARCHAR(500) | Authenticated users only |
| `notes` | TEXT | Authenticated users only |
| `created_at` | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | |
| `updated_at` | TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | |

### 4.6 CSV migration mapping

| Sheet column | Target | Column | Notes |
|---|---|---|---|
| Date Applied | applications | date_applied | |
| Company | applications | company | |
| Position | applications | job_title | |
| Status | applications | status | See mapping table 4.7 |
| Place | applications | hybrid_location | location_type = Hybrid if non-empty, else Remote |
| Source | applications | source | |
| Applied through | applications | applied_through | |
| Rating | applications | rating | |
| Link | applications | job_link | |
| Salary Requested | applications | salary_requested | |
| Salary Listed | applications | salary_listed | |
| Date Recruiter contact | timeline_entries | date_recruiter | Create timeline_entry if any date column non-empty |
| Recruiter Name | timeline_entries | recruiter_name | |
| Date Screening | timeline_entries | date_screening | |
| Screener Name | timeline_entries | screener_name | |
| Screening Type | timeline_entries | screening_type | Map to ENUM; unknown → NULL |
| Date 1st–5th Round | interview_rounds | interview_date | round_order = 1–5 |
| Contact 1st–5th Round | interview_rounds | interviewer | round_order = 1–5 |
| Type 1st–5th Round | interview_rounds | interview_type | Map to ENUM; unknown → NULL |
| Rejection Date | timeline_entries | date_rejected | pending = 0 if set |

### 4.7 Status migration mapping

| Google Sheet value | CATS ENUM value |
|---|---|
| (blank) | Applied |
| In Progress | Interviewing |
| Not Selected | Not Selected |
| Ghosted | Ghosted |
| Rejected | Rejected |
| Withdrawn | Withdrawn |
| Accepted | Accepted |
| No Answer | No Answer |
| (any unknown) | Applied |

---

## 5. Navigation and Layout

### 5.1 Tabs
**Applications** | **Timeline** — always visible
- URL hash: `#applications` (default), `#timeline`
- Browser back/forward works

### 5.2 Top bar
- Left: app title + eyebrow
- Right: theme toggle + "Admin Login" (unauthenticated) or + Add + "Logout" (authenticated)
- Add: adds Application on Applications tab, adds Timeline entry on Timeline tab

---

## 6. Reference Data

### 6.1 Source
Company website, LinkedIn, Recruiter Outreach, Referral, Dice, Recruiting Agency, Indeed, Cybercoders, Jobgether, BuiltIn, Hiring Café, Other

### 6.2 Applied Through
**Direct**: LinkedIn Easy Apply, Indeed, Dice, Cybercoders, Email, Recruiting Firm Portal, Other/Unknown

**ATS**: Workday, SmartRecruiters, ADP, Greenhouse, iCIMS, Rippling, Oracle, Taleo, Bamboo, Lever, Oracle Cloud, Dayforce, SAP SuccessFactors, Ashby, Kronos, Pinpoint, Humi, Paylocity, Hirebridge, Kula, Paycor, UltiPro, Jobvite, JazzHR, Eightfold, Workable, Gem, Trakstar, Paycom, Dover, ApplyToJob, Avature, Teamtailor, Breezy

### 6.3 Status values, grouping and display

| Group | Status | Meaning |
|---|---|---|
| **Active** | Interviewing | Progressing — recruiter contact or beyond |
| **Active** | Offer | Offer received, decision pending |
| **Pending** | Applied | Submitted, waiting — not yet active or closed |
| **Closed — Positive** | Accepted | Offer accepted |
| **Closed — Reached Recruiter** | Rejected | Had recruiter contact; actively rejected |
| **Closed — Reached Recruiter** | Ghosted | Was in active process; contact stopped |
| **Closed — Did Not Progress** | Not Selected | No recruiter contact; ATS or automated rejection |
| **Closed — Did Not Progress** | No Answer | No rejection but job no longer posted |
| **Closed — Did Not Progress** | Withdrawn | Candidate withdrew |

**Table sort order**: Active → Pending → Closed Positive → Closed Reached Recruiter → Closed Did Not Progress; date_applied DESC within each group.

### 6.4 Interview Type
Phone, Async, Home Assessment, Zoom, MS Teams, Google Meet, On Site

### 6.5 Resume Version
TPM, SPO, TIM, Custom

---

## 7. Applications Tab

### 7.1 Stats bar
| Stat | Definition |
|---|---|
| Total | All rows |
| Active | status IN (Interviewing, Offer) |
| Reached Screening | Has linked timeline_entry with date_screening set |
| Average Rating | AVG(rating) |
| This Week | date_applied in current calendar week (Mon–Sun) |
| This Month | date_applied in current calendar month |
| Avg / Week | Total ÷ calendar weeks since first application date |
| Avg / Month | Total ÷ calendar months since first application date |
| Closed — Positive | status = Accepted |
| Closed — Reached Recruiter | status IN (Rejected, Ghosted) |
| Closed — Did Not Progress | status IN (Not Selected, No Answer, Withdrawn) |

### 7.2 Filters (always visible, inline)
- Free text search — company + job title
- Status group filter — multi-select pills
- Resume version filter — multi-select pills
- Sort — Date Applied desc (default) / Date Applied asc / Rating desc

### 7.3 Table columns
Date Applied · Company · Job Title · Status badge · Source · Applied Through · Resume · Rating · Location · Salary Listed

### 7.4 Add application modal (authenticated)

**Required:**
- Date Applied (auto-fills today)
- Via Recruiting Firm checkbox → Recruiting Firm Name (required), Company (optional)
- Company (required if not via recruiting firm)
- Job Title
- Location: Remote (default) / Hybrid → Location text + optional Days Onsite
- Source
- Applied Through
- Rating (0–100)

**Optional:**
- Job ID, Job Link, Dashboard Link
- Salary Requested + Salary Listed + Salary Type (Yearly / Hourly, default Yearly)
- Resume Version
- Contacts, Notes, Job Description

Status defaults to **Applied** on create. Not shown in add modal — only in edit mode.

### 7.5 View modal (all users)
Two tabs: **Application Info** | **Interview Info**

**Application Info**: all fields. Authenticated: pencil (edit) + trash (delete).

**Interview Info**:
- Recruiter contact: date + name (name: auth only)
- Screening: date + type + screener name (name: auth only)
- Rounds 1–N: date + type + interviewer + notes (interviewer/notes: auth only)
- Authenticated: add/remove rounds

### 7.6 Edit (authenticated)
All fields editable. Status dropdown appears in edit mode:
- Status → Interviewing + no timeline_id → auto-create timeline entry
- timeline_id set → show "View in Timeline →" link

### 7.7 Delete (authenticated)
Type "DELETE" to confirm. Cascades: application + interview_rounds + timeline_entries.

---

## 8. Timeline Tab

### 8.1 Visual
No changes. All existing behavior fully preserved.

### 8.2 Data source
Reads from `timeline_entries` + `interview_rounds` via `api.php`. Same data shape as current JSON — no frontend logic changes needed beyond swapping the fetch call.

### 8.3 Link indicator
Rows with `application_id` set show a small icon. Clicking opens the linked application detail modal.

### 8.4 Timeline-only entries
Entries without a linked application work normally with no link indicator.

---

## 9. All Resolved Decisions

| Decision | Resolution |
|---|---|
| JSON vs MySQL | MySQL |
| Single page vs two pages | Single page, role-aware |
| Default status | Applied (not NULL) |
| Delete cascade | Yes — application + interview_rounds + timeline_entries |
| Custom resume version | ENUM only, no free text |
| Notes field | Yes, per application |
| Source vs Applied Through | Two separate fields |
| Interview type | Communication channel; stage inferred from round_order |
| Recruiter vs screening | Separate events on timeline_entries; rounds start at round_order = 1 in interview_rounds |
| VARCHAR vs ENUM | ENUM for fixed lists; VARCHAR for source + applied_through |
| Salary | Two fields: salary_requested + salary_listed, both free text |
| Interview notes visibility | Authenticated only |
| Stats periods | Calendar week (Mon–Sun) + calendar month + avg/week + avg/month |
| Closed group split | Positive / Reached Recruiter / Did Not Progress |
| Status grouping | Active / Pending / Closed (three sub-groups) |
| updated_at | On all three tables |
| DB credentials | Placeholder in db.php |
| Timeline FK linking | Migration script auto-matches; Jerome corrects manually |
| CSV import | One-time migration script with documented column mapping |
| Build step | None — React via CDN + Babel |

---

## 10. Out of Scope

- Email/calendar integration
- CSV export
- Duplicate detection
- Mobile-specific layout
- Multi-user access
- AI-assisted JD rating within the app
