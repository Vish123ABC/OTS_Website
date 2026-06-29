<?php
/**
 * zeffy.php — Ottawa Tamil Sangam · Zeffy API Service
 *
 * Zeffy REST API (read-only, Bearer token authentication)
 * Base URL: https://www.zeffy.com/api/v1
 * Docs:     https://www.zeffy.com/api/docs  (login required)
 *
 * SETUP: Get your API key from Zeffy Dashboard → Settings → Organization → Integrations
 */

class ZeffyAPI {

    private string $apiKey;
    private const BASE = 'https://www.zeffy.com/api/v1';

    public function __construct(string $apiKey) {
        $this->apiKey = $apiKey;
    }

    // ── Core HTTP ──────────────────────────────────────────────────────────

    private function get(string $path, array $params = []): array {
        if (!$this->apiKey) {
            return ['success' => false, 'error' => 'Zeffy API key is not configured. Go to Admin → Memberships → Zeffy Settings.'];
        }

        $url = self::BASE . $path;
        if ($params) $url .= '?' . http_build_query($params);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->apiKey,
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err) {
            return ['success' => false, 'error' => "Connection error: $err"];
        }
        if ($code === 401) {
            return ['success' => false, 'error' => 'Invalid API key — regenerate it in Zeffy Dashboard → Settings → Organization → Integrations.'];
        }
        if ($code === 403) {
            return ['success' => false, 'error' => 'API key does not have permission for this resource.'];
        }
        if ($code === 429) {
            return ['success' => false, 'error' => 'Zeffy rate limit reached (100 req/min). Try again shortly.'];
        }
        if ($code !== 200) {
            error_log("Zeffy API HTTP $code for $url: $body");
            return ['success' => false, 'error' => "Zeffy API returned HTTP $code", 'raw' => substr($body, 0, 500)];
        }

        $data = json_decode($body, true);
        if ($data === null) {
            error_log("Zeffy API invalid JSON for $url: $body");
            return ['success' => false, 'error' => 'Unexpected response from Zeffy API', 'raw' => substr($body, 0, 500)];
        }

        return ['success' => true, 'data' => $data];
    }

    // ── Public endpoints ───────────────────────────────────────────────────

    /** Test connectivity — returns campaign list or error */
    public function testConnection(): array {
        return $this->get('/campaigns', ['limit' => 1]);
    }

    /** Fetch all campaigns/forms this org has */
    public function getCampaigns(): array {
        return $this->get('/campaigns');
    }

    /** Fetch payments, optionally filtered by email */
    public function getPayments(array $filters = []): array {
        return $this->get('/payments', $filters);
    }

    /** Fetch all payments made by a specific email address */
    public function getPaymentsByEmail(string $email): array {
        // Try 'email' param first; Zeffy may also use 'buyer_email' or 'contact_email'
        $r = $this->get('/payments', ['email' => $email]);
        if ($r['success']) return $r;
        // Fallback: some versions use different param names
        return $this->get('/payments', ['buyer_email' => $email]);
    }

    /** Fetch contacts, optionally searching by email */
    public function getContactByEmail(string $email): array {
        $r = $this->get('/contacts', ['email' => $email]);
        if ($r['success']) return $r;
        return $this->get('/contacts', ['search' => $email]);
    }

    // ── Business logic ─────────────────────────────────────────────────────

    /**
     * Check if an email address has purchased the OTS membership on Zeffy.
     * Returns: ['found' => bool, 'payment' => array|null, 'error' => string|null]
     */
    public function checkMembershipPurchase(string $email, string $membershipFormSlug): array {
        $result = $this->getPaymentsByEmail($email);
        if (!$result['success']) {
            return ['found' => false, 'payment' => null, 'error' => $result['error']];
        }

        $payments = self::extractList($result['data']);
        if ($payments === null) {
            // Could not parse response — log raw for admin debugging
            error_log('Zeffy checkMembershipPurchase: unexpected data structure for email=' . $email . ': ' . json_encode($result['data']));
            return ['found' => false, 'payment' => null, 'error' => 'Zeffy returned an unexpected data format. Check Admin → Zeffy Debug.', 'raw' => $result['data']];
        }

        foreach ($payments as $p) {
            if (self::isCompletedPayment($p) && self::matchesForm($p, $membershipFormSlug)) {
                return ['found' => true, 'payment' => $p, 'error' => null];
            }
        }

        return ['found' => false, 'payment' => null, 'error' => null];
    }

    /**
     * Get all Zeffy purchases for a user, formatted as a clean list.
     * Returns ['success' => bool, 'purchases' => array, 'error' => string|null]
     */
    public function getUserPurchases(string $email): array {
        $result = $this->getPaymentsByEmail($email);
        if (!$result['success']) {
            return ['success' => false, 'purchases' => [], 'error' => $result['error']];
        }

        $payments = self::extractList($result['data']);
        if ($payments === null) {
            return ['success' => false, 'purchases' => [], 'error' => 'Unexpected Zeffy response format.'];
        }

        $purchases = array_map([self::class, 'normalizePayment'], $payments);
        return ['success' => true, 'purchases' => $purchases, 'error' => null];
    }

    // ── Static helpers ─────────────────────────────────────────────────────

    /**
     * Extract array of payment objects from various response shapes:
     *   - Direct array: [{...}, {...}]
     *   - Wrapped:      {"data": [{...}]}
     *   - Paginated:    {"data": {"data": [{...}], "total": N}}
     */
    private static function extractList($data): ?array {
        if (is_array($data) && (empty($data) || isset($data[0]))) return $data;
        if (isset($data['data'])) {
            $inner = $data['data'];
            if (is_array($inner) && (empty($inner) || isset($inner[0]))) return $inner;
            if (isset($inner['data']) && is_array($inner['data'])) return $inner['data'];
        }
        if (isset($data['payments'])) return $data['payments'];
        if (isset($data['results']))  return $data['results'];
        return null;
    }

    /** Check if a payment is in a "completed/paid" state */
    private static function isCompletedPayment(array $p): bool {
        $status = strtolower(
            $p['status'] ?? $p['payment_status'] ?? $p['paymentStatus'] ?? $p['state'] ?? 'completed'
        );
        return in_array($status, ['completed', 'paid', 'success', 'succeeded', 'captured', ''], true);
    }

    /** Check if a payment matches the membership form by slug/title */
    private static function matchesForm(array $p, string $slug): bool {
        // Try every field that might hold the form identifier
        $candidates = [
            $p['form_slug']     ?? '',
            $p['formSlug']      ?? '',
            $p['campaign_slug'] ?? '',
            $p['campaignSlug']  ?? '',
            $p['form_id']       ?? '',
            $p['formId']        ?? '',
            $p['campaign_id']   ?? '',
            $p['campaignId']    ?? '',
            $p['form_title']    ?? '',
            $p['formTitle']     ?? '',
            $p['campaign_name'] ?? '',
            $p['campaignName']  ?? '',
        ];
        foreach ($candidates as $c) {
            if ($c && (
                str_contains((string)$c, $slug) ||
                str_contains($slug, (string)$c) ||
                str_contains(strtolower((string)$c), 'membership')
            )) return true;
        }
        return false;
    }

    /** Normalize a raw Zeffy payment into a clean consistent array */
    public static function normalizePayment(array $p): array {
        return [
            'zeffy_id'    => $p['id']               ?? $p['payment_id']   ?? $p['paymentId']  ?? '',
            'form_title'  => $p['form_title']        ?? $p['formTitle']    ?? $p['campaign_name'] ?? $p['campaignName'] ?? 'Unknown Event',
            'form_slug'   => $p['form_slug']         ?? $p['formSlug']     ?? $p['campaign_slug'] ?? $p['campaignSlug'] ?? '',
            'ticket_type' => $p['ticket_type']       ?? $p['ticketType']   ?? $p['ticket_name'] ?? $p['ticketName'] ?? 'General',
            'quantity'    => (int)($p['quantity']    ?? $p['qty']          ?? 1),
            'amount'      => (float)($p['amount']    ?? $p['total']        ?? $p['total_amount'] ?? 0),
            'currency'    => strtoupper($p['currency'] ?? 'CAD'),
            'status'      => $p['status']            ?? $p['payment_status'] ?? $p['paymentStatus'] ?? 'completed',
            'bought_at'   => $p['created_at']        ?? $p['createdAt']    ?? $p['purchased_at'] ?? $p['purchasedAt'] ?? $p['date'] ?? null,
            'buyer_name'  => ($p['first_name'] ?? $p['firstName'] ?? '') . ' ' . ($p['last_name'] ?? $p['lastName'] ?? ''),
            'buyer_email' => $p['email']             ?? $p['buyer_email']  ?? $p['buyerEmail']   ?? '',
            '_raw'        => $p,
        ];
    }
}

/** Get the active ZeffyAPI instance (reads key from settings table) */
function getZeffy(): ZeffyAPI {
    static $instance = null;
    if ($instance !== null) return $instance;
    $key = getSetting('zeffy_api_key', ZEFFY_API_KEY);
    $instance = new ZeffyAPI($key);
    return $instance;
}

// ─────────────────────────────────────────────────────────────────────────────
//  Webhook ingestion (Zapier → OTS)
//
//  Zeffy has no public API, so live membership data arrives by a Zapier Zap
//  ("New Zeffy payment → POST to this webhook"). These helpers normalize the
//  incoming payload, decide whether it's a membership purchase, and activate
//  the matching user — or stash it as pending until that email registers.
// ─────────────────────────────────────────────────────────────────────────────

/** True if a normalized payment looks like the OTS membership purchase. */
function zeffyIsMembershipPayment(array $norm): bool {
    $slug = strtolower(trim(getSetting('zeffy_membership_form_slug', ZEFFY_MEMBERSHIP_FORM_SLUG)));
    $haystacks = [strtolower($norm['form_slug'] ?? ''), strtolower($norm['form_title'] ?? '')];
    foreach ($haystacks as $h) {
        if ($h === '') continue;
        if (str_contains($h, 'membership')) return true;
        if ($slug && (str_contains($h, $slug) || str_contains($slug, $h))) return true;
    }
    return false;
}

/** Find a user by their OTS email or their recorded Zeffy email (case-insensitive). */
function zeffyFindUserByEmail(PDO $db, string $email): ?array {
    $email = strtolower(trim($email));
    if ($email === '') return null;
    $stmt = $db->prepare("SELECT * FROM users WHERE lower(email)=? OR lower(zeffy_email)=? LIMIT 1");
    $stmt->execute([$email, $email]);
    return $stmt->fetch() ?: null;
}

/**
 * Stable identifier for a payment, used to avoid duplicate rows. Uses the real
 * Zeffy payment id when present; otherwise derives a deterministic key from the
 * purchase details so re-importing the same export doesn't create duplicates.
 */
function zeffyDedupeId(array $norm): string {
    if (!empty($norm['zeffy_id'])) return (string)$norm['zeffy_id'];
    $date = !empty($norm['bought_at']) ? date('Y-m-d', strtotime((string)$norm['bought_at'])) : '';
    return 'auto-' . md5(implode('|', [
        strtolower(trim($norm['buyer_email'] ?? '')),
        (string)(float)($norm['amount'] ?? 0),
        $date,
        trim((string)($norm['form_title'] ?? '')),
        (string)(int)($norm['quantity'] ?? 0),
    ]));
}

/**
 * Work out which membership plan a purchase is for.
 *
 * The amount paid is often discounted (early-bird / promo), so matching the
 * tier by amount picks the wrong plan. Zeffy records the real plan and its
 * regular price in the line-item text, e.g. "1x Family of 4 (Regular Price $45)".
 * We use that when available and fall back to the amount paid otherwise.
 *
 * Returns ['id' => ?int tier_id, 'name' => string plan label].
 */
function zeffyResolveTier(PDO $db, array $norm): array {
    // Plan/line-item text: prefer the normalized ticket_type, else dig in raw data.
    $planText = trim((string)($norm['ticket_type'] ?? ''));
    if ($planText === '' && !empty($norm['_raw']) && is_array($norm['_raw'])) {
        foreach (['Details', 'details', 'Rate', 'Ticket', 'Ticket Type', 'Product'] as $k) {
            if (!empty($norm['_raw'][$k])) { $planText = trim((string)$norm['_raw'][$k]); break; }
        }
    }

    // Price to match a tier on: the regular price from the text if present,
    // otherwise any $-amount in the text, otherwise the amount actually paid.
    $matchPrice = (float)($norm['amount'] ?? 0);
    if ($planText !== '') {
        if (preg_match('/(?:regular\s*price|price)\D*([0-9]+(?:\.[0-9]+)?)/i', $planText, $m)) {
            $matchPrice = (float)$m[1];
        } elseif (preg_match('/\$\s*([0-9]+(?:\.[0-9]+)?)/', $planText, $m)) {
            $matchPrice = (float)$m[1];
        }
    }

    // Human plan label: drop a leading "Nx " quantity and a trailing "(…)" note.
    $label = preg_replace('/^\s*\d+\s*x\s*/i', '', $planText);
    $label = trim((string)preg_replace('/\s*\(.*?\)\s*$/', '', $label));

    // Closest tier by price.
    $best = null; $bestDiff = null;
    foreach ($db->query("SELECT id,name,price FROM membership_tiers")->fetchAll() as $t) {
        $diff = abs((float)$t['price'] - $matchPrice);
        if ($bestDiff === null || $diff < $bestDiff) { $bestDiff = $diff; $best = $t; }
    }

    return [
        'id'   => $best['id'] ?? null,
        'name' => $label !== '' ? $label : ($best['name'] ?? 'Member'),
    ];
}

/**
 * Record a normalized payment against a known user: cache it in zeffy_purchases
 * and, if it's a membership purchase, activate/extend their membership.
 * Returns true if the payment activated a membership.
 */
function zeffyApplyPaymentToUser(PDO $db, int $userId, array $norm): bool {
    // Cache the purchase, replacing any prior copy of the same payment.
    $zid = zeffyDedupeId($norm);
    $db->prepare("DELETE FROM zeffy_purchases WHERE user_id=? AND zeffy_id=?")
       ->execute([$userId, $zid]);
    $db->prepare("INSERT INTO zeffy_purchases (user_id,zeffy_id,form_title,form_slug,ticket_type,quantity,amount,currency,status,bought_at,raw_data)
                  VALUES (?,?,?,?,?,?,?,?,?,?,?)")
       ->execute([
           $userId, $zid, $norm['form_title'], $norm['form_slug'],
           $norm['ticket_type'], $norm['quantity'], $norm['amount'], $norm['currency'],
           $norm['status'],
           $norm['bought_at'] ? date('Y-m-d H:i:s', strtotime($norm['bought_at'])) : null,
           json_encode($norm['_raw'] ?? $norm),
       ]);

    if (!zeffyIsMembershipPayment($norm)) {
        $db->prepare("UPDATE users SET zeffy_synced_at=? WHERE id=?")
           ->execute([date('Y-m-d H:i:s'), $userId]);
        return false;
    }

    // Activate membership: 1 year from the payment date.
    $boughtAt  = $norm['bought_at'] ? date('Y-m-d', strtotime($norm['bought_at'])) : date('Y-m-d');
    $expiresAt = date('Y-m-d', strtotime($boughtAt . ' +1 year'));

    $db->prepare("UPDATE memberships SET status='superseded' WHERE user_id=? AND status='active'")->execute([$userId]);

    $tier = zeffyResolveTier($db, $norm);

    $db->prepare("INSERT INTO memberships (user_id,tier_id,tier_name,price_paid,started_at,expires_at,status,notes)
                  VALUES (?,?,?,?,?,?,'active','Activated via Zeffy')")
       ->execute([$userId, $tier['id'], $tier['name'], $norm['amount'], $boughtAt, $expiresAt]);

    $db->prepare("UPDATE users SET role=CASE WHEN role='non_member' THEN 'member' ELSE role END,
                  membership_status='active', membership_expiry=?, zeffy_verified=1, zeffy_synced_at=? WHERE id=?")
       ->execute([$expiresAt, date('Y-m-d H:i:s'), $userId]);

    return true;
}

/**
 * Re-resolve the plan/tier of every active membership from its stored Zeffy
 * purchase details. Repairs records activated before tier resolution improved
 * (e.g. plans matched on a discounted amount). Returns the number corrected.
 */
function zeffyResyncMembershipPlans(PDO $db): int {
    $fixed = 0;
    $ms = $db->query("SELECT id, user_id, tier_id, tier_name FROM memberships WHERE status='active'")->fetchAll();
    $find = $db->prepare("SELECT raw_data, amount, ticket_type FROM zeffy_purchases
                          WHERE user_id=? AND lower(form_title) LIKE '%membership%'
                          ORDER BY bought_at DESC LIMIT 1");
    foreach ($ms as $m) {
        $find->execute([$m['user_id']]);
        $row = $find->fetch();
        if (!$row) continue; // no Zeffy source (e.g. manually activated) — leave as is
        $norm = [
            'ticket_type' => $row['ticket_type'],
            'amount'      => $row['amount'],
            '_raw'        => json_decode($row['raw_data'] ?: '{}', true) ?: [],
        ];
        $tier = zeffyResolveTier($db, $norm);
        if ((string)$tier['name'] !== (string)$m['tier_name'] || (int)$tier['id'] !== (int)$m['tier_id']) {
            $db->prepare("UPDATE memberships SET tier_id=?, tier_name=? WHERE id=?")
               ->execute([$tier['id'], $tier['name'], $m['id']]);
            $fixed++;
        }
    }
    return $fixed;
}

/** Stash a payment we can't match to a user yet, for later reconciliation. */
function zeffyStorePending(PDO $db, string $email, array $norm): void {
    $email = strtolower(trim($email));
    $zid   = zeffyDedupeId($norm + ['buyer_email' => $email]);
    // Replace any prior copy of the same payment (prevents re-import duplicates).
    $db->prepare("DELETE FROM zeffy_pending_payments WHERE email=? AND zeffy_id=?")
       ->execute([$email, $zid]);
    $db->prepare("INSERT INTO zeffy_pending_payments (email,zeffy_id,form_title,form_slug,ticket_type,quantity,amount,currency,status,is_membership,bought_at,raw_data)
                  VALUES (?,?,?,?,?,?,?,?,?,?,?,?)")
       ->execute([
           $email, $zid, $norm['form_title'], $norm['form_slug'],
           $norm['ticket_type'], $norm['quantity'], $norm['amount'], $norm['currency'],
           $norm['status'], zeffyIsMembershipPayment($norm) ? 1 : 0,
           $norm['bought_at'] ? date('Y-m-d H:i:s', strtotime($norm['bought_at'])) : null,
           json_encode($norm['_raw'] ?? $norm),
       ]);
}

/**
 * Apply any pending Zeffy payments matching this user's email(s).
 * Called on login/registration so purchases made before signup take effect.
 * Returns the number of pending payments applied.
 */
function zeffyReconcilePending(PDO $db, int $userId): int {
    $u = $db->prepare("SELECT email, zeffy_email FROM users WHERE id=?");
    $u->execute([$userId]);
    $row = $u->fetch();
    if (!$row) return 0;

    $emails = array_filter([strtolower(trim($row['email'] ?? '')), strtolower(trim($row['zeffy_email'] ?? ''))]);
    if (!$emails) return 0;

    $placeholders = implode(',', array_fill(0, count($emails), '?'));
    $stmt = $db->prepare("SELECT * FROM zeffy_pending_payments WHERE lower(email) IN ($placeholders)");
    $stmt->execute(array_values($emails));
    $pending = $stmt->fetchAll();
    if (!$pending) return 0;

    $applied = 0;
    foreach ($pending as $p) {
        $norm = [
            'zeffy_id'    => $p['zeffy_id'],
            'form_title'  => $p['form_title'],
            'form_slug'   => $p['form_slug'],
            'ticket_type' => $p['ticket_type'],
            'quantity'    => (int)$p['quantity'],
            'amount'      => (float)$p['amount'],
            'currency'    => $p['currency'],
            'status'      => $p['status'],
            'bought_at'   => $p['bought_at'],
            '_raw'        => json_decode($p['raw_data'] ?: '{}', true) ?: [],
        ];
        zeffyApplyPaymentToUser($db, $userId, $norm);
        $db->prepare("DELETE FROM zeffy_pending_payments WHERE id=?")->execute([$p['id']]);
        $applied++;
    }
    return $applied;
}

/** Generate (once) and return the secret token used to authenticate the webhook. */
function zeffyWebhookSecret(): string {
    $s = getSetting('zeffy_webhook_secret', '');
    if ($s === '') {
        $s = bin2hex(random_bytes(24));
        putSetting('zeffy_webhook_secret', $s);
    }
    return $s;
}

/**
 * Map one CSV row (header => value) onto the normalized payment shape.
 * Tolerant of varied export column names from Zeffy and similar tools.
 */
function zeffyCsvRowToNorm(array $assoc): array {
    // Index values by a simplified header key (lowercase, alphanumerics only).
    $byKey = [];
    foreach ($assoc as $h => $v) {
        $k = preg_replace('/[^a-z0-9]/', '', strtolower((string)$h));
        if ($k !== '') $byKey[$k] = is_string($v) ? trim($v) : $v;
    }
    // Pick the first column whose key exactly matches, then fall back to a contains-match.
    $pick = function (array $cands) use ($byKey) {
        foreach ($cands as $c) {
            if (isset($byKey[$c]) && $byKey[$c] !== '') return $byKey[$c];
        }
        foreach ($byKey as $k => $v) {
            if ($v === '') continue;
            foreach ($cands as $c) {
                if (str_contains($k, $c)) return $v;
            }
        }
        return '';
    };

    $amountRaw = $pick(['amount', 'total', 'totalamount', 'grossamount', 'netamount', 'amountpaid', 'paymentamount', 'paid']);
    $amount    = (float)preg_replace('/[^0-9.\-]/', '', (string)$amountRaw);

    $mapped = [
        'id'          => $pick(['paymentid', 'transactionid', 'orderid', 'id', 'reference', 'receiptnumber']),
        'email'       => $pick(['email', 'buyeremail', 'donoremail', 'contactemail', 'emailaddress']),
        'first_name'  => $pick(['firstname', 'first', 'givenname']),
        'last_name'   => $pick(['lastname', 'last', 'surname', 'familyname']),
        'amount'      => $amount,
        'form_title'  => $pick(['formtitle', 'formname', 'campaign', 'campaignname', 'project', 'projectname', 'item', 'itemname', 'product', 'productname']),
        'form_slug'   => $pick(['formslug', 'campaignslug', 'slug']),
        'ticket_type' => $pick(['tickettype', 'rate', 'ratename', 'item', 'itemname', 'product', 'productname', 'tier', 'details']),
        'quantity'    => (int)($pick(['quantity', 'qty', 'numberoftickets', 'tickets']) ?: 1),
        'currency'    => $pick(['currency']) ?: 'CAD',
        'status'      => $pick(['status', 'paymentstatus']) ?: 'completed',
        'created_at'  => $pick(['paymentdate', 'transactiondate', 'purchasedate', 'createdat', 'created', 'datetime', 'date']),
    ];

    $norm = ZeffyAPI::normalizePayment($mapped);
    $norm['_raw'] = $assoc;
    return $norm;
}
