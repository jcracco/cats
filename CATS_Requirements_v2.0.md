# CATS — Candidate Application Tracking System
## Requirements Document v2.0
**Status**: In Progress
**Last Updated**: June 2026
**Author**: Jerome Cracco (with Claude AI)
**Base**: Built on top of CATS v1.0 — see Requirements v1.0 for full baseline

---

## 1. Executive Summary

| | |
|---|---|
| **Problem** | CATS v1.0 is missing key functionalities that require breaking DB changes — multi-user support, CSV export, offer tracking, and additional application fields |
| **Solution** | Incremental v2.0 update with DB migration script; no backwards compatibility required |
| **Leading** | Jerome Cracco, assisted by Claude AI |
| **Related** | CATS v1.0 Requirements, migrate_v1_to_v2.sql |

---

## 2. New Features

### 2.1 Multi-User Support

**New table:** `users` — stores username, bcrypt password hash, admin flag, and a `share_token` column reserved for v3 read-only share (no UI in v2).

**DB change:** `applications` gains a `user_id` FK to `users.id`. All queries scoped to the authenticated user.

**Auth change:** Login now resolves credentials against the `users` table instead of `config.php` constants. Session token stores `user_id`.

**Admin page:** Hidden `admin.php`, accessible via `ADMIN_TOKEN` URL parameter (defined in `config.php`). Allows creating users (admin or regular), deleting users, and changing passwords. Simple HTML form, no React.

**Self-registration:** Out of scope.

**Migration:** All existing applications assigned to user_id = 1 (first inserted user).

---

### 2.2 CSV Export

**UI:** Button top-right opens a modal with:
- Field toggles for all application fields individually
- Date range filter (reuses existing date picker component)
- Toggle: Applications only vs Full export (includes timeline + interview rounds)
- Export button → triggers CSV download

**CSV rules:**
- All free text fields quoted to handle commas
- `hybrid_location` inline with location column
- `salary_listed` and `salary_requested` as separate columns
- `via_recruiting_firm` and `recruiting_firm` as separate columns
- Full export: timeline columns appended + interview rounds as dynamic columns (column count = max rounds in filtered export; shorter processes leave columns blank)
- Date range filters on `date_applied`

---

### 2.3 Offer Support

**DB changes on `timeline_entries`:**
- Add `offer_date DATE NULL`
- Add `offer_notes TEXT NULL`
- Rename `date_rejected` → `date_closed` (NULL only for Ghosted; set automatically on all other closed statuses)

**UI changes:**
- `+ Add Offer` button appears after rounds in Interview Info tab
- Offer card: date field (auto-filled today, editable) + notes textarea
- Status → Offer: auto-creates offer card with today's date
- Offer card filled: auto-sets application status to Offer
- New timeline dot for offer date
- Process Status section outcomes restructured:
  - Negative: Rejected, Ghosted
  - Neutral: Withdrawn
  - Positive: Accepted
- Timeline dot color-coded by outcome; Ghosted keeps existing behavior (dot 14 days after last activity)

---

### 2.4 Additional Application Fields

**DB changes on `applications`:**
- Add `cover_letter TINYINT(1) NOT NULL DEFAULT 0`
- Add `outreach_notes VARCHAR(500) NULL`

**UI:** In Application Info tab:
- Cover letter checkbox
- Outreach notes short text field (label: "Outreach — recruiter, HM, or other")

---

## 3. DB Schema Changes Summary

| Table | Change |
|---|---|
| `users` | New table |
| `applications` | Add `user_id`, `cover_letter`, `outreach_notes` |
| `timeline_entries` | Add `offer_date`, `offer_notes`; rename `date_rejected` → `date_closed` |

---

## 4. Migration

Single `migrate_v1_to_v2.sql` script handles all schema changes and backfills existing data. No backwards compatibility with v1 schema after migration.

---

## 5. Out of Scope

- Read-only share UI (schema column `share_token` added to `users` for v3)
- Self-registration
- Any other v1 out-of-scope items remain out of scope
