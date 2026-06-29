<?php
/**
 * mail.php — Ottawa Tamil Sangam · Mail Utility
 * Routes email via Gmail SMTP, Resend API, PHP mail(), or local log file.
 */

require_once __DIR__ . '/config.php';

/**
 * Send an email.
 * @param string $to      Recipient email address
 * @param string $name    Recipient display name
 * @param string $subject Email subject
 * @param string $html    HTML body content
 * @return bool           True on success
 */
function sendMail(string $to, string $name, string $subject, string $html): bool {
    $provider = MAIL_PROVIDER;

    switch ($provider) {
        case 'gmail':
            return _mailGmail($to, $name, $subject, $html);
        case 'resend':
            return _mailResend($to, $name, $subject, $html);
        case 'php':
            return _mailPhp($to, $name, $subject, $html);
        case 'log':
        default:
            return _mailLog($to, $name, $subject, $html);
    }
}

/**
 * Send via Gmail SMTP using an App Password.
 * Requires: GMAIL_USER set to your Gmail address,
 *           GMAIL_APP_PASSWORD set to a 16-char Google App Password.
 * The Gmail account must have 2-Step Verification enabled.
 */
function _mailGmail(string $to, string $name, string $subject, string $html): bool {
    $user = GMAIL_USER;
    $pass = str_replace(' ', '', GMAIL_APP_PASSWORD); // strip spaces Google adds for readability

    if (!$user || !$pass) {
        return _mailLog($to, $name, $subject, $html);
    }

    // Connect to Gmail SMTP over SSL (port 465)
    $socket = @fsockopen('ssl://smtp.gmail.com', 465, $errno, $errstr, 30);
    if (!$socket) {
        error_log("Gmail SMTP connect failed: $errstr ($errno)");
        return _mailLog($to, $name, $subject, $html);
    }
    stream_set_timeout($socket, 30);

    // Helpers scoped to this call
    $read = function() use ($socket): string {
        $out = '';
        while (!feof($socket)) {
            $line = fgets($socket, 512);
            if ($line === false) break;
            $out .= $line;
            if (strlen($line) >= 4 && $line[3] === ' ') break; // end of multi-line reply
        }
        return $out;
    };
    $cmd = function(string $c) use ($socket, $read): string {
        fwrite($socket, $c . "\r\n");
        return $read();
    };

    $read();                                   // 220 smtp.gmail.com greeting
    $cmd('EHLO localhost');                    // 250-capabilities
    $cmd('AUTH LOGIN');                        // 334 username prompt
    $cmd(base64_encode($user));                // 334 password prompt
    $auth = $cmd(base64_encode($pass));        // 235 OK  or  535 auth failed

    if (!str_starts_with(trim($auth), '235')) {
        fclose($socket);
        error_log('Gmail SMTP auth failed — check GMAIL_APP_PASSWORD');
        return _mailLog($to, $name, $subject, $html);
    }

    $toHeader   = $name ? "{$name} <{$to}>" : $to;
    $fromHeader = MAIL_FROM_NAME . ' <' . $user . '>';
    // Encode subject as UTF-8 Q-encoding so non-ASCII chars survive transit
    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

    $cmd("MAIL FROM:<{$user}>");
    $cmd("RCPT TO:<{$to}>");
    $cmd('DATA');

    // Base64-encode body to handle all character sets cleanly
    $body  = chunk_split(base64_encode($html));
    $msgId = '<' . time() . '.' . bin2hex(random_bytes(6)) . '@ottawatamilsangam.ca>';

    $message = implode("\r\n", [
        "Message-ID: {$msgId}",
        "From: {$fromHeader}",
        "To: {$toHeader}",
        "Subject: {$encodedSubject}",
        "MIME-Version: 1.0",
        "Content-Type: text/html; charset=UTF-8",
        "Content-Transfer-Encoding: base64",
        "",
        $body,
    ]);

    fwrite($socket, $message . "\r\n.\r\n");
    $read();           // 250 queued
    $cmd('QUIT');
    fclose($socket);
    return true;
}

/**
 * Send via Resend API.
 */
function _mailResend(string $to, string $name, string $subject, string $html): bool {
    $apiKey = RESEND_API_KEY;
    if (!$apiKey) return _mailLog($to, $name, $subject, $html);

    $payload = json_encode([
        'from'    => MAIL_FROM_NAME . ' <' . MAIL_FROM_EMAIL . '>',
        'to'      => [$name . ' <' . $to . '>'],
        'subject' => $subject,
        'html'    => $html,
    ]);

    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT        => 15,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $code >= 200 && $code < 300;
}

/**
 * Send via PHP's built-in mail().
 */
function _mailPhp(string $to, string $name, string $subject, string $html): bool {
    $fromName  = MAIL_FROM_NAME;
    $fromEmail = MAIL_FROM_EMAIL;
    $headers   = implode("\r\n", [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $fromName . ' <' . $fromEmail . '>',
        'Reply-To: ' . $fromEmail,
        'X-Mailer: PHP/' . PHP_VERSION,
    ]);
    $toHeader = $name ? ($name . ' <' . $to . '>') : $to;
    return mail($toHeader, $subject, $html, $headers);
}

/**
 * Log email to a file (for development / testing).
 */
function _mailLog(string $to, string $name, string $subject, string $html): bool {
    $logDir = __DIR__ . '/database/mail_log';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    $filename = $logDir . '/' . date('Ymd_His') . '_' . substr(md5($to), 0, 8) . '.html';
    $meta = "<!-- To: {$name} <{$to}>\n     Subject: {$subject}\n     Sent: " . date('c') . " -->\n";
    file_put_contents($filename, $meta . $html);
    return true;
}

/**
 * Send a contact-form email with Reply-To set to the visitor's address.
 */
function _sendContactMail(string $to, string $subject, string $html, string $replyTo, string $replyName): bool {
    $provider = MAIL_PROVIDER;
    switch ($provider) {
        case 'gmail':
            return _contactMailGmail($to, $subject, $html, $replyTo, $replyName);
        case 'resend':
            return _contactMailResend($to, $subject, $html, $replyTo, $replyName);
        case 'php':
            return _contactMailPhp($to, $subject, $html, $replyTo, $replyName);
        default:
            return _mailLog($to, $replyName, $subject, $html);
    }
}

function _contactMailGmail(string $to, string $subject, string $html, string $replyTo, string $replyName): bool {
    $user = GMAIL_USER;
    $pass = str_replace(' ', '', GMAIL_APP_PASSWORD);
    if (!$user || !$pass) return _mailLog($to, $replyName, $subject, $html);

    $socket = @fsockopen('ssl://smtp.gmail.com', 465, $errno, $errstr, 30);
    if (!$socket) return _mailLog($to, $replyName, $subject, $html);
    stream_set_timeout($socket, 30);

    $read = function() use ($socket): string {
        $out = '';
        while (!feof($socket)) {
            $line = fgets($socket, 512);
            if ($line === false) break;
            $out .= $line;
            if (strlen($line) >= 4 && $line[3] === ' ') break;
        }
        return $out;
    };
    $cmd = function(string $c) use ($socket, $read): string {
        fwrite($socket, $c . "\r\n");
        return $read();
    };

    $read();
    $cmd('EHLO localhost');
    $cmd('AUTH LOGIN');
    $cmd(base64_encode($user));
    $auth = $cmd(base64_encode($pass));
    if (!str_starts_with(trim($auth), '235')) { fclose($socket); return _mailLog($to, $replyName, $subject, $html); }

    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $replyHeader    = $replyName ? "{$replyName} <{$replyTo}>" : $replyTo;
    $cmd("MAIL FROM:<{$user}>");
    $cmd("RCPT TO:<{$to}>");
    $cmd('DATA');

    $body  = chunk_split(base64_encode($html));
    $msgId = '<' . time() . '.' . bin2hex(random_bytes(6)) . '@ottawatamilsangam.ca>';
    $message = implode("\r\n", [
        "Message-ID: {$msgId}",
        "From: " . MAIL_FROM_NAME . " <{$user}>",
        "To: {$to}",
        "Reply-To: {$replyHeader}",
        "Subject: {$encodedSubject}",
        "MIME-Version: 1.0",
        "Content-Type: text/html; charset=UTF-8",
        "Content-Transfer-Encoding: base64",
        "",
        $body,
    ]);
    fwrite($socket, $message . "\r\n.\r\n");
    $read();
    $cmd('QUIT');
    fclose($socket);
    return true;
}

function _contactMailResend(string $to, string $subject, string $html, string $replyTo, string $replyName): bool {
    $apiKey = RESEND_API_KEY;
    if (!$apiKey) return _mailLog($to, $replyName, $subject, $html);
    $payload = json_encode([
        'from'     => MAIL_FROM_NAME . ' <' . MAIL_FROM_EMAIL . '>',
        'to'       => [$to],
        'reply_to' => [$replyName ? "{$replyName} <{$replyTo}>" : $replyTo],
        'subject'  => $subject,
        'html'     => $html,
    ]);
    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_POST=>true, CURLOPT_POSTFIELDS=>$payload,
        CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$apiKey,'Content-Type: application/json'], CURLOPT_TIMEOUT=>15]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $code >= 200 && $code < 300;
}

function _contactMailPhp(string $to, string $subject, string $html, string $replyTo, string $replyName): bool {
    $replyHeader = $replyName ? ($replyName . ' <' . $replyTo . '>') : $replyTo;
    $headers = implode("\r\n", [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM_EMAIL . '>',
        'Reply-To: ' . $replyHeader,
        'X-Mailer: PHP/' . PHP_VERSION,
    ]);
    return mail($to, $subject, $html, $headers);
}

/**
 * Generate a standard HTML email body.
 */
function mailBody(string $heading, string $bodyHtml): string {
    $siteName = SITE_NAME;
    $siteUrl  = SITE_URL;
    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>{$heading}</title>
  <style>
    body { margin:0; padding:0; background:#f5f5f0; font-family:Inter,Arial,sans-serif; }
    .wrap { max-width:600px; margin:40px auto; border-radius:12px; overflow:hidden; box-shadow:0 4px 24px rgba(107,15,26,.12); }
    .hdr  { background:linear-gradient(135deg,#6b0f1a 0%,#8b1f2a 100%); padding:32px 40px; text-align:center; }
    .hdr h1 { color:#d4a73a; font-family:Georgia,serif; font-size:1.5rem; margin:0 0 4px; }
    .hdr p  { color:rgba(255,255,255,.7); font-size:.85rem; margin:0; }
    .body { background:#ffffff; padding:40px; color:#333; line-height:1.7; }
    .body h2 { color:#6b0f1a; font-family:Georgia,serif; margin-top:0; }
    .body p  { color:#4a4a4a; }
    .btn  { display:inline-block; background:#6b0f1a; color:#ffffff!important; text-decoration:none;
            padding:14px 32px; border-radius:8px; font-weight:700; margin:20px 0; font-size:.95rem; }
    .btn:hover { background:#8b1f2a; }
    .footer { background:#faf8f3; padding:20px 40px; text-align:center; font-size:.8rem; color:#6b7280;
              border-top:1px solid #e5e7eb; }
    .footer a { color:#6b0f1a; text-decoration:none; }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="hdr">
      <h1>{$siteName}</h1>
      <p>Ottawa Tamil Community</p>
    </div>
    <div class="body">
      {$bodyHtml}
    </div>
    <div class="footer">
      <p>You received this email from <a href="{$siteUrl}">{$siteName}</a>.</p>
      <p>If you did not request this, you can safely ignore it.</p>
    </div>
  </div>
</body>
</html>
HTML;
}

/**
 * Send an email verification link.
 */
function mailVerification(string $to, string $name, string $token): bool {
    $link    = SITE_URL . '/verify.php?token=' . urlencode($token);
    $subject = 'Verify your email — ' . SITE_NAME;
    $body    = mailBody('Email Verification', <<<HTML
      <h2>Verify Your Email Address</h2>
      <p>Hi {$name},</p>
      <p>Thank you for registering with Ottawa Tamil Sangam! Please click the button below to verify your email address and activate your account.</p>
      <p style="text-align:center">
        <a href="{$link}" class="btn">Verify Email Address</a>
      </p>
      <p style="color:#6b7280;font-size:.85rem">This link expires in 24 hours. If you did not create an account, please ignore this email.</p>
      <p style="word-break:break-all;font-size:.8rem;color:#9ca3af">Or paste this link: {$link}</p>
HTML);
    return sendMail($to, $name, $subject, $body);
}

/**
 * Send a password reset link.
 */
function mailPasswordReset(string $to, string $name, string $token): bool {
    $link    = SITE_URL . '/reset.php?token=' . urlencode($token);
    $subject = 'Reset your password — ' . SITE_NAME;
    $body    = mailBody('Password Reset', <<<HTML
      <h2>Reset Your Password</h2>
      <p>Hi {$name},</p>
      <p>We received a request to reset the password for your Ottawa Tamil Sangam account. Click the button below to choose a new password.</p>
      <p style="text-align:center">
        <a href="{$link}" class="btn">Reset Password</a>
      </p>
      <p style="color:#6b7280;font-size:.85rem">This link expires in 1 hour. If you did not request a password reset, no action is needed.</p>
      <p style="word-break:break-all;font-size:.8rem;color:#9ca3af">Or paste this link: {$link}</p>
HTML);
    return sendMail($to, $name, $subject, $body);
}
