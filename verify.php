<?php
/**
 * verify.php — Ottawa Tamil Sangam · Email Verification
 *
 * This page has no UI of its own. It processes the verification token and
 * immediately redirects the visitor back to a normal, fully-styled page
 * (the home page for guests, the dashboard for logged-in users) with a
 * banner explaining the result.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/mail.php';

ots_session();
$currentUser = getCurrentUser();

$token = trim($_GET['token'] ?? '');

// Build the redirect target from this script's own directory so it works no
// matter where the site is installed and even if the link has extra path parts.
$dir  = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$page = $currentUser ? 'dashboard.php' : 'index.php';
$home = ($dir === '' ? '' : $dir) . '/' . $page;

if ($token === '') {
    header('Location: ' . $home . '?verify_error=none');
    exit;
}

$result = authVerifyEmail($token);

if ($result['success']) {
    // Guests land on the home page with the login modal open; logged-in users
    // go to their dashboard. Either way they see a green success banner.
    header('Location: ' . $home . '?verified=1');
    exit;
}

$reason = ($result['error'] ?? '') === 'expired' ? 'expired' : 'invalid';
header('Location: ' . $home . '?verify_error=' . $reason);
exit;
