<?php
/**
 * api.php — Ottawa Tamil Sangam · REST API
 * All panel actions are routed through here via fetch().
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/mail.php';
require_once __DIR__ . '/zeffy.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$method = $_SERVER['REQUEST_METHOD'];

// Read JSON body if applicable
$body = [];
$rawInput = file_get_contents('php://input');
if ($rawInput && str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
    $body = json_decode($rawInput, true) ?? [];
}
$input = array_merge($_POST, $body);

// Action may come from the query string, a form field, or the JSON body.
$action = $_GET['action'] ?? $_POST['action'] ?? $body['action'] ?? '';

try {
    switch ($action) {

        // ── PUBLIC ──────────────────────────────────────────────────────────
        case 'login':
            requireMethod('POST');
            $loginResult = authLogin($input['email'] ?? '', $input['password'] ?? '');
            // Apply any Zeffy purchases that arrived before this account existed.
            if (!empty($loginResult['success']) && !empty($_SESSION['user_id'])) {
                try { zeffyReconcilePending(getDB(), (int)$_SESSION['user_id']); } catch (Exception) {}
            }
            echo json_encode($loginResult);
            break;

        case 'register':
            requireMethod('POST');
            $regResult = authRegister($input);
            // If the new account was auto-logged-in, reconcile pending Zeffy payments now.
            if (!empty($regResult['success']) && !empty($_SESSION['user_id'])) {
                try { zeffyReconcilePending(getDB(), (int)$_SESSION['user_id']); } catch (Exception) {}
            }
            echo json_encode($regResult);
            break;

        case 'logout':
            authLogout();
            echo json_encode(['success' => true, 'redirect' => 'index.php']);
            break;

        case 'forgot_password':
            requireMethod('POST');
            $email = trim($input['email'] ?? '');
            if (!$email) { echo json_encode(['success'=>false,'error'=>'Email is required.']); break; }
            echo json_encode(authForgotPassword($email));
            break;

        case 'reset_password':
            requireMethod('POST');
            $token    = trim($input['token']    ?? '');
            $password = trim($input['password'] ?? '');
            if (!$token || !$password) { echo json_encode(['success'=>false,'error'=>'Token and password are required.']); break; }
            echo json_encode(authResetPassword($token, $password));
            break;

        case 'verify_email':
            requireMethod('POST');
            $token = trim($input['token'] ?? '');
            if (!$token) { echo json_encode(['success'=>false,'error'=>'Token is required.']); break; }
            echo json_encode(authVerifyEmail($token));
            break;

        case 'resend_verification':
            requireMethod('POST');
            $user = requireLogin();
            echo json_encode(authResendVerification((int)$user['id']));
            break;

        case 'zeffy_webhook':
            // Public endpoint hit by Zapier ("New Zeffy payment → POST here").
            // Authenticated by a shared secret token, NOT a user session.
            requireMethod('POST');
            $db = getDB();

            $token = $_GET['token']
                ?? ($_SERVER['HTTP_X_ZEFFY_TOKEN'] ?? null)
                ?? ($input['token'] ?? '');
            if (!hash_equals(zeffyWebhookSecret(), (string)$token)) {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Invalid or missing webhook token.']);
                break;
            }

            // Capture the raw payload so an admin can verify field mapping after
            // the first real event (Zeffy's inner field names aren't documented).
            putSetting('zeffy_webhook_last_at', date('Y-m-d H:i:s'));
            putSetting('zeffy_webhook_count', (string)(((int)getSetting('zeffy_webhook_count', '0')) + 1));
            putSetting('zeffy_webhook_last_payload', substr(json_encode($input, JSON_PRETTY_PRINT), 0, 4000));

            // Zeffy native webhook wraps the payment as {eventType, timestamp, payment:{…}};
            // Zapier/other relays may use different keys. Accept any common shape.
            $payload = $input;
            foreach (['payment', 'data', 'payload', 'object', 'order', 'donation'] as $k) {
                if (isset($input[$k]) && is_array($input[$k])) { $payload = $input[$k]; break; }
            }

            // Ignore refund/cancellation/dispute events — only completed purchases activate.
            $eventType = strtolower((string)($input['eventType'] ?? $input['event'] ?? $input['type'] ?? ''));
            if ($eventType && preg_match('/refund|cancel|dispute|fail|void/', $eventType)) {
                echo json_encode(['success' => true, 'ignored' => true, 'message' => "Ignored event '$eventType'."]);
                break;
            }

            $norm  = ZeffyAPI::normalizePayment($payload);
            $email = strtolower(trim($norm['buyer_email'] ?? ''));
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                // Respond 2xx so Zeffy doesn't retry a payload we simply can't map.
                echo json_encode(['success' => false, 'error' => 'No valid buyer email found in payload. Check the "last received payload" in Admin → Memberships to adjust field mapping.']);
                break;
            }

            $matched = zeffyFindUserByEmail($db, $email);
            if ($matched) {
                $activated = zeffyApplyPaymentToUser($db, (int)$matched['id'], $norm);
                echo json_encode([
                    'success'   => true,
                    'matched'   => true,
                    'activated' => $activated,
                    'message'   => $activated ? 'Membership activated.' : 'Purchase recorded.',
                ]);
            } else {
                // No account with this email yet — hold it until they register.
                zeffyStorePending($db, $email, $norm);
                echo json_encode([
                    'success' => true,
                    'matched' => false,
                    'message' => 'No account for this email yet — payment stored and will apply automatically when they sign up.',
                ]);
            }
            break;

        case 'get_events':
            $db = getDB();
            // Auto-mark events with a past date as non-upcoming (strict past = before today)
            $db->exec("UPDATE events SET is_upcoming=0 WHERE is_upcoming=1 AND event_date IS NOT NULL AND event_date < date('now')");
            // Also restore any that have a future date but were incorrectly marked past
            $db->exec("UPDATE events SET is_upcoming=1 WHERE is_upcoming=0 AND event_date IS NOT NULL AND event_date >= date('now')");
            $published = isset($_GET['all']) && isAdmin() ? '' : 'WHERE is_published=1';
            $upcoming  = isset($_GET['upcoming']) ? " AND is_upcoming=1" : '';
            $rows = $db->query("SELECT * FROM events $published $upcoming ORDER BY event_date ASC")->fetchAll();
            echo json_encode(['success' => true, 'events' => $rows]);
            break;

        case 'get_posts':
            $db = getDB();
            $published = isset($_GET['all']) && isAdmin() ? '' : 'WHERE is_published=1';
            $rows = $db->query("SELECT p.*, u.first_name||' '||u.last_name AS author_name
                                FROM posts p LEFT JOIN users u ON p.created_by=u.id
                                $published ORDER BY p.created_at DESC")->fetchAll();
            echo json_encode(['success' => true, 'posts' => $rows]);
            break;

        case 'get_site_content':
            $db = getDB();
            $rows = $db->query("SELECT * FROM site_content ORDER BY section_key")->fetchAll();
            $map = [];
            foreach ($rows as $r) $map[$r['section_key']] = $r;
            echo json_encode(['success' => true, 'content' => $map]);
            break;

        case 'get_committee_members':
            $db = getDB();
            $rows = $db->query("SELECT * FROM committee_members ORDER BY display_order ASC, id ASC")->fetchAll();
            echo json_encode(['success' => true, 'members' => $rows]);
            break;

        case 'get_membership_tiers':
            $db = getDB();
            $rows = $db->query("SELECT * FROM membership_tiers ORDER BY display_order ASC, id ASC")->fetchAll();
            echo json_encode(['success' => true, 'tiers' => $rows]);
            break;

        case 'get_slideshow_photos':
            $db = getDB();
            $rows = $db->query("SELECT * FROM slideshow_photos ORDER BY display_order ASC, id ASC")->fetchAll();
            echo json_encode(['success' => true, 'photos' => $rows]);
            break;

        // ── EVENT PHOTOS (public read) ───────────────────────────────────────
        case 'get_event_photos':
            $eventId = (int)($_GET['event_id'] ?? 0);
            if (!$eventId) { echo json_encode(['success'=>false,'error'=>'event_id required']); break; }
            $db   = getDB();
            $stmt = $db->prepare("SELECT * FROM event_photos WHERE event_id=? ORDER BY display_order ASC, created_at ASC");
            $stmt->execute([$eventId]);
            echo json_encode(['success' => true, 'photos' => $stmt->fetchAll()]);
            break;

        // ── EVENT FORMS ──────────────────────────────────────────────────────
        case 'get_event_forms':
            $eventId = (int)($_GET['event_id'] ?? $input['event_id'] ?? 0);
            if (!$eventId) { echo json_encode(['success'=>false,'error'=>'event_id required']); break; }
            $db   = getDB();
            $rows = $db->prepare("SELECT id,form_type,title,description,is_active,deadline,max_submissions
                FROM event_forms WHERE event_id=? ORDER BY form_type ASC");
            $rows->execute([$eventId]);
            $forms = $rows->fetchAll();
            // Attach submission count to each form
            foreach ($forms as &$f) {
                $cnt = $db->prepare("SELECT COUNT(*) FROM event_form_submissions WHERE form_id=?");
                $cnt->execute([$f['id']]);
                $f['submission_count'] = (int)$cnt->fetchColumn();
            }
            unset($f);
            echo json_encode(['success'=>true,'forms'=>$forms]);
            break;

        case 'get_event_form_detail':
            $db     = getDB();
            $formId = (int)($_GET['form_id'] ?? $input['form_id'] ?? 0);
            if (!$formId) {
                // Allow lookup by event_id + form_type (public form flow)
                $evId  = (int)($_GET['event_id'] ?? 0);
                $fType = in_array($_GET['form_type'] ?? '', ['volunteer','performer']) ? $_GET['form_type'] : '';
                if ($evId && $fType) {
                    $row = $db->prepare("SELECT id FROM event_forms WHERE event_id=? AND form_type=? AND is_active=1");
                    $row->execute([$evId, $fType]);
                    $formId = (int)($row->fetchColumn() ?: 0);
                }
            }
            if (!$formId) { echo json_encode(['success'=>false,'error'=>'Form not found']); break; }
            $stmt = $db->prepare("SELECT ef.*,e.title AS event_title FROM event_forms ef
                JOIN events e ON e.id=ef.event_id WHERE ef.id=?");
            $stmt->execute([$formId]);
            $form = $stmt->fetch();
            if (!$form) { echo json_encode(['success'=>false,'error'=>'Form not found']); break; }
            $qStmt = $db->prepare("SELECT * FROM event_form_questions WHERE form_id=? ORDER BY display_order ASC, id ASC");
            $qStmt->execute([$formId]);
            $questions = $qStmt->fetchAll();
            foreach ($questions as &$q) {
                $q['options'] = json_decode($q['options_json'] ?: '[]', true) ?: [];
            }
            unset($q);
            // Check if current user already submitted; include their answers
            $userSubmission = null;
            $u = getCurrentUser();
            if ($u) {
                $subStmt = $db->prepare("SELECT id,status,submitted_at FROM event_form_submissions WHERE form_id=? AND user_id=?");
                $subStmt->execute([$formId, $u['id']]);
                $userSubmission = $subStmt->fetch() ?: null;
                if ($userSubmission) {
                    $aStmt = $db->prepare(
                        "SELECT a.answer_text, q.question_text
                         FROM event_form_answers a
                         JOIN event_form_questions q ON q.id=a.question_id
                         WHERE a.submission_id=? ORDER BY q.display_order ASC, q.id ASC");
                    $aStmt->execute([$userSubmission['id']]);
                    $userSubmission['answers'] = $aStmt->fetchAll();
                }
            }
            echo json_encode(['success'=>true,'form'=>$form,'questions'=>$questions,'user_submission'=>$userSubmission]);
            break;

        case 'save_event_form':
            requireMethod('POST');
            requireRole(['admin','cultural_coordinator','coordinator']);
            $db      = getDB();
            $eventId = (int)($input['event_id'] ?? 0);
            $type    = in_array($input['form_type'] ?? '', ['volunteer','performer']) ? $input['form_type'] : null;
            if (!$eventId || !$type) { echo json_encode(['success'=>false,'error'=>'event_id and form_type required']); break; }
            $user    = getCurrentUser();
            $title   = trim($input['title'] ?? '') ?: ($type === 'volunteer' ? 'Volunteer Sign-Up' : 'Performer Application');
            $desc    = trim($input['description'] ?? '');
            $active  = (int)($input['is_active'] ?? 1);
            $deadline= trim($input['deadline'] ?? '') ?: null;
            $maxSubs = (int)($input['max_submissions'] ?? 0);
            $db->prepare(
                "INSERT INTO event_forms (event_id,form_type,title,description,is_active,deadline,max_submissions,created_by)
                 VALUES (?,?,?,?,?,?,?,?)
                 ON CONFLICT(event_id,form_type) DO UPDATE SET
                   title=excluded.title, description=excluded.description,
                   is_active=excluded.is_active, deadline=excluded.deadline,
                   max_submissions=excluded.max_submissions"
            )->execute([$eventId, $type, $title, $desc, $active, $deadline, $maxSubs, $user['id']]);
            $formId = $db->query("SELECT id FROM event_forms WHERE event_id=$eventId AND form_type='$type'")->fetchColumn();
            echo json_encode(['success'=>true,'form_id'=>(int)$formId]);
            break;

        case 'delete_event_form':
            requireMethod('POST');
            requireRole(['admin','cultural_coordinator','coordinator']);
            $db = getDB();
            $db->prepare("DELETE FROM event_forms WHERE id=?")->execute([(int)($input['id'] ?? 0)]);
            echo json_encode(['success'=>true]);
            break;

        case 'save_form_question':
            requireMethod('POST');
            requireRole(['admin','cultural_coordinator','coordinator']);
            $db     = getDB();
            $formId = (int)($input['form_id'] ?? 0);
            $id     = (int)($input['id'] ?? 0);
            $text   = trim($input['question_text'] ?? '');
            $type   = in_array($input['input_type'] ?? '', ['text','textarea','radio','select','checkbox'])
                      ? $input['input_type'] : 'text';
            if (!$formId || !$text) { echo json_encode(['success'=>false,'error'=>'form_id and question_text required']); break; }
            $opts  = json_encode(array_values(array_filter(array_map('trim',
                     is_array($input['options'] ?? null) ? $input['options'] : explode("\n", $input['options'] ?? '')))));
            $wl    = (int)($input['word_limit'] ?? 0);
            $cl    = (int)($input['char_limit'] ?? 0);
            $req   = (int)($input['is_required'] ?? 1);
            $order = (int)($input['display_order'] ?? 0);
            if ($id) {
                $db->prepare("UPDATE event_form_questions SET question_text=?,input_type=?,options_json=?,word_limit=?,char_limit=?,is_required=?,display_order=? WHERE id=?")
                   ->execute([$text, $type, $opts, $wl, $cl, $req, $order, $id]);
                echo json_encode(['success'=>true,'id'=>$id]);
            } else {
                $maxOrder = $db->prepare("SELECT COALESCE(MAX(display_order),0)+1 FROM event_form_questions WHERE form_id=?");
                $maxOrder->execute([$formId]);
                $db->prepare("INSERT INTO event_form_questions (form_id,question_text,input_type,options_json,word_limit,char_limit,is_required,display_order) VALUES (?,?,?,?,?,?,?,?)")
                   ->execute([$formId, $text, $type, $opts, $wl, $cl, $req, $maxOrder->fetchColumn()]);
                echo json_encode(['success'=>true,'id'=>$db->lastInsertId()]);
            }
            break;

        case 'delete_form_question':
            requireMethod('POST');
            requireRole(['admin','cultural_coordinator','coordinator']);
            $db = getDB();
            $db->prepare("DELETE FROM event_form_questions WHERE id=?")->execute([(int)($input['id'] ?? 0)]);
            echo json_encode(['success'=>true]);
            break;

        case 'reorder_form_questions':
            requireMethod('POST');
            requireRole(['admin','cultural_coordinator','coordinator']);
            $db   = getDB();
            $upd  = $db->prepare("UPDATE event_form_questions SET display_order=? WHERE id=?");
            foreach (($input['items'] ?? []) as $item) {
                if (isset($item['id'], $item['order'])) $upd->execute([(int)$item['order'], (int)$item['id']]);
            }
            echo json_encode(['success'=>true]);
            break;

        case 'submit_event_form':
            requireMethod('POST');
            $u = requireLogin();
            $db     = getDB();
            $formId = (int)($input['form_id'] ?? 0);
            if (!$formId) { echo json_encode(['success'=>false,'error'=>'form_id required']); break; }
            // Load form
            $form = $db->prepare("SELECT * FROM event_forms WHERE id=? AND is_active=1")->execute([$formId])
                    ? $db->prepare("SELECT * FROM event_forms WHERE id=? AND is_active=1") : null;
            $fStmt = $db->prepare("SELECT * FROM event_forms WHERE id=? AND is_active=1");
            $fStmt->execute([$formId]);
            $form = $fStmt->fetch();
            if (!$form) { echo json_encode(['success'=>false,'error'=>'Form not found or not active']); break; }
            // Check deadline
            if ($form['deadline'] && strtotime($form['deadline']) < time()) {
                echo json_encode(['success'=>false,'error'=>'This form is no longer accepting submissions']); break;
            }
            // Check max submissions
            if ($form['max_submissions'] > 0) {
                $cnt = $db->prepare("SELECT COUNT(*) FROM event_form_submissions WHERE form_id=?");
                $cnt->execute([$formId]);
                if ($cnt->fetchColumn() >= $form['max_submissions']) {
                    echo json_encode(['success'=>false,'error'=>'This form has reached its maximum number of submissions']); break;
                }
            }
            // Check duplicate
            $dup = $db->prepare("SELECT id FROM event_form_submissions WHERE form_id=? AND user_id=?");
            $dup->execute([$formId, $u['id']]);
            if ($dup->fetch()) { echo json_encode(['success'=>false,'error'=>'You have already submitted this form']); break; }
            // Load questions and validate
            $qStmt = $db->prepare("SELECT * FROM event_form_questions WHERE form_id=? ORDER BY display_order ASC");
            $qStmt->execute([$formId]);
            $questions = $qStmt->fetchAll();
            $answers   = $input['answers'] ?? [];
            foreach ($questions as $q) {
                $ans = trim($answers[$q['id']] ?? '');
                if ($q['is_required'] && $ans === '') {
                    echo json_encode(['success'=>false,'error'=>'Please answer: ' . $q['question_text']]); break 2;
                }
                if ($q['word_limit'] > 0 && $ans !== '') {
                    $words = str_word_count($ans);
                    if ($words > $q['word_limit']) {
                        echo json_encode(['success'=>false,'error'=>'Answer to "' . $q['question_text'] . '" exceeds ' . $q['word_limit'] . ' word limit']); break 2;
                    }
                }
                if ($q['char_limit'] > 0 && mb_strlen($ans) > $q['char_limit']) {
                    echo json_encode(['success'=>false,'error'=>'Answer to "' . $q['question_text'] . '" exceeds ' . $q['char_limit'] . ' character limit']); break 2;
                }
            }
            // Insert submission
            $db->prepare("INSERT INTO event_form_submissions (form_id,user_id) VALUES (?,?)")
               ->execute([$formId, $u['id']]);
            $subId = $db->lastInsertId();
            // Insert answers
            $insA = $db->prepare("INSERT INTO event_form_answers (submission_id,question_id,answer_text) VALUES (?,?,?)");
            foreach ($questions as $q) {
                $ans = $answers[$q['id']] ?? '';
                if (is_array($ans)) $ans = implode(', ', $ans);
                $insA->execute([$subId, $q['id'], trim($ans)]);
            }
            echo json_encode(['success'=>true,'submission_id'=>(int)$subId]);
            break;

        case 'get_form_submissions':
            requireRole(['admin','coordinator','cultural_coordinator','sports_coordinator','membership_coordinator']);
            $formId = (int)($_GET['form_id'] ?? $input['form_id'] ?? 0);
            if (!$formId) { echo json_encode(['success'=>false,'error'=>'form_id required']); break; }
            $db   = getDB();
            $stmt = $db->prepare(
                "SELECT s.*, u.first_name, u.last_name, u.email, u.membership_number
                 FROM event_form_submissions s
                 LEFT JOIN users u ON u.id = s.user_id
                 WHERE s.form_id=? ORDER BY s.submitted_at DESC"
            );
            $stmt->execute([$formId]);
            $subs = $stmt->fetchAll();
            // Attach answers to each submission
            foreach ($subs as &$sub) {
                $aStmt = $db->prepare(
                    "SELECT a.answer_text, q.question_text, q.input_type
                     FROM event_form_answers a JOIN event_form_questions q ON q.id=a.question_id
                     WHERE a.submission_id=? ORDER BY q.display_order ASC"
                );
                $aStmt->execute([$sub['id']]);
                $sub['answers'] = $aStmt->fetchAll();
            }
            unset($sub);
            echo json_encode(['success'=>true,'submissions'=>$subs]);
            break;

        case 'update_submission_status':
            requireMethod('POST');
            requireRole(['admin','coordinator','cultural_coordinator','sports_coordinator']);
            $db     = getDB();
            $id     = (int)($input['id'] ?? 0);
            $status = in_array($input['status'] ?? '', ['pending','approved','rejected']) ? $input['status'] : 'pending';
            $notes  = trim($input['admin_notes'] ?? '');
            $db->prepare("UPDATE event_form_submissions SET status=?,admin_notes=? WHERE id=?")->execute([$status, $notes, $id]);
            echo json_encode(['success'=>true]);
            break;

        // ── EVENT MEDIA (videos & links, public read) ────────────────────────
        case 'get_event_media':
            $eventId = (int)($_GET['event_id'] ?? 0);
            if (!$eventId) { echo json_encode(['success'=>false,'error'=>'event_id required']); break; }
            $db   = getDB();
            $stmt = $db->prepare("SELECT * FROM event_media WHERE event_id=? ORDER BY display_order ASC, created_at ASC");
            $stmt->execute([$eventId]);
            echo json_encode(['success' => true, 'media' => $stmt->fetchAll()]);
            break;

        case 'add_event_media':
            requireMethod('POST');
            requireRole(['admin', 'coordinator', 'cultural_coordinator', 'sports_coordinator']);
            $db      = getDB();
            $eventId = (int)($input['event_id'] ?? 0);
            $url     = trim($input['url'] ?? '');
            $label   = trim($input['label'] ?? '');
            if (!$eventId || !$url) { echo json_encode(['success'=>false,'error'=>'event_id and url required']); break; }
            if (!filter_var($url, FILTER_VALIDATE_URL)) { echo json_encode(['success'=>false,'error'=>'Invalid URL']); break; }

            // Detect type
            $type = 'link';
            if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/|v\/)|youtu\.be\/)([^&\s?]+)/', $url)) {
                $type = 'youtube';
            }

            $order = (int)$db->query("SELECT COALESCE(MAX(display_order),0)+1 FROM event_media WHERE event_id=$eventId")->fetchColumn();
            $ins = $db->prepare("INSERT INTO event_media (event_id, type, url, label, display_order) VALUES (?,?,?,?,?)");
            $ins->execute([$eventId, $type, $url, $label, $order]);
            echo json_encode(['success' => true, 'id' => $db->lastInsertId(), 'type' => $type]);
            break;

        case 'delete_event_media':
            requireMethod('POST');
            requireRole(['admin', 'coordinator']);
            $db = getDB();
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success'=>false,'error'=>'id required']); break; }
            $db->prepare("DELETE FROM event_media WHERE id=?")->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        // ── MEMBER ──────────────────────────────────────────────────────────
        case 'get_my_tickets':
            $user = requireLogin();
            $db = getDB();
            $stmt = $db->prepare(
                "SELECT t.*, e.title, e.event_date, e.event_time, e.location, e.image_path
                 FROM tickets t JOIN events e ON t.event_id=e.id
                 WHERE t.user_id=? ORDER BY t.purchase_date DESC"
            );
            $stmt->execute([$user['id']]);
            echo json_encode(['success' => true, 'tickets' => $stmt->fetchAll()]);
            break;

        case 'purchase_ticket':
            requireMethod('POST');
            $user  = requireLogin();
            $db    = getDB();
            $evtId = (int)($input['event_id'] ?? 0);
            $qty   = max(1, (int)($input['quantity'] ?? 1));
            $type  = isActiveMember($user) ? 'member' : 'regular';

            $evt = $db->prepare("SELECT * FROM events WHERE id=? AND is_published=1 LIMIT 1");
            $evt->execute([$evtId]);
            $event = $evt->fetch();
            if (!$event) { echo json_encode(['success'=>false,'error'=>'Event not found']); break; }

            $price = $type === 'member' ? $event['member_price'] : $event['regular_price'];
            $total = $price * $qty;

            $ins = $db->prepare("INSERT INTO tickets (user_id,event_id,ticket_type,quantity,total_price) VALUES (?,?,?,?,?)");
            $ins->execute([$user['id'], $evtId, $type, $qty, $total]);
            echo json_encode(['success' => true, 'ticket_id' => $db->lastInsertId(), 'total' => $total]);
            break;

        case 'cancel_ticket':
            requireMethod('POST');
            $user = requireLogin();
            $db   = getDB();
            $tid  = (int)($input['ticket_id'] ?? 0);
            $stmt = $db->prepare("UPDATE tickets SET status='cancelled' WHERE id=? AND user_id=?");
            $stmt->execute([$tid, $user['id']]);
            echo json_encode(['success' => true]);
            break;

        case 'update_profile':
            requireMethod('POST');
            $user = requireLogin();
            $db   = getDB();
            $upd  = $db->prepare("UPDATE users SET first_name=?,last_name=?,phone=? WHERE id=?");
            $upd->execute([
                trim($input['first_name'] ?? $user['first_name']),
                trim($input['last_name']  ?? $user['last_name']),
                trim($input['phone']      ?? ''),
                $user['id'],
            ]);
            if (!empty($input['new_password'])) {
                if (strlen($input['new_password']) < 8) {
                    echo json_encode(['success'=>false,'error'=>'Password must be 8+ chars']); break;
                }
                $db->prepare("UPDATE users SET password=? WHERE id=?")->execute([
                    password_hash($input['new_password'], PASSWORD_DEFAULT), $user['id']
                ]);
            }
            echo json_encode(['success' => true]);
            break;

        // ── COORDINATOR / ADMIN ─────────────────────────────────────────────
        case 'save_membership_tier':
            requireMethod('POST');
            requireRole('admin');
            $db   = getDB();
            $id   = (int)($input['id'] ?? 0);
            $name = trim($input['name'] ?? '');
            if (!$name) { echo json_encode(['success'=>false,'error'=>'Name required']); break; }
            if ($id) {
                $db->prepare("UPDATE membership_tiers SET name=?,icon=?,price=?,currency=?,description=?,is_featured=?,display_order=? WHERE id=?")
                   ->execute([
                       $name, trim($input['icon'] ?? 'bi-person'),
                       (float)($input['price'] ?? 0), trim($input['currency'] ?? '$'),
                       trim($input['description'] ?? ''),
                       (int)($input['is_featured'] ?? 0), (int)($input['display_order'] ?? 0), $id,
                   ]);
                echo json_encode(['success'=>true,'id'=>$id]);
            } else {
                $db->prepare("INSERT INTO membership_tiers (name,icon,price,currency,description,is_featured,display_order) VALUES (?,?,?,?,?,?,?)")
                   ->execute([
                       $name, trim($input['icon'] ?? 'bi-person'),
                       (float)($input['price'] ?? 0), trim($input['currency'] ?? '$'),
                       trim($input['description'] ?? ''),
                       (int)($input['is_featured'] ?? 0), (int)($input['display_order'] ?? 0),
                   ]);
                echo json_encode(['success'=>true,'id'=>$db->lastInsertId()]);
            }
            break;

        case 'delete_membership_tier':
            requireMethod('POST');
            requireRole('admin');
            $db = getDB();
            $db->prepare("DELETE FROM membership_tiers WHERE id=?")->execute([(int)($input['id'] ?? 0)]);
            echo json_encode(['success' => true]);
            break;

        case 'get_benefit_panels':
            $db = getDB();
            $panels = $db->query("SELECT * FROM benefit_panels ORDER BY display_order ASC, id ASC")->fetchAll();
            echo json_encode(['success'=>true,'panels'=>$panels]);
            break;

        case 'save_benefit_panel':
            requireMethod('POST');
            requireRole('admin');
            $db    = getDB();
            $id    = (int)($input['id'] ?? 0);
            $title = trim($input['title'] ?? '');
            if (!$title) { echo json_encode(['success'=>false,'error'=>'Title required']); break; }
            if ($id) {
                $db->prepare("UPDATE benefit_panels SET icon=?,title=?,content=?,display_order=? WHERE id=?")
                   ->execute([trim($input['icon'] ?? 'bi-star'), $title, $input['content'] ?? '', (int)($input['display_order'] ?? 0), $id]);
                echo json_encode(['success'=>true,'id'=>$id]);
            } else {
                $db->prepare("INSERT INTO benefit_panels (icon,title,content,display_order) VALUES (?,?,?,?)")
                   ->execute([trim($input['icon'] ?? 'bi-star'), $title, $input['content'] ?? '', (int)($input['display_order'] ?? 0)]);
                echo json_encode(['success'=>true,'id'=>$db->lastInsertId()]);
            }
            break;

        case 'delete_benefit_panel':
            requireMethod('POST');
            requireRole('admin');
            $db = getDB();
            $db->prepare("DELETE FROM benefit_panels WHERE id=?")->execute([(int)($input['id'] ?? 0)]);
            echo json_encode(['success'=>true]);
            break;

        case 'get_vision_stats':
            $db = getDB();
            $stats = $db->query("SELECT * FROM vision_stats ORDER BY display_order ASC, id ASC")->fetchAll();
            echo json_encode(['success'=>true,'stats'=>$stats]);
            break;

        case 'save_vision_stat':
            requireMethod('POST');
            requireRole('admin');
            $db = getDB();
            $id  = (int)($input['id'] ?? 0);
            $num = trim($input['number_text'] ?? '');
            $lbl = trim($input['label'] ?? '');
            if (!$num || !$lbl) { echo json_encode(['success'=>false,'error'=>'Number and label required']); break; }
            if ($id) {
                $db->prepare("UPDATE vision_stats SET number_text=?,label=?,display_order=? WHERE id=?")
                   ->execute([$num, $lbl, (int)($input['display_order'] ?? 0), $id]);
                echo json_encode(['success'=>true,'id'=>$id]);
            } else {
                $db->prepare("INSERT INTO vision_stats (number_text,label,display_order) VALUES (?,?,?)")
                   ->execute([$num, $lbl, (int)($input['display_order'] ?? 0)]);
                echo json_encode(['success'=>true,'id'=>$db->lastInsertId()]);
            }
            break;

        case 'delete_vision_stat':
            requireMethod('POST');
            requireRole('admin');
            $db = getDB();
            $db->prepare("DELETE FROM vision_stats WHERE id=?")->execute([(int)($input['id'] ?? 0)]);
            echo json_encode(['success'=>true]);
            break;

        case 'get_vision_core_values':
            $db = getDB();
            $vals = $db->query("SELECT * FROM vision_core_values ORDER BY display_order ASC, id ASC")->fetchAll();
            echo json_encode(['success'=>true,'values'=>$vals]);
            break;

        case 'save_vision_core_value':
            requireMethod('POST');
            requireRole('admin');
            $db    = getDB();
            $id    = (int)($input['id'] ?? 0);
            $title = trim($input['title'] ?? '');
            if (!$title) { echo json_encode(['success'=>false,'error'=>'Title required']); break; }
            if ($id) {
                $db->prepare("UPDATE vision_core_values SET title=?,description=?,display_order=? WHERE id=?")
                   ->execute([$title, $input['description'] ?? '', (int)($input['display_order'] ?? 0), $id]);
                echo json_encode(['success'=>true,'id'=>$id]);
            } else {
                $db->prepare("INSERT INTO vision_core_values (title,description,display_order) VALUES (?,?,?)")
                   ->execute([$title, $input['description'] ?? '', (int)($input['display_order'] ?? 0)]);
                echo json_encode(['success'=>true,'id'=>$db->lastInsertId()]);
            }
            break;

        case 'delete_vision_core_value':
            requireMethod('POST');
            requireRole('admin');
            $db = getDB();
            $db->prepare("DELETE FROM vision_core_values WHERE id=?")->execute([(int)($input['id'] ?? 0)]);
            echo json_encode(['success'=>true]);
            break;

        case 'get_contact_settings':
            requireRole('admin');
            echo json_encode([
                'success'                 => true,
                'contact_recipient_email' => getSetting('contact_recipient_email', 'ottawatamilsangam@gmail.com'),
            ]);
            break;

        case 'save_contact_settings':
            requireMethod('POST');
            requireRole('admin');
            $recip = trim($input['contact_recipient_email'] ?? '');
            if (!filter_var($recip, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success'=>false,'error'=>'Invalid email address']); break;
            }
            putSetting('contact_recipient_email', $recip);
            echo json_encode(['success'=>true]);
            break;

        case 'send_contact_message':
            requireMethod('POST');
            $firstName = trim($input['first_name'] ?? '');
            $lastName  = trim($input['last_name']  ?? '');
            $senderEmail = trim($input['email']    ?? '');
            $message   = trim($input['message']    ?? '');
            if (!$firstName || !$lastName || !$senderEmail || !$message) {
                echo json_encode(['success'=>false,'error'=>'All fields are required']); break;
            }
            if (!filter_var($senderEmail, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success'=>false,'error'=>'Invalid email address']); break;
            }
            if (strlen($message) > 5000) {
                echo json_encode(['success'=>false,'error'=>'Message is too long (5000 characters max).']); break;
            }

            // Store the message first so it's captured even if the email fails.
            $db = getDB();
            $db->prepare("INSERT INTO contact_messages (first_name,last_name,email,message) VALUES (?,?,?,?)")
               ->execute([$firstName, $lastName, $senderEmail, $message]);
            $msgRowId = (int)$db->lastInsertId();

            $recip   = getSetting('contact_recipient_email', 'ottawatamilsangam@gmail.com');
            $subject = 'OTS Website Email from ' . $firstName . ' ' . $lastName;
            $msgHtml = nl2br(htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
            $body = mailBody($subject, <<<HTML
              <h2>New Message from {$firstName} {$lastName}</h2>
              <table style="border-collapse:collapse;margin-bottom:20px;font-size:.95rem">
                <tr>
                  <td style="padding:6px 16px 6px 0;color:#6b7280;font-weight:600;white-space:nowrap">Name</td>
                  <td style="padding:6px 0">{$firstName} {$lastName}</td>
                </tr>
                <tr style="background:#faf8f3">
                  <td style="padding:6px 16px 6px 0;color:#6b7280;font-weight:600;white-space:nowrap">Reply&nbsp;To</td>
                  <td style="padding:6px 0"><a href="mailto:{$senderEmail}" style="color:#6b0f1a;font-weight:700">{$senderEmail}</a></td>
                </tr>
              </table>
              <hr style="border:none;border-top:1px solid #e5e7eb;margin:0 0 20px"/>
              <h3 style="color:#333;font-family:Georgia,serif;font-size:1rem;margin-bottom:10px">Message</h3>
              <p style="background:#faf8f3;border-left:3px solid #d4a73a;padding:16px 20px;border-radius:0 8px 8px 0;white-space:pre-wrap">{$msgHtml}</p>
HTML);
            // Add Reply-To header so admin can reply directly
            // We re-use sendMail but need reply-to — pass senderEmail as "name" in a custom call
            // Workaround: override MAIL_FROM in header via custom send
            $sent = _sendContactMail($recip, $subject, $body, $senderEmail, $firstName . ' ' . $lastName);
            if ($sent) {
                $db->prepare("UPDATE contact_messages SET emailed=1 WHERE id=?")->execute([$msgRowId]);
            }
            // The message is saved either way, so the submission succeeds.
            echo json_encode(['success'=>true]);
            break;

        case 'get_contact_messages':
            requireRole(['admin', 'coordinator', 'social_media']);
            $db = getDB();
            $rows = $db->query("SELECT * FROM contact_messages ORDER BY created_at DESC, id DESC")->fetchAll();
            $unread = (int)$db->query("SELECT COUNT(*) FROM contact_messages WHERE is_read=0")->fetchColumn();
            echo json_encode(['success'=>true, 'messages'=>$rows, 'unread'=>$unread]);
            break;

        case 'mark_contact_message_read':
            requireMethod('POST');
            requireRole(['admin', 'coordinator', 'social_media']);
            $db = getDB();
            $db->prepare("UPDATE contact_messages SET is_read=1 WHERE id=?")->execute([(int)($input['id'] ?? 0)]);
            echo json_encode(['success'=>true]);
            break;

        case 'delete_contact_message':
            requireMethod('POST');
            requireRole(['admin', 'coordinator', 'social_media']);
            $db = getDB();
            $db->prepare("DELETE FROM contact_messages WHERE id=?")->execute([(int)($input['id'] ?? 0)]);
            echo json_encode(['success'=>true]);
            break;

        case 'upload_slideshow_photo':
            requireRole(['admin', 'coordinator', 'social_media', 'cultural_coordinator', 'sports_coordinator']);
            if (empty($_FILES['photo'])) { echo json_encode(['success'=>false,'error'=>'No file uploaded']); break; }
            $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
            $exts    = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'];
            $file = $_FILES['photo'];
            if ($file['error'] !== UPLOAD_ERR_OK) { echo json_encode(['success'=>false,'error'=>'Upload error']); break; }
            $mime = mime_content_type($file['tmp_name']);
            if (!in_array($mime, $allowed)) { echo json_encode(['success'=>false,'error'=>'Images only (JPEG, PNG, WebP, GIF)']); break; }
            if ($file['size'] > 25 * 1024 * 1024) { echo json_encode(['success'=>false,'error'=>'Max 25 MB']); break; }
            $dir = __DIR__ . '/assets/OTS_pics/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $filename = uniqid('slide_', true) . '.' . ($exts[$mime] ?? 'jpg');
            if (!move_uploaded_file($file['tmp_name'], $dir . $filename)) {
                echo json_encode(['success'=>false,'error'=>'Could not save file']); break;
            }
            $path = 'OTS_pics/' . $filename;
            $db   = getDB();
            $maxOrder = $db->query("SELECT COALESCE(MAX(display_order),0) FROM slideshow_photos")->fetchColumn();
            $db->prepare("INSERT INTO slideshow_photos (photo_path,is_active,display_order) VALUES (?,1,?)")
               ->execute([$path, $maxOrder + 1]);
            echo json_encode(['success'=>true,'path'=>$path,'id'=>$db->lastInsertId()]);
            break;

        case 'delete_slideshow_photo':
            requireMethod('POST');
            requireRole(['admin', 'coordinator', 'social_media', 'cultural_coordinator', 'sports_coordinator']);
            $db = getDB();
            $id = (int)($input['id'] ?? 0);
            $row = $db->prepare("SELECT photo_path FROM slideshow_photos WHERE id=?");
            $row->execute([$id]);
            $photo = $row->fetch();
            if ($photo) {
                // Only delete file if it's in our managed OTS_pics folder
                $fullPath = __DIR__ . '/assets/' . $photo['photo_path'];
                if (str_starts_with($photo['photo_path'], 'OTS_pics/slide_') && file_exists($fullPath)) {
                    unlink($fullPath);
                }
                $db->prepare("DELETE FROM slideshow_photos WHERE id=?")->execute([$id]);
            }
            echo json_encode(['success' => true]);
            break;

        case 'toggle_slideshow_photo':
            requireMethod('POST');
            requireRole(['admin', 'coordinator', 'social_media', 'cultural_coordinator', 'sports_coordinator']);
            $db = getDB();
            $db->prepare("UPDATE slideshow_photos SET is_active = CASE WHEN is_active=1 THEN 0 ELSE 1 END WHERE id=?")
               ->execute([(int)($input['id'] ?? 0)]);
            echo json_encode(['success' => true]);
            break;

        case 'reorder_slideshow_photos':
            requireMethod('POST');
            requireRole(['admin', 'coordinator', 'social_media', 'cultural_coordinator', 'sports_coordinator']);
            $items = $input['items'] ?? [];
            if (!is_array($items)) { echo json_encode(['success'=>false,'error'=>'items must be array']); break; }
            $db  = getDB();
            $upd = $db->prepare("UPDATE slideshow_photos SET display_order=? WHERE id=?");
            foreach ($items as $item) {
                if (isset($item['id'], $item['order'])) $upd->execute([(int)$item['order'], (int)$item['id']]);
            }
            echo json_encode(['success' => true]);
            break;

        case 'save_committee_member':
            requireMethod('POST');
            requireRole(['admin', 'coordinator']);
            $db = getDB();
            $id           = (int)($input['id'] ?? 0);
            $nameEn       = trim($input['name_english'] ?? '');
            $nameTa       = trim($input['name_tamil']   ?? '');
            $roleEn       = trim($input['role_english'] ?? '');
            $roleTa       = trim($input['role_tamil']   ?? '');
            $photo        = trim($input['photo_path']   ?? '');
            $order        = (int)($input['display_order'] ?? 0);
            if (!$nameEn) { echo json_encode(['success'=>false,'error'=>'Name is required']); break; }
            if ($id) {
                $db->prepare("UPDATE committee_members SET name_english=?,name_tamil=?,role_english=?,role_tamil=?,photo_path=?,display_order=? WHERE id=?")
                   ->execute([$nameEn, $nameTa, $roleEn, $roleTa, $photo, $order, $id]);
                echo json_encode(['success'=>true,'id'=>$id]);
            } else {
                $db->prepare("INSERT INTO committee_members (name_english,name_tamil,role_english,role_tamil,photo_path,display_order) VALUES (?,?,?,?,?,?)")
                   ->execute([$nameEn, $nameTa, $roleEn, $roleTa, $photo, $order]);
                echo json_encode(['success'=>true,'id'=>$db->lastInsertId()]);
            }
            break;

        case 'delete_committee_member':
            requireMethod('POST');
            requireRole(['admin', 'coordinator']);
            $db = getDB();
            $db->prepare("DELETE FROM committee_members WHERE id=?")->execute([(int)($input['id'] ?? 0)]);
            echo json_encode(['success' => true]);
            break;

        case 'reorder_committee_members':
            requireMethod('POST');
            requireRole(['admin', 'coordinator']);
            $items = $input['items'] ?? [];
            if (!is_array($items)) { echo json_encode(['success'=>false,'error'=>'items must be array']); break; }
            $db  = getDB();
            $upd = $db->prepare("UPDATE committee_members SET display_order=? WHERE id=?");
            foreach ($items as $item) {
                if (isset($item['id'], $item['order'])) $upd->execute([(int)$item['order'], (int)$item['id']]);
            }
            echo json_encode(['success' => true]);
            break;

        case 'upload_committee_photo':
            requireRole(['admin', 'coordinator']);
            if (empty($_FILES['photo'])) { echo json_encode(['success'=>false,'error'=>'No file uploaded']); break; }
            $result = handleImageUpload($_FILES['photo'], 'committee');
            if ($result['success']) $result['path'] = $result['path']; // already relative
            echo json_encode($result);
            break;

        case 'create_event':
            requireMethod('POST');
            requireRole(['admin', 'coordinator', 'cultural_coordinator', 'sports_coordinator']);
            $user = getCurrentUser();
            $db   = getDB();
            $ins  = $db->prepare(
                "INSERT INTO events (title,title_tamil,description,event_date,event_time,location,image_path,ticket_url,member_price,regular_price,is_upcoming,is_published,created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)"
            );
            $eventDate  = trim($input['event_date'] ?? '');
            // Auto-derive is_upcoming from date; if no date, default to upcoming
            $isUpcoming = $eventDate
                ? (strtotime($eventDate) >= strtotime(date('Y-m-d')) ? 1 : 0)
                : 1;
            $ins->execute([
                trim($input['title'] ?? ''),
                trim($input['title_tamil'] ?? ''),
                $input['description'] ?? '',
                $eventDate ?: null,
                $input['event_time'] ?? '',
                trim($input['location'] ?? ''),
                $input['image_path'] ?? '',
                $input['ticket_url'] ?? '',
                (float)($input['member_price'] ?? 0),
                (float)($input['regular_price'] ?? 0),
                $isUpcoming,
                (int)($input['is_published'] ?? 1),
                $user['id'],
            ]);
            echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
            break;

        case 'update_event':
            requireMethod('POST');
            requireRole(['admin', 'coordinator', 'cultural_coordinator', 'sports_coordinator']);
            $user = getCurrentUser();
            $db   = getDB();
            $id   = (int)($input['id'] ?? 0);

            // Coordinators can only edit description & image; admins can edit everything
            if (in_array($user['role'], ['coordinator', 'cultural_coordinator', 'sports_coordinator'], true)) {
                $upd = $db->prepare("UPDATE events SET description=?,image_path=?,updated_at=CURRENT_TIMESTAMP WHERE id=?");
                $upd->execute([$input['description'] ?? '', $input['image_path'] ?? '', $id]);
            } else {
                $upd = $db->prepare(
                    "UPDATE events SET title=?,title_tamil=?,description=?,event_date=?,event_time=?,
                     location=?,image_path=?,ticket_url=?,member_price=?,regular_price=?,is_upcoming=?,is_published=?,updated_at=CURRENT_TIMESTAMP
                     WHERE id=?"
                );
                $evDate2    = trim($input['event_date'] ?? '');
                $isUpcoming2 = $evDate2
                    ? (strtotime($evDate2) >= strtotime(date('Y-m-d')) ? 1 : 0)
                    : (int)($input['is_upcoming'] ?? 1);
                $upd->execute([
                    trim($input['title'] ?? ''),
                    trim($input['title_tamil'] ?? ''),
                    $input['description'] ?? '',
                    $evDate2 ?: null,
                    $input['event_time'] ?? '',
                    trim($input['location'] ?? ''),
                    $input['image_path'] ?? '',
                    $input['ticket_url'] ?? '',
                    (float)($input['member_price'] ?? 0),
                    (float)($input['regular_price'] ?? 0),
                    $isUpcoming2,
                    (int)($input['is_published'] ?? 1),
                    $id,
                ]);
            }
            echo json_encode(['success' => true]);
            break;

        case 'delete_event':
            requireMethod('POST');
            requireRole('admin');
            $db  = getDB();
            $eid = (int)($input['id'] ?? 0);
            // tickets references events(id) without ON DELETE CASCADE, so with
            // PRAGMA foreign_keys=ON the parent delete would fail. Remove them first.
            $db->prepare("DELETE FROM tickets WHERE event_id=?")->execute([$eid]);
            $db->prepare("DELETE FROM events WHERE id=?")->execute([$eid]);
            echo json_encode(['success' => true]);
            break;

        case 'create_post':
            requireMethod('POST');
            requireRole(['admin', 'coordinator', 'social_media']);
            $user = getCurrentUser();
            $db   = getDB();
            $ins  = $db->prepare(
                "INSERT INTO posts (title,content,image_path,post_type,is_published,created_by) VALUES (?,?,?,?,?,?)"
            );
            $ins->execute([
                trim($input['title'] ?? ''),
                $input['content'] ?? '',
                $input['image_path'] ?? '',
                $input['post_type'] ?? 'announcement',
                (int)($input['is_published'] ?? 1),
                $user['id'],
            ]);
            echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
            break;

        case 'update_post':
            requireMethod('POST');
            requireRole(['admin', 'coordinator', 'social_media']);
            $db = getDB();
            $upd = $db->prepare(
                "UPDATE posts SET title=?,content=?,image_path=?,post_type=?,is_published=?,updated_at=CURRENT_TIMESTAMP WHERE id=?"
            );
            $upd->execute([
                trim($input['title'] ?? ''),
                $input['content'] ?? '',
                $input['image_path'] ?? '',
                $input['post_type'] ?? 'announcement',
                (int)($input['is_published'] ?? 1),
                (int)($input['id'] ?? 0),
            ]);
            echo json_encode(['success' => true]);
            break;

        case 'delete_post':
            requireMethod('POST');
            requireRole(['admin', 'coordinator', 'social_media']);
            $db = getDB();
            $user = getCurrentUser();
            // Non-admin roles can only delete their own posts
            if (in_array($user['role'], ['coordinator', 'social_media'], true)) {
                $db->prepare("DELETE FROM posts WHERE id=? AND created_by=?")->execute([(int)$input['id'], $user['id']]);
            } else {
                $db->prepare("DELETE FROM posts WHERE id=?")->execute([(int)$input['id']]);
            }
            echo json_encode(['success' => true]);
            break;

        case 'update_site_content':
            requireMethod('POST');
            requireRole(['admin', 'coordinator', 'social_media']);
            $user = getCurrentUser();
            $db   = getDB();

            $key = $input['section_key'] ?? '';
            if (!$key) { echo json_encode(['success'=>false,'error'=>'section_key required']); break; }

            $upd = $db->prepare(
                "INSERT INTO site_content (section_key,content_html,image_path,updated_by,updated_at)
                 VALUES (?,?,?,?,CURRENT_TIMESTAMP)
                 ON CONFLICT(section_key) DO UPDATE SET
                   content_html=excluded.content_html,
                   image_path=excluded.image_path,
                   updated_by=excluded.updated_by,
                   updated_at=CURRENT_TIMESTAMP"
            );
            $upd->execute([
                $key,
                $input['content_html'] ?? '',
                $input['image_path'] ?? '',
                $user['id'],
            ]);
            echo json_encode(['success' => true]);
            break;

        // ── EVENT PHOTOS (coordinator/admin write) ───────────────────────────
        case 'upload_event_photo':
            requireRole(['admin', 'coordinator', 'cultural_coordinator', 'sports_coordinator']);
            $eventId = (int)($_POST['event_id'] ?? 0);
            if (!$eventId) { echo json_encode(['success'=>false,'error'=>'event_id required']); break; }
            if (empty($_FILES['photo'])) { echo json_encode(['success'=>false,'error'=>'No file uploaded']); break; }

            $db = getDB();
            // Check photo limit (max 200 per event)
            $count = $db->prepare("SELECT COUNT(*) FROM event_photos WHERE event_id=?");
            $count->execute([$eventId]);
            if ($count->fetchColumn() >= 200) {
                echo json_encode(['success'=>false,'error'=>'Maximum 200 photos per event.']); break;
            }

            $result = uploadToCloudinaryOrLocal($_FILES['photo'], (string)$eventId);
            if (!$result['success']) { echo json_encode($result); break; }

            $caption = trim($_POST['caption'] ?? '');
            $order   = (int)($_POST['display_order'] ?? 0);
            $ins = $db->prepare(
                "INSERT INTO event_photos (event_id, photo_url, public_id, caption, display_order) VALUES (?,?,?,?,?)"
            );
            $ins->execute([$eventId, $result['url'], $result['public_id'], $caption, $order]);
            echo json_encode([
                'success'  => true,
                'photo_id' => $db->lastInsertId(),
                'url'      => $result['url'],
                'public_id'=> $result['public_id'],
            ]);
            break;

        case 'delete_event_photo':
            requireMethod('POST');
            requireRole(['admin', 'coordinator', 'cultural_coordinator', 'sports_coordinator']);
            $photoId = (int)($input['id'] ?? 0);
            if (!$photoId) { echo json_encode(['success'=>false,'error'=>'id required']); break; }

            $db   = getDB();
            $stmt = $db->prepare("SELECT * FROM event_photos WHERE id=? LIMIT 1");
            $stmt->execute([$photoId]);
            $photo = $stmt->fetch();
            if (!$photo) { echo json_encode(['success'=>false,'error'=>'Photo not found']); break; }

            // Delete from Cloudinary if we have a public_id
            if (!empty($photo['public_id']) && CLOUDINARY_CLOUD_NAME && CLOUDINARY_API_KEY && CLOUDINARY_API_SECRET) {
                _deleteFromCloudinary($photo['public_id']);
            }

            $db->prepare("DELETE FROM event_photos WHERE id=?")->execute([$photoId]);
            echo json_encode(['success' => true]);
            break;

        case 'reorder_event_photos':
            requireMethod('POST');
            requireRole(['admin', 'coordinator', 'cultural_coordinator', 'sports_coordinator']);
            $items = $input['items'] ?? [];
            if (!is_array($items)) { echo json_encode(['success'=>false,'error'=>'items must be array']); break; }

            $db  = getDB();
            $upd = $db->prepare("UPDATE event_photos SET display_order=? WHERE id=?");
            foreach ($items as $item) {
                if (isset($item['id'], $item['order'])) {
                    $upd->execute([(int)$item['order'], (int)$item['id']]);
                }
            }
            echo json_encode(['success' => true]);
            break;

        case 'update_photo_caption':
            requireMethod('POST');
            requireRole(['admin', 'coordinator', 'cultural_coordinator', 'sports_coordinator']);
            $photoId = (int)($input['id'] ?? 0);
            $caption = trim($input['caption'] ?? '');
            if (!$photoId) { echo json_encode(['success'=>false,'error'=>'id required']); break; }
            $db = getDB();
            $db->prepare("UPDATE event_photos SET caption=? WHERE id=?")->execute([$caption, $photoId]);
            echo json_encode(['success' => true]);
            break;

        // ── MEMBERSHIP COORDINATOR ──────────────────────────────────────────
        case 'get_members_list':
            requireRole(['admin', 'membership_coordinator']);
            $db   = getDB();
            $rows = $db->query(
                "SELECT id,email,first_name,last_name,role,membership_number,membership_expiry,
                        membership_status,phone,created_at,account_status
                 FROM users ORDER BY first_name ASC, last_name ASC"
            )->fetchAll();
            echo json_encode(['success' => true, 'users' => $rows]);
            break;

        case 'mem_coord_save_user':
            requireMethod('POST');
            requireRole(['admin', 'membership_coordinator']);
            $db = getDB();
            $id = (int)($input['id'] ?? 0);
            // New user
            if (!$id) {
                $email = strtolower(trim($input['email'] ?? ''));
                if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    echo json_encode(['success'=>false,'error'=>'Valid email required']); break;
                }
                $exists = $db->prepare("SELECT id FROM users WHERE email=?")->execute([$email]);
                if ($db->prepare("SELECT id FROM users WHERE email=?")->execute([$email]) && $db->query("SELECT id FROM users WHERE email='".SQLite3::escapeString($email)."'")->fetchColumn()) {
                    echo json_encode(['success'=>false,'error'=>'Email already exists']); break;
                }
                $hash = password_hash($input['password'] ?? uniqid('tmp', true), PASSWORD_DEFAULT);
                $db->prepare(
                    "INSERT INTO users (first_name,last_name,email,password,phone,role,membership_status,email_verified,account_status)
                     VALUES (?,?,?,?,?,?,?,1,'active')"
                )->execute([
                    trim($input['first_name'] ?? ''),
                    trim($input['last_name']  ?? ''),
                    $email,
                    $hash,
                    trim($input['phone'] ?? ''),
                    'non_member',
                    'none',
                ]);
                echo json_encode(['success'=>true,'id'=>$db->lastInsertId()]);
            } else {
                // Update — membership coordinator cannot change roles to admin/coordinator
                $db->prepare(
                    "UPDATE users SET first_name=?,last_name=?,email=?,phone=?,
                     membership_status=?,membership_expiry=? WHERE id=?"
                )->execute([
                    trim($input['first_name'] ?? ''),
                    trim($input['last_name']  ?? ''),
                    strtolower(trim($input['email'] ?? '')),
                    trim($input['phone'] ?? ''),
                    $input['membership_status'] ?? 'none',
                    $input['membership_expiry'] ?: null,
                    $id,
                ]);
                echo json_encode(['success'=>true]);
            }
            break;

        case 'set_account_status':
            requireMethod('POST');
            requireRole(['admin', 'membership_coordinator']);
            $db     = getDB();
            $id     = (int)($input['id'] ?? 0);
            $status = in_array($input['status'] ?? '', ['active','inactive']) ? $input['status'] : 'inactive';
            $db->prepare("UPDATE users SET account_status=? WHERE id=?")->execute([$status, $id]);
            echo json_encode(['success'=>true]);
            break;

        // ── ADMIN ONLY ───────────────────────────────────────────────────────
        case 'get_users':
            requireRole('admin');
            $db   = getDB();
            $rows = $db->query(
                "SELECT id,email,first_name,last_name,role,extra_roles,membership_number,membership_expiry,membership_status,phone,created_at,email_verified,account_status FROM users ORDER BY created_at DESC"
            )->fetchAll();
            echo json_encode(['success' => true, 'users' => $rows]);
            break;

        case 'search_user_by_email':
            requireRole('admin');
            $db    = getDB();
            $email = strtolower(trim($input['email'] ?? ''));
            if (!$email) { echo json_encode(['success'=>false,'error'=>'Email required']); break; }
            $stmt  = $db->prepare("SELECT id,email,first_name,last_name,role,extra_roles,account_status FROM users WHERE email LIKE ? LIMIT 10");
            $stmt->execute(['%' . $email . '%']);
            echo json_encode(['success'=>true,'users'=>$stmt->fetchAll()]);
            break;

        case 'assign_role':
            requireMethod('POST');
            requireRole('admin');
            $db      = getDB();
            $id      = (int)($input['id'] ?? 0);
            $allowed = ['non_member','member','coordinator','social_media','membership_coordinator','cultural_coordinator','sports_coordinator','admin'];
            // Accept either a `roles` array (multi-role) or a single `role` (legacy)
            $roles = [];
            if (!empty($input['roles']) && is_array($input['roles'])) {
                foreach ($input['roles'] as $r) {
                    if (in_array($r, $allowed, true)) $roles[] = $r;
                }
            } elseif (!empty($input['role']) && in_array($input['role'], $allowed, true)) {
                $roles[] = $input['role'];
            }
            if (!$id || empty($roles)) { echo json_encode(['success'=>false,'error'=>'User id and at least one valid role required']); break; }
            // Determine primary role by hierarchy; remainder go into extra_roles
            $hierarchy = ['admin','coordinator','cultural_coordinator','sports_coordinator','membership_coordinator','social_media','member','non_member'];
            usort($roles, fn($a,$b) => array_search($a,$hierarchy) <=> array_search($b,$hierarchy));
            $primaryRole = $roles[0];
            $extraRoles  = array_slice($roles, 1);
            $db->prepare("UPDATE users SET role=?, extra_roles=? WHERE id=?")->execute([$primaryRole, json_encode($extraRoles), $id]);
            echo json_encode(['success'=>true,'role'=>$primaryRole,'extra_roles'=>$extraRoles]);
            break;

        case 'update_user':
            requireMethod('POST');
            requireRole('admin');
            $db = getDB();
            $upd = $db->prepare(
                "UPDATE users SET first_name=?,last_name=?,email=?,role=?,extra_roles=?,membership_expiry=?,membership_status=?,phone=? WHERE id=?"
            );
            // If extra_roles provided (array), validate and re-sort just like assign_role
            $allowedRoles = ['non_member','member','coordinator','social_media','membership_coordinator','cultural_coordinator','sports_coordinator','admin'];
            $updRoles = [];
            if (!empty($input['extra_roles']) && is_array($input['extra_roles'])) {
                foreach ($input['extra_roles'] as $r) { if (in_array($r,$allowedRoles,true)) $updRoles[]=$r; }
            }
            $upd->execute([
                trim($input['first_name'] ?? ''),
                trim($input['last_name']  ?? ''),
                strtolower(trim($input['email'] ?? '')),
                in_array($input['role']??'',$allowedRoles,true) ? $input['role'] : 'non_member',
                json_encode($updRoles),
                $input['membership_expiry'] ?? null,
                $input['membership_status'] ?? 'none',
                trim($input['phone'] ?? ''),
                (int)($input['id'] ?? 0),
            ]);
            if (!empty($input['new_password'])) {
                $db->prepare("UPDATE users SET password=? WHERE id=?")->execute([
                    password_hash($input['new_password'], PASSWORD_DEFAULT), (int)$input['id']
                ]);
            }
            echo json_encode(['success' => true]);
            break;

        case 'delete_user':
            requireMethod('POST');
            requireRole('admin');
            $uid = (int)($input['id'] ?? 0);
            $me  = getCurrentUser();
            if ($uid === (int)$me['id']) { echo json_encode(['success'=>false,'error'=>'Cannot delete yourself']); break; }
            $db = getDB();
            $db->prepare("DELETE FROM tickets WHERE user_id=?")->execute([$uid]);
            $db->prepare("DELETE FROM users WHERE id=?")->execute([$uid]);
            echo json_encode(['success' => true]);
            break;

        case 'get_all_tickets':
            requireRole('admin');
            $db   = getDB();
            $rows = $db->query(
                "SELECT t.*, e.title AS event_title, e.event_date,
                        u.first_name||' '||u.last_name AS member_name, u.email AS member_email, u.membership_number
                 FROM tickets t
                 JOIN events e ON t.event_id=e.id
                 JOIN users  u ON t.user_id=u.id
                 ORDER BY t.purchase_date DESC"
            )->fetchAll();
            echo json_encode(['success' => true, 'tickets' => $rows]);
            break;

        case 'update_ticket_status':
            requireMethod('POST');
            requireRole('admin');
            $db = getDB();
            $db->prepare("UPDATE tickets SET status=? WHERE id=?")->execute([
                $input['status'] ?? 'confirmed',
                (int)($input['id'] ?? 0),
            ]);
            echo json_encode(['success' => true]);
            break;

        // ── ZEFFY INTEGRATION ────────────────────────────────────────────────

        case 'zeffy_verify_membership':
            // Zeffy purchases flow in automatically via the Zapier webhook. This
            // endpoint lets a member re-check immediately: it optionally records
            // an alternate Zeffy email, then applies any pending payments held
            // for their email(s).
            requireMethod('POST');
            $user = requireLogin();
            $db   = getDB();

            $altEmail = trim($input['zeffy_email'] ?? '');
            if ($altEmail !== '') {
                if (!filter_var($altEmail, FILTER_VALIDATE_EMAIL)) {
                    echo json_encode(['success' => false, 'error' => 'Please enter a valid email address.']);
                    break;
                }
                if (strtolower($altEmail) !== strtolower($user['email'])) {
                    $db->prepare("UPDATE users SET zeffy_email=? WHERE id=?")->execute([$altEmail, $user['id']]);
                }
            }

            $applied = zeffyReconcilePending($db, (int)$user['id']);

            // Re-read membership status after reconciliation.
            $st = $db->prepare("SELECT membership_status, membership_expiry FROM users WHERE id=?");
            $st->execute([$user['id']]);
            $info = $st->fetch();

            if ($applied > 0 || ($info['membership_status'] ?? 'none') === 'active') {
                echo json_encode([
                    'success'   => true,
                    'message'   => $applied > 0
                        ? 'Found your Zeffy purchase — your membership is now active!'
                        : 'Your membership is already active.',
                    'expires_at' => $info['membership_expiry'] ?? null,
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error'   => "We haven't received a Zeffy purchase for your email yet. Purchases sync automatically within a minute of paying — if you used a different email on Zeffy, enter it above and try again.",
                ]);
            }
            break;

        case 'zeffy_sync_tickets':
            // Re-apply any pending Zeffy purchases for this user, then return cache.
            requireMethod('POST');
            $user = requireLogin();
            $db   = getDB();

            $altEmail = trim($input['zeffy_email'] ?? '');
            if ($altEmail !== '' && filter_var($altEmail, FILTER_VALIDATE_EMAIL)
                && strtolower($altEmail) !== strtolower($user['email'])) {
                $db->prepare("UPDATE users SET zeffy_email=? WHERE id=?")->execute([$altEmail, $user['id']]);
            }

            zeffyReconcilePending($db, (int)$user['id']);

            // Always stamp the sync time on every click, even if nothing changed.
            $syncedAt = date('Y-m-d H:i:s');
            $db->prepare("UPDATE users SET zeffy_synced_at=? WHERE id=?")->execute([$syncedAt, $user['id']]);

            $stmt = $db->prepare("SELECT * FROM zeffy_purchases WHERE user_id=? ORDER BY bought_at DESC");
            $stmt->execute([$user['id']]);
            $purchases = $stmt->fetchAll();
            echo json_encode(['success' => true, 'purchases' => $purchases, 'count' => count($purchases), 'synced_at' => $syncedAt]);
            break;

        case 'zeffy_get_tickets':
            // Return cached Zeffy purchases for the current user
            $user = requireLogin();
            $db   = getDB();
            $stmt = $db->prepare("SELECT * FROM zeffy_purchases WHERE user_id=? ORDER BY bought_at DESC");
            $stmt->execute([$user['id']]);
            $rows = $stmt->fetchAll();

            $userRow = $db->prepare("SELECT zeffy_synced_at, zeffy_email FROM users WHERE id=?")->execute([$user['id']]) ? null : null;
            $ur2 = $db->prepare("SELECT zeffy_synced_at, zeffy_email FROM users WHERE id=?");
            $ur2->execute([$user['id']]);
            $uInfo = $ur2->fetch();

            echo json_encode([
                'success'   => true,
                'purchases' => $rows,
                'synced_at' => $uInfo['zeffy_synced_at'] ?? null,
                'zeffy_email' => $uInfo['zeffy_email'] ?? null,
            ]);
            break;

        case 'zeffy_save_settings':
            requireMethod('POST');
            requireRole('admin');
            $formSlug = trim($input['zeffy_membership_form_slug'] ?? '');
            putSetting('zeffy_membership_form_slug', $formSlug);
            echo json_encode(['success' => true]);
            break;

        case 'zeffy_get_settings':
            // Returns the Zapier webhook details + live receive stats for the admin.
            requireRole('admin');
            $secret = zeffyWebhookSecret();
            $base   = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
                      . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
            $dir    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
            $webhookUrl = $base . $dir . '/api.php?action=zeffy_webhook&token=' . $secret;

            $db = getDB();
            $pending = (int)$db->query("SELECT COUNT(*) c FROM zeffy_pending_payments")->fetch()['c'];

            echo json_encode([
                'success'      => true,
                'webhook_url'  => $webhookUrl,
                'form_slug'    => getSetting('zeffy_membership_form_slug', ZEFFY_MEMBERSHIP_FORM_SLUG),
                'last_received' => getSetting('zeffy_webhook_last_at', ''),
                'total_received' => (int)getSetting('zeffy_webhook_count', '0'),
                'pending_count' => $pending,
                'last_payload' => getSetting('zeffy_webhook_last_payload', ''),
            ]);
            break;

        case 'zeffy_regenerate_webhook':
            // Rotate the webhook secret (invalidates the old URL).
            requireMethod('POST');
            requireRole('admin');
            putSetting('zeffy_webhook_secret', bin2hex(random_bytes(24)));
            echo json_encode(['success' => true]);
            break;

        case 'zeffy_reconcile_all':
            // Admin: re-apply every held pending payment against existing accounts.
            requireMethod('POST');
            requireRole('admin');
            $db = getDB();
            $emails = $db->query("SELECT DISTINCT lower(email) e FROM zeffy_pending_payments")->fetchAll();
            $applied = 0;
            foreach ($emails as $row) {
                $u = zeffyFindUserByEmail($db, $row['e']);
                if ($u) $applied += zeffyReconcilePending($db, (int)$u['id']);
            }
            // Also repair the plan/tier on existing memberships (fixes any that
            // were activated before tier resolution was corrected).
            $repaired  = zeffyResyncMembershipPlans($db);
            $remaining = (int)$db->query("SELECT COUNT(*) c FROM zeffy_pending_payments")->fetch()['c'];
            echo json_encode(['success' => true, 'applied' => $applied, 'plans_fixed' => $repaired, 'pending_remaining' => $remaining]);
            break;

        case 'zeffy_import_csv':
            // Admin uploads an export of purchases; each row is matched to an
            // account by email. Memberships activate; other purchases (event
            // tickets, etc.) are recorded on the buyer's account.
            requireMethod('POST');
            requireRole('admin');
            $db = getDB();

            if (empty($_FILES['csv']) || ($_FILES['csv']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                echo json_encode(['success' => false, 'error' => 'No file uploaded. Choose a CSV file and try again.']);
                break;
            }
            $fh = fopen($_FILES['csv']['tmp_name'], 'r');
            if (!$fh) {
                echo json_encode(['success' => false, 'error' => 'Could not read the uploaded file.']);
                break;
            }

            $headers = fgetcsv($fh, 0, ',', '"', '\\');
            // Strip a UTF-8 BOM from the first header if present.
            if ($headers && isset($headers[0])) $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
            if (!$headers) {
                fclose($fh);
                echo json_encode(['success' => false, 'error' => 'The file appears to be empty.']);
                break;
            }

            $stats = ['rows' => 0, 'matched' => 0, 'activated' => 0, 'tickets' => 0, 'pending' => 0, 'skipped' => 0];
            while (($data = fgetcsv($fh, 0, ',', '"', '\\')) !== false) {
                // Skip fully blank lines.
                if (count(array_filter($data, fn($v) => trim((string)$v) !== '')) === 0) continue;

                $assoc = [];
                foreach ($headers as $i => $h) { $assoc[$h] = $data[$i] ?? ''; }

                $norm  = zeffyCsvRowToNorm($assoc);
                $stats['rows']++;

                $email = strtolower(trim($norm['buyer_email'] ?? ''));
                if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $stats['skipped']++;
                    continue;
                }

                $u = zeffyFindUserByEmail($db, $email);
                if ($u) {
                    $stats['matched']++;
                    if (zeffyApplyPaymentToUser($db, (int)$u['id'], $norm)) $stats['activated']++;
                    else $stats['tickets']++;
                } else {
                    zeffyStorePending($db, $email, $norm);
                    $stats['pending']++;
                }
            }
            fclose($fh);

            putSetting('zeffy_webhook_last_at', date('Y-m-d H:i:s'));

            echo json_encode(['success' => true] + $stats);
            break;

        case 'zeffy_update_user_email':
            requireMethod('POST');
            $user  = requireLogin();
            $email = trim($input['zeffy_email'] ?? '');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'error' => 'Invalid email address.']);
                break;
            }
            $db = getDB();
            $db->prepare("UPDATE users SET zeffy_email=? WHERE id=?")->execute([$email, $user['id']]);
            echo json_encode(['success' => true]);
            break;

        // ── MEMBERSHIP MANAGEMENT ────────────────────────────────────────────
        case 'get_my_membership':
            $user = requireLogin();
            $db   = getDB();
            $stmt = $db->prepare(
                "SELECT m.*, mt.icon FROM memberships m
                 LEFT JOIN membership_tiers mt ON m.tier_id=mt.id
                 WHERE m.user_id=? ORDER BY m.created_at DESC LIMIT 1"
            );
            $stmt->execute([$user['id']]);
            $mem = $stmt->fetch();
            echo json_encode(['success' => true, 'membership' => $mem ?: null, 'status' => $user['membership_status'] ?? 'none']);
            break;

        case 'cancel_membership':
            requireMethod('POST');
            $user = requireLogin();
            if (!isActiveMember($user)) { echo json_encode(['success'=>false,'error'=>'No active membership']); break; }
            $db = getDB();
            // Mark the most recent active membership as cancelled (expires at period end)
            $db->prepare("UPDATE memberships SET is_recurring=0, status='cancelled' WHERE user_id=? AND status='active'")
               ->execute([$user['id']]);
            echo json_encode(['success' => true, 'message' => 'Membership will not renew. Access continues until expiry.']);
            break;

        case 'toggle_membership_recurring':
            requireMethod('POST');
            $user = requireLogin();
            if (!isActiveMember($user)) { echo json_encode(['success'=>false,'error'=>'No active membership']); break; }
            $db   = getDB();
            $val  = (int)($input['is_recurring'] ?? 0);
            $db->prepare("UPDATE memberships SET is_recurring=? WHERE user_id=? AND status='active'")
               ->execute([$val, $user['id']]);
            echo json_encode(['success' => true]);
            break;

        case 'get_all_memberships':
            requireRole('admin');
            $db   = getDB();
            $rows = $db->query(
                "SELECT m.*, u.first_name||' '||u.last_name AS member_name, u.email AS member_email,
                        u.membership_number, u.membership_status
                 FROM memberships m
                 JOIN users u ON m.user_id=u.id
                 ORDER BY m.created_at DESC"
            )->fetchAll();
            echo json_encode(['success' => true, 'memberships' => $rows]);
            break;

        case 'activate_membership':
            requireMethod('POST');
            requireRole('admin');
            $db        = getDB();
            $uid       = (int)($input['user_id'] ?? 0);
            $tierId    = (int)($input['tier_id'] ?? 0);
            $tierName  = trim($input['tier_name'] ?? '');
            $pricePaid = (float)($input['price_paid'] ?? 0);
            $startedAt = $input['started_at'] ?? date('Y-m-d');
            $expiresAt = $input['expires_at'] ?? date('Y-m-d', strtotime('+1 year'));
            $recurring = (int)($input['is_recurring'] ?? 0);
            $notes     = trim($input['notes'] ?? '');

            if (!$uid) { echo json_encode(['success'=>false,'error'=>'user_id required']); break; }

            // Close any existing active memberships for this user
            $db->prepare("UPDATE memberships SET status='superseded' WHERE user_id=? AND status='active'")
               ->execute([$uid]);

            // Insert new membership record
            $db->prepare("INSERT INTO memberships (user_id,tier_id,tier_name,price_paid,started_at,expires_at,is_recurring,status,notes)
                          VALUES (?,?,?,?,?,?,?,'active',?)")
               ->execute([$uid, $tierId ?: null, $tierName, $pricePaid, $startedAt, $expiresAt, $recurring, $notes]);

            // Update user membership_status, expiry, and promote to member role
            $db->prepare("UPDATE users SET role='member', membership_status='active', membership_expiry=? WHERE id=?")
               ->execute([$expiresAt, $uid]);

            echo json_encode(['success' => true]);
            break;

        case 'deactivate_membership':
            requireMethod('POST');
            requireRole('admin');
            $db  = getDB();
            $uid = (int)($input['user_id'] ?? 0);
            if (!$uid) { echo json_encode(['success'=>false,'error'=>'user_id required']); break; }

            $db->prepare("UPDATE memberships SET status='expired' WHERE user_id=? AND status='active'")
               ->execute([$uid]);
            $db->prepare("UPDATE users SET role='non_member', membership_status='expired', membership_expiry=? WHERE id=?")
               ->execute([date('Y-m-d'), $uid]);

            echo json_encode(['success' => true]);
            break;

        // ── FILE UPLOAD ──────────────────────────────────────────────────────
        case 'upload_image':
            requireRole(['admin', 'coordinator']);
            if (empty($_FILES['image'])) {
                echo json_encode(['success'=>false,'error'=>'No file uploaded']); break;
            }
            $result = handleImageUpload($_FILES['image'], $_POST['upload_dir'] ?? 'posts');
            echo json_encode($result);
            break;

        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => "Unknown action: $action"]);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

// ─── Helpers ────────────────────────────────────────────────────────────────

function requireMethod(string $method): void {
    if ($_SERVER['REQUEST_METHOD'] !== $method) {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }
}

function handleImageUpload(array $file, string $subdir = 'posts'): array {
    $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
    $exts    = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success'=>false,'error'=>'Upload failed: error code '.$file['error']];
    }
    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($mime, $allowed)) {
        return ['success'=>false,'error'=>'Only JPEG, PNG, GIF and WebP images allowed.'];
    }
    if ($file['size'] > 25 * 1024 * 1024) {
        return ['success'=>false,'error'=>'Image must be under 25 MB.'];
    }

    $dir = UPLOADS_PATH . $subdir . '/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $filename = uniqid('img_', true) . '.' . $exts[$mime];
    $dest     = $dir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['success'=>false,'error'=>'Could not save file.'];
    }

    return [
        'success'  => true,
        'path'     => UPLOADS_URL . $subdir . '/' . $filename,
        'filename' => $filename,
    ];
}

/**
 * Upload photo to Cloudinary (if configured) or local storage.
 */
function uploadToCloudinaryOrLocal(array $file, string $eventId): array {
    // Validate file type and size
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    $exts    = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success'=>false,'error'=>'Upload failed: error code '.$file['error']];
    }
    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($mime, $allowed)) {
        return ['success'=>false,'error'=>'Only JPEG, PNG, and WebP images allowed.'];
    }
    if ($file['size'] > 10 * 1024 * 1024) {
        return ['success'=>false,'error'=>'Image must be under 10 MB.'];
    }

    $cloudName = CLOUDINARY_CLOUD_NAME;
    $apiKey    = CLOUDINARY_API_KEY;
    $apiSecret = CLOUDINARY_API_SECRET;

    if ($cloudName && $apiKey && $apiSecret) {
        // Upload to Cloudinary
        $folder    = CLOUDINARY_FOLDER . '/event_' . $eventId;
        $timestamp = time();
        $sigStr    = "folder={$folder}&timestamp={$timestamp}" . $apiSecret;
        $signature = sha1($sigStr);

        $ch = curl_init("https://api.cloudinary.com/v1_1/{$cloudName}/image/upload");
        $postFields = [
            'file'      => new CURLFile($file['tmp_name'], $mime, $file['name']),
            'api_key'   => $apiKey,
            'timestamp' => $timestamp,
            'folder'    => $folder,
            'signature' => $signature,
        ];
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postFields,
            CURLOPT_TIMEOUT        => 60,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200 && $resp) {
            $data = json_decode($resp, true);
            if (!empty($data['secure_url'])) {
                return [
                    'success'   => true,
                    'url'       => $data['secure_url'],
                    'public_id' => $data['public_id'] ?? '',
                ];
            }
        }
        // Cloudinary failed — return its error message if available
        $errMsg = 'Upload to photo service failed.';
        if ($resp) {
            $errData = json_decode($resp, true);
            if (!empty($errData['error']['message'])) {
                $errMsg = $errData['error']['message'];
            }
        }
        return ['success'=>false,'error'=>$errMsg];
    }

    // Local file storage
    $ext    = $exts[$mime] ?? 'jpg';
    $dir    = UPLOADS_PATH . 'events/' . $eventId . '/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $filename = uniqid('photo_', true) . '.' . $ext;
    $dest     = $dir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['success'=>false,'error'=>'Could not save file.'];
    }

    return [
        'success'   => true,
        'url'       => UPLOADS_URL . 'events/' . $eventId . '/' . $filename,
        'public_id' => '',
    ];
}

/**
 * Delete an image from Cloudinary by public_id.
 */
function _deleteFromCloudinary(string $publicId): bool {
    $cloudName = CLOUDINARY_CLOUD_NAME;
    $apiKey    = CLOUDINARY_API_KEY;
    $apiSecret = CLOUDINARY_API_SECRET;
    if (!$cloudName || !$apiKey || !$apiSecret) return false;

    $timestamp = time();
    $sigStr    = "public_id={$publicId}&timestamp={$timestamp}" . $apiSecret;
    $signature = sha1($sigStr);

    $ch = curl_init("https://api.cloudinary.com/v1_1/{$cloudName}/image/destroy");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => [
            'public_id' => $publicId,
            'api_key'   => $apiKey,
            'timestamp' => $timestamp,
            'signature' => $signature,
        ],
        CURLOPT_TIMEOUT        => 15,
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($resp, true);
    return isset($data['result']) && $data['result'] === 'ok';
}
