<?php
/**
 * api.php — CATS REST API
 * All responses: Content-Type: application/json
 *
 * Auth uses a persistent cookie (cats_session) validated server-side.
 * Cookie is set on login, expires in 30 days, renewed on each authenticated request.
 *
 * Routes:
 *   POST   ?action=login
 *   POST   ?action=logout
 *   GET    ?action=session
 *
 *   GET    ?action=applications[&search=&status=&resume=&sort=]
 *   GET    ?action=application&id=N
 *   POST   ?action=application_add
 *   POST   ?action=application_update&id=N
 *   POST   ?action=application_delete&id=N
 *
 *   GET    ?action=timeline
 *   GET    ?action=timeline_entry&id=N
 *   POST   ?action=timeline_add
 *   POST   ?action=timeline_update&id=N
 *   POST   ?action=timeline_delete&id=N
 *
 *   GET    ?action=rounds&timeline_id=N
 *   POST   ?action=round_save
 *   POST   ?action=round_delete&id=N
 *
 *   GET    ?action=stats
 */

require_once __DIR__ . '/bootstrap.php';
require_once PRIVATE_PATH . 'config.php';
require_once PRIVATE_PATH . 'db.php';

// Block direct API access from the demo domain
if (isset($_SERVER['HTTP_HOST']) && 
    strpos($_SERVER['HTTP_HOST'], IS_DEMO_DOMAIN) !== false) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'API not available in demo mode']);
    exit;
}

// ── Session token store (flat file, above web root) ───────────────────────────
define('TOKEN_FILE', PRIVATE_PATH . 'session_tokens.json');

function load_tokens(): array {
    if (!file_exists(TOKEN_FILE)) return [];
    $data = json_decode(file_get_contents(TOKEN_FILE), true);
    // Purge expired tokens
    $now = time();
    $data = array_filter($data ?? [], fn($t) => $t['expires'] > $now);
    return $data;
}
function save_tokens(array $tokens): void {
    file_put_contents(TOKEN_FILE, json_encode(array_values($tokens)));
}
function generate_token(): string {
    return bin2hex(random_bytes(32));
}
function is_authenticated(): bool {
    $cookie = $_COOKIE[SESSION_COOKIE] ?? '';
    if (!$cookie) return false;
    $tokens = load_tokens();
    foreach ($tokens as $t) {
        if (hash_equals($t['token'], $cookie) && $t['expires'] > time()) {
            return true;
        }
    }
    return false;
}
function set_auth_cookie(): void {
    $token   = generate_token();
    $expires = time() + SESSION_DURATION;
    $tokens  = load_tokens();
    $tokens[] = ['token' => $token, 'expires' => $expires];
    save_tokens($tokens);
    setcookie(SESSION_COOKIE, $token, [
        'expires'  => $expires,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        // 'secure' => true,  // uncomment when on HTTPS
    ]);
}
function clear_auth_cookie(): void {
    $cookie = $_COOKIE[SESSION_COOKIE] ?? '';
    if ($cookie) {
        $tokens = load_tokens();
        $tokens = array_filter($tokens, fn($t) => !hash_equals($t['token'], $cookie));
        save_tokens(array_values($tokens));
    }
    setcookie(SESSION_COOKIE, '', time() - 3600, '/');
}

// ── Response helpers ──────────────────────────────────────────────────────────
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

function ok(mixed $data = null): void {
    echo json_encode(['ok' => true, 'data' => $data]);
    exit;
}
function fail(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}
function body(): array {
    return json_decode(file_get_contents('php://input'), true) ?? [];
}
function auth_required(): void {
    if (!is_authenticated()) fail('Not authenticated', 401);
}
function str_or_null(array $data, string $key): ?string {
    $v = trim($data[$key] ?? '');
    return $v === '' ? null : $v;
}
function date_or_null(array $data, string $key): ?string {
    $v = trim($data[$key] ?? '');
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : null;
}
function int_or_null(array $data, string $key): ?int {
    $v = $data[$key] ?? null;
    return ($v !== null && $v !== '') ? (int)$v : null;
}

$action = $_GET['action'] ?? '';

// ── Require authentication for all actions except session + login ─────────────
// Demo domain is already blocked above — this protects production from
// unauthenticated access to any data endpoint
if (!in_array($action, ['session', 'login']) && !is_authenticated()) {
    fail('Not authenticated', 401);
}

// ── Auth ──────────────────────────────────────────────────────────────────────
if ($action === 'login') {
    $b = body();
    if (($b['username'] ?? '') === AUTH_USER && password_verify($b['password'] ?? '', AUTH_PASS)) {
        set_auth_cookie();
        ok(['auth' => true]);
    } else {
        fail('Invalid credentials', 401);
    }
}

if ($action === 'logout') {
    clear_auth_cookie();
    ok();
}

if ($action === 'session') {
    ok(['auth' => is_authenticated()]);
}

// ── Stats ─────────────────────────────────────────────────────────────────────
if ($action === 'stats') {
    $pdo = db();
    $total              = (int)$pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();
    $active             = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE status IN ('Interviewing','Offer')")->fetchColumn();
    $reached_recruiter  = (int)$pdo->query("SELECT COUNT(*) FROM applications a JOIN timeline_entries t ON a.timeline_id=t.id WHERE t.date_recruiter IS NOT NULL")->fetchColumn();
    $avg_rating         = round((float)$pdo->query("SELECT AVG(rating) FROM applications WHERE rating IS NOT NULL")->fetchColumn(), 1);
    $closed_positive    = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE status='Accepted'")->fetchColumn();
    $closed_reached     = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE status IN ('Rejected','Ghosted')")->fetchColumn();
    $closed_no_prog     = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE status IN ('Not Selected','No Answer','Withdrawn')")->fetchColumn();
    $this_week          = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE YEARWEEK(date_applied,1)=YEARWEEK(CURDATE(),1)")->fetchColumn();
    $this_month         = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE YEAR(date_applied)=YEAR(CURDATE()) AND MONTH(date_applied)=MONTH(CURDATE())")->fetchColumn();
    $first              = $pdo->query("SELECT MIN(date_applied) FROM applications")->fetchColumn();
    $avg_week = $avg_month = null;
    if ($first) {
        $weeks     = max(1, ceil((time() - strtotime($first)) / 604800));
        $months    = max(1, (int)$pdo->query("SELECT PERIOD_DIFF(DATE_FORMAT(CURDATE(),'%Y%m'),DATE_FORMAT(MIN(date_applied),'%Y%m'))+1 FROM applications")->fetchColumn());
        $avg_week  = round($total / $weeks, 1);
        $avg_month = round($total / $months, 1);
    }
    $reached_final_round = (int)$pdo->query("SELECT COUNT(DISTINCT t.application_id) FROM interview_rounds r JOIN timeline_entries t ON r.timeline_id=t.id WHERE r.is_final_round=1 AND t.application_id IS NOT NULL")->fetchColumn();
    ok(compact('total','active','reached_recruiter','reached_final_round','avg_rating','closed_positive','closed_reached','closed_no_prog','this_week','this_month','avg_week','avg_month'));
}

// ── Applications — list ───────────────────────────────────────────────────────
if ($action === 'applications') {
    $pdo     = db();
    $is_auth = is_authenticated();
    $where   = ['1=1'];
    $params  = [];

    $search = trim($_GET['search'] ?? '');
    if ($search !== '') {
        $where[]  = '(a.company LIKE ? OR a.job_title LIKE ? OR a.recruiting_firm LIKE ?)';
        $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
    }
    $status = trim($_GET['status'] ?? '');
    if ($status !== '') {
        $statuses = array_filter(array_map('trim', explode(',', $status)));
        if ($statuses) {
            $placeholders = implode(',', array_fill(0, count($statuses), '?'));
            $where[]  = "a.status IN ($placeholders)";
            $params   = array_merge($params, $statuses);
        }
    }
    $applied_via = trim($_GET['applied'] ?? '');
    if ($applied_via !== '') {
        $avs = array_filter(array_map('trim', explode(',', $applied_via)));
        if ($avs) {
            $placeholders = implode(',', array_fill(0, count($avs), '?'));
            $where[]  = "a.applied_through IN ($placeholders)";
            $params   = array_merge($params, $avs);
        }
    }
    $rating_min = (int)($_GET['rating_min'] ?? 0);
    if ($rating_min > 0) {
        $where[]  = "a.rating >= ?";
        $params[] = $rating_min;
    }
    $source_filter = trim($_GET['source_filter'] ?? '');
    if ($source_filter !== '') {
        $srcs = array_filter(array_map('trim', explode(',', $source_filter)));
        if ($srcs) {
            $placeholders = implode(',', array_fill(0, count($srcs), '?'));
            $where[]  = "a.source IN ($placeholders)";
            $params   = array_merge($params, $srcs);
        }
    }
    $salary_min         = (int)($_GET['salary_min'] ?? 0);
    $salary_type_filter = trim($_GET['salary_type_filter'] ?? 'Yearly');
    if ($salary_min > 0) {
        // Parse first number from salary field (handles "120-140", "130", "68-75/h")
        // Only filter rows that have salary data matching the selected type
        // REGEXP_SUBSTR extracts first number from salary string (e.g. "120-140" → 120)
        // Rows with no salary data are excluded when filter is active
        $where[] = "(
            a.salary_type = ?
            AND COALESCE(a.salary_listed, a.salary_requested) IS NOT NULL
            AND CAST(REGEXP_SUBSTR(COALESCE(a.salary_listed, a.salary_requested), '[0-9]+') AS UNSIGNED) >= ?
        )";
        $params[] = $salary_type_filter;
        $params[] = $salary_min;
    }
    $date_from = trim($_GET['date_from'] ?? '');
    $date_to   = trim($_GET['date_to']   ?? '');
    if ($date_from) { $where[] = "a.date_applied >= ?"; $params[] = $date_from; }
    if ($date_to)   { $where[] = "a.date_applied <= ?"; $params[] = $date_to; }
    $resume = trim($_GET['resume'] ?? '');
    if ($resume !== '') {
        $versions = array_filter(array_map('trim', explode(',', $resume)));
        if ($versions) {
            $placeholders = implode(',', array_fill(0, count($versions), '?'));
            $where[]  = "a.resume_version IN ($placeholders)";
            $params   = array_merge($params, $versions);
        }
    }
    $sort_map = [
        'date_desc'   => 'a.date_applied DESC',
        'date_asc'    => 'a.date_applied ASC',
        'rating_desc' => 'a.rating DESC, a.date_applied DESC',
    ];
    $sort_sql   = $sort_map[$_GET['sort'] ?? ''] ?? 'a.date_applied DESC';
    $group_order = "CASE a.status WHEN 'Interviewing' THEN 1 WHEN 'Offer' THEN 2 WHEN 'Applied' THEN 3 WHEN 'Accepted' THEN 4 WHEN 'Rejected' THEN 5 WHEN 'Ghosted' THEN 6 WHEN 'Not Selected' THEN 7 WHEN 'No Answer' THEN 8 WHEN 'Withdrawn' THEN 9 ELSE 10 END";

    $sql  = "SELECT a.id, a.date_applied, a.company, a.via_recruiting_firm, a.recruiting_firm,
                    a.job_title, a.location_type, a.hybrid_location, a.days_onsite,
                    a.source, a.applied_through, a.resume_version, a.rating, a.status,
                    a.salary_requested, a.salary_listed, a.salary_type,
                    a.job_link, a.dashboard_link, a.job_id, a.timeline_id"
         . ($is_auth ? ", a.contacts" : "")
         . " FROM applications a WHERE " . implode(' AND ', $where)
         . " ORDER BY $group_order, $sort_sql";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    ok($stmt->fetchAll());
}

// ── Application — single ──────────────────────────────────────────────────────
if ($action === 'application') {
    $id      = (int)($_GET['id'] ?? 0);
    $pdo     = db();
    $is_auth = is_authenticated();

    $stmt = $pdo->prepare("SELECT * FROM applications WHERE id=?");
    $stmt->execute([$id]);
    $app = $stmt->fetch();
    if (!$app) fail('Not found', 404);

    if (!$is_auth) unset($app['contacts'], $app['notes'], $app['job_description']);

    $timeline = null;
    $rounds   = [];
    if ($app['timeline_id']) {
        $ts = $pdo->prepare("SELECT * FROM timeline_entries WHERE id=?");
        $ts->execute([$app['timeline_id']]);
        $timeline = $ts->fetch() ?: null;
        if ($timeline) {
            if (!$is_auth) unset($timeline['recruiter_name'], $timeline['screener_name']);
            $rs = $pdo->prepare("SELECT * FROM interview_rounds WHERE timeline_id=? ORDER BY round_order ASC");
            $rs->execute([$app['timeline_id']]);
            $rounds = $rs->fetchAll();
            if (!$is_auth) { foreach ($rounds as &$r) unset($r['interviewer'], $r['notes']); }
        }
    }
    ok(['application' => $app, 'timeline' => $timeline, 'rounds' => $rounds]);
}

// ── Application — add ─────────────────────────────────────────────────────────
if ($action === 'application_add') {
    auth_required();
    $b = body(); $pdo = db();
    $stmt = $pdo->prepare("INSERT INTO applications
        (date_applied,company,via_recruiting_firm,recruiting_firm,job_title,
         location_type,hybrid_location,days_onsite,source,applied_through,
         resume_version,rating,status,job_id,job_link,dashboard_link,
         salary_requested,salary_listed,salary_type,contacts,notes,job_description)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([
        date_or_null($b,'date_applied') ?? date('Y-m-d'),
        str_or_null($b,'company'), (int)($b['via_recruiting_firm']??0), str_or_null($b,'recruiting_firm'),
        trim($b['job_title']??''), $b['location_type']??'Remote', str_or_null($b,'hybrid_location'),
        str_or_null($b,'days_onsite'), str_or_null($b,'source'), str_or_null($b,'applied_through'),
        str_or_null($b,'resume_version'), int_or_null($b,'rating'), $b['status']??'Applied',
        str_or_null($b,'job_id'), str_or_null($b,'job_link'), str_or_null($b,'dashboard_link'),
        str_or_null($b,'salary_requested'), str_or_null($b,'salary_listed'), $b['salary_type']??'Yearly',
        str_or_null($b,'contacts'), str_or_null($b,'notes'), str_or_null($b,'job_description'),
    ]);
    $new_id = (int)$pdo->lastInsertId();
    if (($b['status']??'') === 'Interviewing') _auto_create_timeline($pdo, $new_id, $b);
    ok(['id' => $new_id]);
}

// ── Application — update ──────────────────────────────────────────────────────
if ($action === 'application_update') {
    auth_required();
    $id = (int)($_GET['id']??0); $b = body(); $pdo = db();
    $cur = $pdo->prepare("SELECT status,timeline_id FROM applications WHERE id=?");
    $cur->execute([$id]); $current = $cur->fetch();
    if (!$current) fail('Not found', 404);
    $stmt = $pdo->prepare("UPDATE applications SET
        date_applied=?,company=?,via_recruiting_firm=?,recruiting_firm=?,job_title=?,
        location_type=?,hybrid_location=?,days_onsite=?,source=?,applied_through=?,
        resume_version=?,rating=?,status=?,job_id=?,job_link=?,dashboard_link=?,
        salary_requested=?,salary_listed=?,salary_type=?,contacts=?,notes=?,job_description=?
        WHERE id=?");
    $stmt->execute([
        date_or_null($b,'date_applied') ?? date('Y-m-d'),
        str_or_null($b,'company'), (int)($b['via_recruiting_firm']??0), str_or_null($b,'recruiting_firm'),
        trim($b['job_title']??''), $b['location_type']??'Remote', str_or_null($b,'hybrid_location'),
        str_or_null($b,'days_onsite'), str_or_null($b,'source'), str_or_null($b,'applied_through'),
        str_or_null($b,'resume_version'), int_or_null($b,'rating'), $b['status']??'Applied',
        str_or_null($b,'job_id'), str_or_null($b,'job_link'), str_or_null($b,'dashboard_link'),
        str_or_null($b,'salary_requested'), str_or_null($b,'salary_listed'), $b['salary_type']??'Yearly',
        str_or_null($b,'contacts'), str_or_null($b,'notes'), str_or_null($b,'job_description'),
        $id,
    ]);
    if (($b['status']??'') === 'Interviewing' && !$current['timeline_id'])
        _auto_create_timeline($pdo, $id, $b);
    ok();
}

// ── Application — delete ──────────────────────────────────────────────────────
if ($action === 'application_delete') {
    auth_required();
    $id = (int)($_GET['id']??0); $pdo = db();
    $row = $pdo->prepare("SELECT timeline_id FROM applications WHERE id=?");
    $row->execute([$id]); $app = $row->fetch();
    if (!$app) fail('Not found', 404);
    if ($app['timeline_id']) {
        $pdo->prepare("DELETE FROM interview_rounds WHERE timeline_id=?")->execute([$app['timeline_id']]);
        $pdo->prepare("DELETE FROM timeline_entries WHERE id=?")->execute([$app['timeline_id']]);
    }
    $pdo->prepare("DELETE FROM applications WHERE id=?")->execute([$id]);
    ok();
}

// ── Timeline — list ───────────────────────────────────────────────────────────
if ($action === 'timeline') {
    $pdo = db(); $is_auth = is_authenticated();
    $entries = $pdo->query("
        SELECT t.*,
               a.company, a.job_title AS position, a.rating, a.date_applied,
               a.via_recruiting_firm, a.recruiting_firm
        FROM timeline_entries t
        JOIN applications a ON t.application_id = a.id
        ORDER BY a.date_applied ASC
    ")->fetchAll();
    foreach ($entries as &$entry) {
        $rs = $pdo->prepare("SELECT * FROM interview_rounds WHERE timeline_id=? ORDER BY round_order ASC");
        $rs->execute([$entry['id']]);
        $rounds = $rs->fetchAll();
        if (!$is_auth) {
            foreach ($rounds as &$r) unset($r['interviewer'], $r['notes']);
            unset($entry['recruiter_name'], $entry['screener_name']);
        }
        $entry['rounds']      = array_values(array_filter(array_column($rounds,'interview_date')));
        $entry['rounds_full'] = $rounds;
    }
    ok($entries);
}

// ── Timeline — single ─────────────────────────────────────────────────────────
if ($action === 'timeline_entry') {
    $id = (int)($_GET['id']??0); $pdo = db(); $is_auth = is_authenticated();
    $stmt = $pdo->prepare("
        SELECT t.*,
               a.company, a.job_title AS position, a.rating, a.date_applied,
               a.via_recruiting_firm, a.recruiting_firm
        FROM timeline_entries t
        JOIN applications a ON t.application_id = a.id
        WHERE t.id=?
    ");
    $stmt->execute([$id]); $entry = $stmt->fetch();
    if (!$entry) fail('Not found', 404);
    $rs = $pdo->prepare("SELECT * FROM interview_rounds WHERE timeline_id=? ORDER BY round_order ASC");
    $rs->execute([$id]); $rounds = $rs->fetchAll();
    if (!$is_auth) {
        foreach ($rounds as &$r) unset($r['interviewer'], $r['notes']);
        unset($entry['recruiter_name'], $entry['screener_name']);
    }
    $entry['rounds'] = $rounds;
    ok($entry);
}

// ── Timeline — add ────────────────────────────────────────────────────────────
if ($action === 'timeline_add') {
    auth_required(); $b = body(); $pdo = db();
    $stmt = $pdo->prepare("INSERT INTO timeline_entries
        (date_recruiter,recruiter_name,date_screening,screener_name,
         screening_type,pending,date_rejected,application_id)
        VALUES (?,?,?,?,?,?,?,?)");
    $stmt->execute([
        date_or_null($b,'date_recruiter'), str_or_null($b,'recruiter_name'),
        date_or_null($b,'date_screening'), str_or_null($b,'screener_name'), str_or_null($b,'screening_type'),
        (int)($b['pending']??1), date_or_null($b,'date_rejected'), int_or_null($b,'application_id'),
    ]);
    ok(['id' => (int)$pdo->lastInsertId()]);
}

// ── Timeline — update ─────────────────────────────────────────────────────────
if ($action === 'timeline_update') {
    auth_required(); $id = (int)($_GET['id']??0); $b = body(); $pdo = db();
    $stmt = $pdo->prepare("UPDATE timeline_entries SET
        date_recruiter=?,recruiter_name=?,date_screening=?,screener_name=?,
        screening_type=?,pending=?,date_rejected=?
        WHERE id=?");
    $stmt->execute([
        date_or_null($b,'date_recruiter'), str_or_null($b,'recruiter_name'),
        date_or_null($b,'date_screening'), str_or_null($b,'screener_name'), str_or_null($b,'screening_type'),
        (int)($b['pending']??1), date_or_null($b,'date_rejected'), $id,
    ]);
    ok();
}

// ── Timeline — delete ─────────────────────────────────────────────────────────
if ($action === 'timeline_delete') {
    auth_required(); $id = (int)($_GET['id']??0); $pdo = db();
    $pdo->prepare("DELETE FROM interview_rounds WHERE timeline_id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM timeline_entries WHERE id=?")->execute([$id]);
    $pdo->prepare("UPDATE applications SET timeline_id=NULL WHERE timeline_id=?")->execute([$id]);
    ok();
}

// ── Rounds — list ─────────────────────────────────────────────────────────────
if ($action === 'rounds') {
    $tid = (int)($_GET['timeline_id']??0); $pdo = db(); $is_auth = is_authenticated();
    $stmt = $pdo->prepare("SELECT * FROM interview_rounds WHERE timeline_id=? ORDER BY round_order ASC");
    $stmt->execute([$tid]); $rounds = $stmt->fetchAll();
    if (!$is_auth) { foreach ($rounds as &$r) unset($r['interviewer'], $r['notes']); }
    ok($rounds);
}

// ── Round — save (upsert) ─────────────────────────────────────────────────────
if ($action === 'round_save') {
    auth_required(); $b = body(); $pdo = db();
    $is_final = isset($b['is_final_round']) ? (int)(bool)$b['is_final_round'] : 0;
    // If marking as final, unset all other rounds in this timeline as final first
    if ($is_final) {
        $pdo->prepare("UPDATE interview_rounds SET is_final_round=0 WHERE timeline_id=?")
            ->execute([(int)($b['timeline_id']??0)]);
    }
    $stmt = $pdo->prepare("INSERT INTO interview_rounds
        (timeline_id,round_order,interview_date,interview_type,interviewer,notes,is_final_round)
        VALUES (?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
            interview_date=VALUES(interview_date),interview_type=VALUES(interview_type),
            interviewer=VALUES(interviewer),notes=VALUES(notes),is_final_round=VALUES(is_final_round)");
    $stmt->execute([
        (int)($b['timeline_id']??0), (int)($b['round_order']??0),
        date_or_null($b,'interview_date'), str_or_null($b,'interview_type'),
        str_or_null($b,'interviewer'), str_or_null($b,'notes'),
        $is_final,
    ]);
    ok(['id' => (int)$pdo->lastInsertId()]);
}

// ── Round — delete ────────────────────────────────────────────────────────────
if ($action === 'round_delete') {
    auth_required(); $id = (int)($_GET['id']??0); $pdo = db();
    $pdo->prepare("DELETE FROM interview_rounds WHERE id=?")->execute([$id]);
    ok();
}

// ── One-time data migrations (admin only) ─────────────────────────────────────
if ($action === 'run_migration') {
    auth_required();
    $pdo = db();
    $results = [];

    // 1. Move Workday job_link → dashboard_link where dashboard_link is empty
    $stmt = $pdo->query("UPDATE applications
        SET dashboard_link = job_link, job_link = NULL
        WHERE applied_through = 'Workday'
        AND (dashboard_link IS NULL OR dashboard_link = '')
        AND job_link IS NOT NULL AND job_link != ''");
    $results['workday_links_moved'] = $stmt->rowCount();

    // 2. Consolidate LinkedIn → LinkedIn Easy Apply
    $stmt = $pdo->query("UPDATE applications
        SET applied_through = 'LinkedIn Easy Apply'
        WHERE applied_through = 'LinkedIn'");
    $results['linkedin_consolidated'] = $stmt->rowCount();

    ok($results);
}

// ── Auto-create timeline helper ───────────────────────────────────────────────
function _auto_create_timeline(PDO $pdo, int $app_id, array $b): void {
    // Use client-supplied date (browser local date), fall back to UTC
    $today = isset($b['today']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $b['today']) ? $b['today'] : gmdate('Y-m-d');
    $stmt  = $pdo->prepare("INSERT INTO timeline_entries (pending, date_recruiter, application_id) VALUES (1, ?, ?)");
    $stmt->execute([$today, $app_id]);
    $tid = (int)$pdo->lastInsertId();
    $pdo->prepare("UPDATE applications SET timeline_id=? WHERE id=?")->execute([$tid, $app_id]);
}

// ── Application — quick status update ────────────────────────────────────────
if ($action === 'application_status') {
    auth_required();
    $id = (int)($_GET['id']??0); $b = body(); $pdo = db();
    $status = $b['status'] ?? null;
    if (!$status) fail('Missing status');
    $stmt = $pdo->prepare("UPDATE applications SET status=? WHERE id=?");
    $stmt->execute([$status, $id]);
    // Sync timeline entry if one exists
    $cur = $pdo->prepare("SELECT timeline_id, company, job_title, date_applied, rating, via_recruiting_firm, recruiting_firm FROM applications WHERE id=?");
    $cur->execute([$id]); $app = $cur->fetch();
    if ($app && $app['timeline_id']) {
        $tid   = $app['timeline_id'];
        $today = date('Y-m-d');
        if ($status === 'Ghosted') {
            // Ghosted: pending=0, NO rejection date — timeline renders dotted line
            $pdo->prepare("UPDATE timeline_entries SET pending=0, date_rejected=NULL WHERE id=?")
                ->execute([$tid]);
        } elseif (in_array($status, ['Rejected','Not Selected','No Answer','Withdrawn','Accepted'])) {
            // Closed with a definitive end: set today as rejection date so timeline
            // renders the red dot rather than the ghosted dotted line
            $pdo->prepare("UPDATE timeline_entries SET pending=0, date_rejected=? WHERE id=?")
                ->execute([$today, $tid]);
        } elseif (in_array($status, ['Interviewing','Offer','Applied'])) {
            // Reopening: pending=1, clear rejection date
            $pdo->prepare("UPDATE timeline_entries SET pending=1, date_rejected=NULL WHERE id=?")
                ->execute([$tid]);
        }
    } elseif ($app && !$app['timeline_id'] && $status === 'Interviewing') {
        _auto_create_timeline($pdo, $id, $app);
    }
    ok();
}

// ── Fallback ──────────────────────────────────────────────────────────────────
fail("Unknown action: $action", 404);
