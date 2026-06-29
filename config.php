<?php
/**
 * config.php — Ottawa Tamil Sangam · Site Configuration
 *
 * SETUP INSTRUCTIONS:
 * 1. Cloudinary (free photo hosting, 25GB): cloudinary.com
 *    Dashboard → API Keys → copy Cloud Name, API Key, API Secret
 * 2. Gmail SMTP (recommended — no signup needed):
 *    a. Enable 2-Step Verification on your Google account
 *    b. Google Account → Security → App Passwords → create one for "Mail"
 *    c. Set GMAIL_USER to your Gmail address and GMAIL_APP_PASSWORD to the 16-char app password
 *    d. Set MAIL_PROVIDER to 'gmail'
 * 3. Set SITE_URL to your actual domain (no trailing slash)
 */

// Site
// Base site URL — NO trailing slash and NO page path (mail.php appends /verify.php etc.)
define('SITE_URL',  getenv('SITE_URL')  ?: 'http://localhost:8000');
define('SITE_NAME', 'Ottawa Tamil Sangam');

// Cloudinary — leave empty to use local file storage instead
define('CLOUDINARY_CLOUD_NAME', 'detize8d2');
define('CLOUDINARY_API_KEY','986285964692299');
define('CLOUDINARY_API_SECRET', 'ZDhBo3z2Cr0WkqUdzVnMe25aV-k');
define('CLOUDINARY_FOLDER',  'ots-events');

// Email — MAIL_PROVIDER: 'gmail' | 'resend' | 'php' | 'log'
// 'log' writes emails to database/mail_log/ (dev mode, no sending)
define('MAIL_PROVIDER',      getenv('MAIL_PROVIDER')      ?: 'gmail');
define('GMAIL_USER',         getenv('GMAIL_USER')         ?: 'vishvag10@gmail.com');
define('GMAIL_APP_PASSWORD', 'psqa ktub umgw sfmv');
define('RESEND_API_KEY',     getenv('RESEND_API_KEY')     ?: '');
define('MAIL_FROM_EMAIL',    GMAIL_USER);   // must match GMAIL_USER when using gmail provider
define('MAIL_FROM_NAME',     SITE_NAME);

// Set to true once email is configured
define('EMAIL_VERIFY_REQUIRED', true);
define('EMAIL_VERIFY_EXPIRES',   86400); // 24h
define('PASSWORD_RESET_EXPIRES', 3600);  // 1h

// Security
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_MINUTES',    15);

// Zeffy integration — set ZEFFY_API_KEY via env var or paste it in here.
// Get your key: Zeffy Dashboard → Settings → Organization → Integrations
// API docs:      https://www.zeffy.com/api/docs  (must be logged in)
define('ZEFFY_API_KEY',              getenv('ZEFFY_API_KEY') ?: '');
define('ZEFFY_MEMBERSHIP_FORM_SLUG', 'ottawa-tamil-sangams-annual-membership');