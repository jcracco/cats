# Changelog

## v2.0.0 — 2026-06-10
- Multi-user support with `users` table; all applications scoped to authenticated user (breaking schema change — run migrate_v1_to_v2.sql)
- Admin page (`admin.php`) for user management via `ADMIN_TOKEN` URL parameter
- CSV export modal with field toggles, date range filter, and optional full export including timeline and interview rounds
- Offer support: `offer_date` and `offer_notes` added to timeline entries; offer card in Interview Info tab; new timeline dot for offer date
- Process Status outcomes restructured: Negative (Rejected/Ghosted), Neutral (Withdrawn), Positive (Accepted)
- `date_rejected` renamed to `date_closed` on `timeline_entries`; NULL only for Ghosted
- Cover letter checkbox added to application fields
- Outreach notes field added to application fields
- `share_token` column added to `users` table to support read-only sharing capability

## v1.0.0 — 2026-06-10
- Initial release
- Single-page application tracker with full CRUD
- Status grouping: Active / Pending / Closed (Reached Recruiter / Did Not Progress / Positive)
- Interview Info tab with screening, rounds, and process status tracking
- Timeline tab with visual dot/bar timeline, gap detection, and stats
- Right-click context menu for instant status change
- Filters: search, status, resume version, source, applied-through, rating, salary, date range
- Stats bar: total, pipeline depth, activity, quality metrics
- Dark/light theme toggle
- Cookie-based auth with 30-day session
- Demo mode with client-side mock API and seed data
- Auto-close stale Applied applications after 60 days (cron)
- Daily DB backup with optional email (cron)
