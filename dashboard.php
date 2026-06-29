<?php
/**
 * dashboard.php — Redirects to the appropriate panel based on role.
 */
require_once __DIR__ . '/auth.php';
$user = requireLogin();

switch ($user['role']) {
    case 'admin':
        header('Location: admin_panel.php');
        break;
    case 'coordinator':
        header('Location: coordinator_panel.php');
        break;
    default:
        header('Location: member_panel.php');
        break;
}
exit;
