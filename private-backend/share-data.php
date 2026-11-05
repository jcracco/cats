<?php
/**
 * share-data.php — Application data for read-only share links.
 * Used by both share entry points so they always return the same shape:
 *   - api.php?action=share&token=…[&slim=1]   (in-app shared view)
 *   - index.php?share=…&format=json[&slim=1]  (raw JSON)
 *
 * Each application has exactly one `date_closed`, read from applications.
 * The timeline's own value is exposed as `timeline_date_closed`.
 * `contacts` is never included.
 */

// Slim variant: every application column except contacts, job_description,
// user_id and the created/updated timestamps.
const SHARE_SLIM_COLS = [
    'id', 'date_applied', 'date_posted', 'date_closed',
    'company', 'via_recruiting_firm', 'recruiting_firm',
    'job_title', 'job_id', 'job_link', 'dashboard_link',
    'location_type', 'location_detail', 'days_onsite',
    'source', 'referrer_name', 'applied_through', 'resume_version',
    'rating', 'status',
    'salary_requested', 'salary_listed', 'salary_type',
    'cover_letter', 'has_outreach', 'outreach_notes', 'has_referral',
    'notes', 'timeline_id',
];

function share_user_id(PDO $pdo, string $token): ?int {
    if ($token === '') return null;
    $stmt = $pdo->prepare("SELECT id FROM users WHERE share_token = ?");
    $stmt->execute([$token]);
    $id = $stmt->fetchColumn();
    return $id === false ? null : (int)$id;
}

function share_applications(PDO $pdo, int $uid, bool $slim): array {
    $cols = $slim
        ? 'a.' . implode(',a.', SHARE_SLIM_COLS)
        : 'a.*';

    $stmt = $pdo->prepare("
        SELECT $cols,
               t.date_recruiter, t.recruiter_name, t.date_screening, t.screener_name,
               t.screening_type, t.offer_date, t.offer_notes,
               t.date_closed AS timeline_date_closed, t.pending
        FROM applications a
        LEFT JOIN timeline_entries t ON a.timeline_id = t.id
        WHERE a.user_id = ?
        ORDER BY a.date_applied ASC, a.id ASC
    ");
    $stmt->execute([$uid]);
    $apps = $stmt->fetchAll();

    $rs = $pdo->prepare("
        SELECT round_order, interview_date, interview_type, interviewer, is_final_round
        FROM interview_rounds WHERE timeline_id=? ORDER BY round_order ASC
    ");
    foreach ($apps as &$app) {
        $rounds = [];
        if ($app['timeline_id']) {
            $rs->execute([$app['timeline_id']]);
            $rounds = $rs->fetchAll();
        }
        $app['rounds'] = $rounds;
        unset($app['contacts']); // never expose contacts in shared view
    }
    unset($app);
    return $apps;
}
