<?php
/**
 * auth.php — Ottawa Tamil Sangam · Auth Layer
 * Session-based authentication with role enforcement.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';

const ROLES = ['non_member', 'member', 'coordinator', 'admin'];

function ots_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name('OTS_SESSION');
        session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
        session_start();
    }
}

/** Returns current user array or null */
function getCurrentUser(): ?array {
    ots_session();
    if (empty($_SESSION['user_id'])) return null;
    try {
        $db = getDB();
        $stmt = $db->prepare(
            "SELECT id, email, first_name, last_name, role, extra_roles, membership_number, membership_expiry, membership_status, phone, email_verified
             FROM users WHERE id = ? LIMIT 1"
        );
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch() ?: null;
    } catch (Exception) {
        return null;
    }
}

/**
 * Returns all roles for a user as an array (primary role + any extra_roles).
 * Extra roles are stored as a JSON array in the extra_roles column.
 */
function getUserRoles(array $u): array {
    $primary = $u['role'] ?? 'non_member';
    $extra   = json_decode($u['extra_roles'] ?? '[]', true);
    $extra   = is_array($extra) ? $extra : [];
    return array_values(array_unique(array_filter(array_merge([$primary], $extra))));
}

/** Check whether a user has at least one of the given role(s) */
function userHasRole(array $u, array|string $roles): bool {
    return (bool) array_intersect(getUserRoles($u), (array)$roles);
}

/** Redirect to home with login prompt if not authenticated */
function requireLogin(): array {
    $user = getCurrentUser();
    if (!$user) {
        header('Location: index.php?need_login=1');
        exit;
    }
    return $user;
}

/** Require one of the given roles (checks all roles, including extra_roles) */
function requireRole(array|string $roles): array {
    $user = requireLogin();
    if (!userHasRole($user, $roles)) {
        http_response_code(403);
        die('<h1>403 — Access Denied</h1><p>You do not have permission to view this page.</p>');
    }
    return $user;
}

function isAdmin(?array $user = null): bool {
    $u = $user ?? getCurrentUser();
    return $u && userHasRole($u, 'admin');
}

function isCoordinator(?array $user = null): bool {
    $u = $user ?? getCurrentUser();
    return $u && userHasRole($u, ['coordinator', 'admin']);
}

/** Any specialist staff role (routes to coordinator panel) */
function isStaff(?array $user = null): bool {
    $u = $user ?? getCurrentUser();
    return $u && userHasRole($u, [
        'admin', 'coordinator',
        'social_media', 'membership_coordinator',
        'cultural_coordinator', 'sports_coordinator',
    ]);
}

function isSocialMedia(?array $user = null): bool {
    $u = $user ?? getCurrentUser();
    return $u && userHasRole($u, ['social_media', 'admin']);
}

function isMembershipCoordinator(?array $user = null): bool {
    $u = $user ?? getCurrentUser();
    return $u && userHasRole($u, ['membership_coordinator', 'admin']);
}

function isEventCoordinator(?array $user = null): bool {
    $u = $user ?? getCurrentUser();
    return $u && userHasRole($u, [
        'cultural_coordinator', 'sports_coordinator', 'coordinator', 'admin',
    ]);
}

/** Can upload/manage slideshow and event galleries */
function canManageMedia(?array $user = null): bool {
    $u = $user ?? getCurrentUser();
    return $u && userHasRole($u, [
        'admin', 'coordinator',
        'social_media', 'cultural_coordinator', 'sports_coordinator',
    ]);
}

/** Can edit site content text sections */
function canEditContent(?array $user = null): bool {
    $u = $user ?? getCurrentUser();
    return $u && userHasRole($u, ['admin', 'coordinator', 'social_media']);
}

function isMember(?array $user = null): bool {
    $user ??= getCurrentUser();
    return $user !== null;
}

/** True for any staff role, member role, or users with active paid membership */
function isActiveMember(?array $user = null): bool {
    $u = $user ?? getCurrentUser();
    if (!$u) return false;
    if (userHasRole($u, ['admin', 'coordinator', 'member',
        'social_media', 'membership_coordinator', 'cultural_coordinator', 'sports_coordinator'])) return true;
    return ($u['membership_status'] ?? 'none') === 'active';
}

/** Human-readable label for a single role slug */
function roleLabel(string $role): string {
    return [
        'admin'                  => 'Admin',
        'coordinator'            => 'Coordinator',
        'social_media'           => 'Social Media',
        'membership_coordinator' => 'Membership Coord.',
        'cultural_coordinator'   => 'Cultural Coord.',
        'sports_coordinator'     => 'Sports Coord.',
        'member'                 => 'Member',
        'non_member'             => 'Non-Member',
    ][$role] ?? ucwords(str_replace('_', ' ', $role));
}

/** Human-readable labels for all roles a user holds, joined by ' / ' */
function roleLabels(array $user): string {
    return implode(' / ', array_map('roleLabel', getUserRoles($user)));
}

// ─── Auth Actions ───────────────────────────────────────────────────────────

function authLogin(string $email, string $password): array {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([strtolower(trim($email))]);
    $user = $stmt->fetch();

    if (!$user) {
        return ['success' => false, 'error' => 'Invalid email or password.'];
    }

    // Check lockout
    if (!empty($user['locked_until'])) {
        $lockedUntil = strtotime($user['locked_until']);
        if ($lockedUntil > time()) {
            $remaining = ceil(($lockedUntil - time()) / 60);
            return ['success' => false, 'error' => "Account locked due to too many failed attempts. Try again in {$remaining} minute(s)."];
        }
    }

    if (!password_verify($password, $user['password'])) {
        // Increment failed attempts
        $attempts = (int)($user['login_attempts'] ?? 0) + 1;
        if ($attempts >= MAX_LOGIN_ATTEMPTS) {
            $lockedUntil = date('Y-m-d H:i:s', time() + LOCKOUT_MINUTES * 60);
            $db->prepare("UPDATE users SET login_attempts=?, locked_until=? WHERE id=?")
               ->execute([$attempts, $lockedUntil, $user['id']]);
            return ['success' => false, 'error' => 'Too many failed attempts. Account locked for ' . LOCKOUT_MINUTES . ' minutes.'];
        }
        $db->prepare("UPDATE users SET login_attempts=? WHERE id=?")
           ->execute([$attempts, $user['id']]);
        $remaining = MAX_LOGIN_ATTEMPTS - $attempts;
        return ['success' => false, 'error' => "Invalid email or password. {$remaining} attempt(s) remaining."];
    }

    // Check email verification requirement
    if (EMAIL_VERIFY_REQUIRED && empty($user['email_verified'])) {
        return [
            'success'    => false,
            'error'      => 'Please verify your email first. Check your inbox.',
            'unverified' => true,
        ];
    }

    // Success: reset lockout fields
    $db->prepare("UPDATE users SET login_attempts=0, locked_until=NULL WHERE id=?")
       ->execute([$user['id']]);

    ots_session();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];

    return [
        'success' => true,
        'user' => [
            'id'    => $user['id'],
            'name'  => $user['first_name'] . ' ' . $user['last_name'],
            'role'  => $user['role'],
            'email' => $user['email'],
        ],
        'redirect' => 'dashboard.php',
    ];
}

function authRegister(array $data): array {
    $required = ['email','password','first_name','last_name'];
    foreach ($required as $f) {
        if (empty($data[$f])) return ['success' => false, 'error' => "Field '$f' is required."];
    }

    $email = strtolower(trim($data['email']));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Invalid email address.'];
    }
    if (strlen($data['password']) < 8) {
        return ['success' => false, 'error' => 'Password must be at least 8 characters.'];
    }

    $db = getDB();
    $exists = $db->prepare("SELECT id FROM users WHERE email=?");
    $exists->execute([$email]);
    if ($exists->fetch()) return ['success' => false, 'error' => 'That email is already registered.'];

    $hash    = password_hash($data['password'], PASSWORD_DEFAULT);
    $memNum  = 'OTS-' . strtoupper(substr(md5($email . time()), 0, 8));

    // Generate verification token
    $verifyToken   = bin2hex(random_bytes(32));
    $verifyExpires = date('Y-m-d H:i:s', time() + EMAIL_VERIFY_EXPIRES);

    $ins = $db->prepare(
        "INSERT INTO users (email, password, first_name, last_name, role, membership_number, membership_status, phone,
                            email_verified, verification_token, verification_expires)
         VALUES (?,?,?,?,'non_member',?,'none',?,?,?,?)"
    );
    $ins->execute([
        $email,
        $hash,
        trim($data['first_name']),
        trim($data['last_name']),
        $memNum,
        trim($data['phone'] ?? ''),
        0,             // email_verified = false by default
        $verifyToken,
        $verifyExpires,
    ]);

    $userId = $db->lastInsertId();

    // Try to send verification email
    if (function_exists('mailVerification')) {
        @mailVerification($email, trim($data['first_name']), $verifyToken);
    }

    if (EMAIL_VERIFY_REQUIRED) {
        return [
            'success' => true,
            'verify'  => true,
            'message' => 'Account created! Check your email to verify your account before logging in.',
        ];
    }

    // Auto-login
    ots_session();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;

    return ['success' => true, 'redirect' => 'dashboard.php'];
}

function authLogout(): void {
    ots_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

// ─── Password Reset ──────────────────────────────────────────────────────────

function authForgotPassword(string $email): array {
    $db    = getDB();
    $email = strtolower(trim($email));
    $stmt  = $db->prepare("SELECT id, first_name FROM users WHERE email=? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Always return the same message for security (don't leak whether email exists)
    $generic = ['success' => true, 'message' => 'If that email is registered, we sent a password reset link.'];

    if (!$user) return $generic;

    $token   = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + PASSWORD_RESET_EXPIRES);

    $db->prepare("UPDATE users SET reset_token=?, reset_expires=? WHERE id=?")
       ->execute([$token, $expires, $user['id']]);

    if (function_exists('mailPasswordReset')) {
        @mailPasswordReset($email, $user['first_name'], $token);
    }

    return $generic;
}

function authResetPassword(string $token, string $password): array {
    if (strlen($password) < 8) {
        return ['success' => false, 'error' => 'Password must be at least 8 characters.'];
    }

    $db   = getDB();
    $stmt = $db->prepare("SELECT id FROM users WHERE reset_token=? AND reset_expires > CURRENT_TIMESTAMP LIMIT 1");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        return ['success' => false, 'error' => 'This reset link has expired or is invalid. Please request a new one.'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $db->prepare("UPDATE users SET password=?, reset_token=NULL, reset_expires=NULL, login_attempts=0, locked_until=NULL WHERE id=?")
       ->execute([$hash, $user['id']]);

    return ['success' => true, 'message' => 'Password updated successfully. You can now log in.'];
}

// ─── Email Verification ──────────────────────────────────────────────────────

function authVerifyEmail(string $token): array {
    $db   = getDB();
    $stmt = $db->prepare("SELECT id FROM users WHERE verification_token=? LIMIT 1");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        return ['success' => false, 'error' => 'invalid', 'message' => 'This verification link is invalid or has already been used.'];
    }

    // Check expiry
    $exp = $db->prepare("SELECT verification_expires FROM users WHERE id=?");
    $exp->execute([$user['id']]);
    $row = $exp->fetch();
    if ($row && $row['verification_expires'] && strtotime($row['verification_expires']) < time()) {
        return ['success' => false, 'error' => 'expired', 'message' => 'This verification link has expired. Please request a new one.'];
    }

    $db->prepare("UPDATE users SET email_verified=1, verification_token=NULL, verification_expires=NULL WHERE id=?")
       ->execute([$user['id']]);

    return ['success' => true, 'message' => 'Your email has been verified! You can now log in.'];
}

function authResendVerification(int $userId): array {
    $db   = getDB();
    $stmt = $db->prepare("SELECT email, first_name, email_verified FROM users WHERE id=? LIMIT 1");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        return ['success' => false, 'error' => 'User not found.'];
    }
    if ($user['email_verified']) {
        return ['success' => false, 'error' => 'Email is already verified.'];
    }

    $token   = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + EMAIL_VERIFY_EXPIRES);

    $db->prepare("UPDATE users SET verification_token=?, verification_expires=? WHERE id=?")
       ->execute([$token, $expires, $userId]);

    if (function_exists('mailVerification')) {
        @mailVerification($user['email'], $user['first_name'], $token);
    }

    return ['success' => true, 'message' => 'Verification email sent! Check your inbox.'];
}
