<?php
/**
 * auth.php — Session token helpers shared by api.php and admin.php
 */

define('TOKEN_FILE', PRIVATE_PATH . 'session_tokens.json');

function _load_tokens(): array {
    if (!file_exists(TOKEN_FILE)) return [];
    $data = json_decode(file_get_contents(TOKEN_FILE), true);
    $now  = time();
    return array_values(array_filter($data ?? [], fn($t) => $t['expires'] > $now));
}

function _current_token_data(): ?array {
    $cookie = $_COOKIE[SESSION_COOKIE] ?? '';
    if (!$cookie) return null;
    foreach (_load_tokens() as $t) {
        if (hash_equals($t['token'], $cookie) && $t['expires'] > time()) return $t;
    }
    return null;
}

function is_authenticated(): bool {
    return _current_token_data() !== null;
}

function current_user_id(): ?int {
    $t = _current_token_data();
    return isset($t['user_id']) ? (int)$t['user_id'] : null;
}

function is_admin(): bool {
    $uid = current_user_id();
    if (!$uid) return false;
    $stmt = db()->prepare('SELECT is_admin FROM users WHERE id = ?');
    $stmt->execute([$uid]);
    $row = $stmt->fetch();
    return $row && (int)$row['is_admin'] === 1;
}
