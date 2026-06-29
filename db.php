<?php
/**
 * db.php — Ottawa Tamil Sangam · Database Layer
 * SQLite via PDO — no server config needed, just drop this on any PHP host.
 */

define('DB_PATH',      __DIR__ . '/database/ots.sqlite');
define('UPLOADS_PATH', __DIR__ . '/uploads/');
define('UPLOADS_URL',  'uploads/');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $dir = dirname(DB_PATH);
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');

    _initSchema($pdo);
    _seedDefaults($pdo);
    return $pdo;
}

function _initSchema(PDO $db): void {
    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id                   INTEGER PRIMARY KEY AUTOINCREMENT,
            email                TEXT    UNIQUE NOT NULL,
            password             TEXT    NOT NULL,
            first_name           TEXT    NOT NULL,
            last_name            TEXT    NOT NULL,
            role                 TEXT    NOT NULL DEFAULT 'non_member',
            membership_number    TEXT,
            membership_expiry    DATE,
            phone                TEXT,
            email_verified       INTEGER NOT NULL DEFAULT 0,
            verification_token   TEXT,
            verification_expires DATETIME,
            reset_token          TEXT,
            reset_expires        DATETIME,
            login_attempts       INTEGER NOT NULL DEFAULT 0,
            locked_until         DATETIME,
            created_at           DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS events (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            title         TEXT    NOT NULL,
            title_tamil   TEXT,
            description   TEXT,
            event_date    DATE,
            event_time    TEXT,
            location      TEXT,
            image_path    TEXT,
            ticket_url    TEXT,
            member_price  REAL,
            regular_price REAL,
            is_upcoming   INTEGER NOT NULL DEFAULT 1,
            is_published  INTEGER NOT NULL DEFAULT 1,
            created_by    INTEGER REFERENCES users(id),
            created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at    DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS event_photos (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            event_id      INTEGER NOT NULL REFERENCES events(id) ON DELETE CASCADE,
            photo_url     TEXT    NOT NULL,
            public_id     TEXT    NOT NULL DEFAULT '',
            caption       TEXT    NOT NULL DEFAULT '',
            display_order INTEGER NOT NULL DEFAULT 0,
            created_at    DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS tickets (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id       INTEGER NOT NULL REFERENCES users(id),
            event_id      INTEGER NOT NULL REFERENCES events(id),
            ticket_type   TEXT    NOT NULL DEFAULT 'regular',
            quantity      INTEGER NOT NULL DEFAULT 1,
            total_price   REAL,
            status        TEXT    NOT NULL DEFAULT 'confirmed',
            purchase_date DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS posts (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            title        TEXT NOT NULL,
            content      TEXT,
            image_path   TEXT,
            post_type    TEXT NOT NULL DEFAULT 'announcement',
            is_published INTEGER NOT NULL DEFAULT 1,
            created_by   INTEGER REFERENCES users(id),
            created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at   DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS site_content (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            section_key TEXT UNIQUE NOT NULL,
            content_html TEXT,
            image_path  TEXT,
            updated_by  INTEGER REFERENCES users(id),
            updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS committee_members (
            id             INTEGER PRIMARY KEY AUTOINCREMENT,
            name_english   TEXT NOT NULL,
            name_tamil     TEXT NOT NULL DEFAULT '',
            role_english   TEXT NOT NULL DEFAULT '',
            role_tamil     TEXT NOT NULL DEFAULT '',
            photo_path     TEXT NOT NULL DEFAULT '',
            display_order  INTEGER NOT NULL DEFAULT 0,
            created_at     DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS membership_tiers (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            name          TEXT    NOT NULL,
            icon          TEXT    NOT NULL DEFAULT 'bi-person',
            price         REAL    NOT NULL DEFAULT 0,
            currency      TEXT    NOT NULL DEFAULT '$',
            description   TEXT    NOT NULL DEFAULT '',
            is_featured   INTEGER NOT NULL DEFAULT 0,
            display_order INTEGER NOT NULL DEFAULT 0
        );

        CREATE TABLE IF NOT EXISTS slideshow_photos (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            photo_path    TEXT    NOT NULL,
            caption       TEXT    NOT NULL DEFAULT '',
            is_active     INTEGER NOT NULL DEFAULT 1,
            display_order INTEGER NOT NULL DEFAULT 0,
            created_at    DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS settings (
            key   TEXT PRIMARY KEY,
            value TEXT NOT NULL DEFAULT ''
        );

        CREATE TABLE IF NOT EXISTS zeffy_purchases (
            id             INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id        INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            zeffy_id       TEXT    NOT NULL DEFAULT '',
            form_title     TEXT    NOT NULL DEFAULT '',
            form_slug      TEXT    NOT NULL DEFAULT '',
            ticket_type    TEXT    NOT NULL DEFAULT '',
            quantity       INTEGER NOT NULL DEFAULT 1,
            amount         REAL    NOT NULL DEFAULT 0,
            currency       TEXT    NOT NULL DEFAULT 'CAD',
            status         TEXT    NOT NULL DEFAULT '',
            bought_at      DATETIME,
            synced_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
            raw_data       TEXT    NOT NULL DEFAULT ''
        );

        CREATE TABLE IF NOT EXISTS memberships (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id      INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            tier_id      INTEGER REFERENCES membership_tiers(id),
            tier_name    TEXT    NOT NULL DEFAULT '',
            price_paid   REAL    NOT NULL DEFAULT 0,
            started_at   DATE    NOT NULL,
            expires_at   DATE    NOT NULL,
            is_recurring INTEGER NOT NULL DEFAULT 0,
            status       TEXT    NOT NULL DEFAULT 'active',
            notes        TEXT    NOT NULL DEFAULT '',
            created_at   DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS event_media (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            event_id      INTEGER NOT NULL REFERENCES events(id) ON DELETE CASCADE,
            type          TEXT    NOT NULL DEFAULT 'link',
            url           TEXT    NOT NULL,
            label         TEXT    NOT NULL DEFAULT '',
            display_order INTEGER NOT NULL DEFAULT 0,
            created_at    DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS benefit_panels (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            icon          TEXT    NOT NULL DEFAULT 'bi-star',
            title         TEXT    NOT NULL,
            content       TEXT    NOT NULL DEFAULT '',
            display_order INTEGER NOT NULL DEFAULT 0
        );

        CREATE TABLE IF NOT EXISTS event_forms (
            id              INTEGER PRIMARY KEY AUTOINCREMENT,
            event_id        INTEGER NOT NULL REFERENCES events(id) ON DELETE CASCADE,
            form_type       TEXT    NOT NULL CHECK(form_type IN ('volunteer','performer')),
            title           TEXT    NOT NULL DEFAULT '',
            description     TEXT    NOT NULL DEFAULT '',
            is_active       INTEGER NOT NULL DEFAULT 1,
            deadline        DATETIME,
            max_submissions INTEGER NOT NULL DEFAULT 0,
            created_by      INTEGER REFERENCES users(id),
            created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(event_id, form_type)
        );

        CREATE TABLE IF NOT EXISTS event_form_questions (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            form_id       INTEGER NOT NULL REFERENCES event_forms(id) ON DELETE CASCADE,
            question_text TEXT    NOT NULL,
            input_type    TEXT    NOT NULL DEFAULT 'text'
                          CHECK(input_type IN ('text','textarea','radio','select','checkbox')),
            options_json  TEXT    NOT NULL DEFAULT '[]',
            word_limit    INTEGER NOT NULL DEFAULT 0,
            char_limit    INTEGER NOT NULL DEFAULT 0,
            is_required   INTEGER NOT NULL DEFAULT 1,
            display_order INTEGER NOT NULL DEFAULT 0
        );

        CREATE TABLE IF NOT EXISTS event_form_submissions (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            form_id      INTEGER NOT NULL REFERENCES event_forms(id) ON DELETE CASCADE,
            user_id      INTEGER REFERENCES users(id),
            guest_name   TEXT    NOT NULL DEFAULT '',
            guest_email  TEXT    NOT NULL DEFAULT '',
            status       TEXT    NOT NULL DEFAULT 'pending'
                         CHECK(status IN ('pending','approved','rejected')),
            admin_notes  TEXT    NOT NULL DEFAULT '',
            submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(form_id, user_id)
        );

        CREATE TABLE IF NOT EXISTS event_form_answers (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            submission_id INTEGER NOT NULL REFERENCES event_form_submissions(id) ON DELETE CASCADE,
            question_id   INTEGER NOT NULL REFERENCES event_form_questions(id) ON DELETE CASCADE,
            answer_text   TEXT    NOT NULL DEFAULT ''
        );

        CREATE TABLE IF NOT EXISTS vision_stats (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            number_text   TEXT    NOT NULL,
            label         TEXT    NOT NULL,
            display_order INTEGER NOT NULL DEFAULT 0
        );

        CREATE TABLE IF NOT EXISTS vision_core_values (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            title         TEXT    NOT NULL,
            description   TEXT    NOT NULL DEFAULT '',
            display_order INTEGER NOT NULL DEFAULT 0
        );

        /* Zeffy payments received via the Zapier webhook that don't yet match a
           registered user. Reconciled into a real membership the moment that
           email registers or logs in. */
        CREATE TABLE IF NOT EXISTS zeffy_pending_payments (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            email       TEXT    NOT NULL,
            zeffy_id    TEXT    NOT NULL DEFAULT '',
            form_title  TEXT    NOT NULL DEFAULT '',
            form_slug   TEXT    NOT NULL DEFAULT '',
            ticket_type TEXT    NOT NULL DEFAULT '',
            quantity    INTEGER NOT NULL DEFAULT 1,
            amount      REAL    NOT NULL DEFAULT 0,
            currency    TEXT    NOT NULL DEFAULT 'CAD',
            status      TEXT    NOT NULL DEFAULT '',
            is_membership INTEGER NOT NULL DEFAULT 0,
            bought_at   DATETIME,
            received_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            raw_data    TEXT    NOT NULL DEFAULT ''
        );

        /* Contact form submissions. Stored so a message is never lost even if
           the email notification fails to send. */
        CREATE TABLE IF NOT EXISTS contact_messages (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            first_name  TEXT    NOT NULL,
            last_name   TEXT    NOT NULL,
            email       TEXT    NOT NULL,
            message     TEXT    NOT NULL,
            is_read     INTEGER NOT NULL DEFAULT 0,
            emailed     INTEGER NOT NULL DEFAULT 0,
            created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // Add zeffy_form_slug to events table
    try { $db->exec("ALTER TABLE events ADD COLUMN zeffy_form_slug TEXT NOT NULL DEFAULT ''"); } catch (PDOException) {}

    // Add new columns to existing users table (for databases created before this schema version)
    $newUserCols = [
        'email_verified'       => 'ALTER TABLE users ADD COLUMN email_verified INTEGER NOT NULL DEFAULT 0',
        'verification_token'   => 'ALTER TABLE users ADD COLUMN verification_token TEXT',
        'verification_expires' => 'ALTER TABLE users ADD COLUMN verification_expires DATETIME',
        'reset_token'          => 'ALTER TABLE users ADD COLUMN reset_token TEXT',
        'reset_expires'        => 'ALTER TABLE users ADD COLUMN reset_expires DATETIME',
        'login_attempts'       => 'ALTER TABLE users ADD COLUMN login_attempts INTEGER NOT NULL DEFAULT 0',
        'locked_until'         => 'ALTER TABLE users ADD COLUMN locked_until DATETIME',
        'membership_status'    => "ALTER TABLE users ADD COLUMN membership_status TEXT NOT NULL DEFAULT 'none'",
        'zeffy_email'          => "ALTER TABLE users ADD COLUMN zeffy_email TEXT",
        'zeffy_synced_at'      => "ALTER TABLE users ADD COLUMN zeffy_synced_at DATETIME",
        'zeffy_verified'       => "ALTER TABLE users ADD COLUMN zeffy_verified INTEGER NOT NULL DEFAULT 0",
    ];
    foreach ($newUserCols as $col => $sql) {
        try { $db->exec($sql); } catch (PDOException) { /* column already exists */ }
    }

    // account_status: 'active' | 'inactive' — soft-removal flag (no hard deletes)
    try { $db->exec("ALTER TABLE users ADD COLUMN account_status TEXT NOT NULL DEFAULT 'active'"); } catch (PDOException) {}
    // extra_roles: JSON array of additional roles beyond the primary role column
    try { $db->exec("ALTER TABLE users ADD COLUMN extra_roles TEXT NOT NULL DEFAULT '[]'"); } catch (PDOException) {}
}

function _seedDefaults(PDO $db): void {
    // Default admin account (change password immediately on first deploy!)
    $cnt = $db->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
    if ($cnt == 0) {
        $hash = password_hash('Admin@OTS2026', PASSWORD_DEFAULT);
        $db->prepare("INSERT INTO users (email,password,first_name,last_name,role,membership_number,membership_status,email_verified)
                      VALUES (?,?,?,?,'admin','OTS-ADMIN','active',1)")
           ->execute(['admin@ottawatamilsangam.ca', $hash, 'Admin', 'OTS']);
    } else {
        $db->exec("UPDATE users SET email_verified=1 WHERE role='admin' AND (email_verified IS NULL OR email_verified=0)");
        $db->exec("UPDATE users SET membership_status='active' WHERE role='admin' AND (membership_status IS NULL OR membership_status='none')");
    }

    // Default coordinator account
    $cnt2 = $db->query("SELECT COUNT(*) FROM users WHERE role='coordinator'")->fetchColumn();
    if ($cnt2 == 0) {
        $hash = password_hash('Coord@OTS2025', PASSWORD_DEFAULT);
        $db->prepare("INSERT INTO users (email,password,first_name,last_name,role,membership_number,membership_status,email_verified)
                      VALUES (?,?,?,?,'coordinator','OTS-COORD','active',1)")
           ->execute(['coordinator@ottawatamilsangam.ca', $hash, 'Coordinator', 'OTS']);
    } else {
        $db->exec("UPDATE users SET email_verified=1 WHERE role='coordinator' AND (email_verified IS NULL OR email_verified=0)");
        $db->exec("UPDATE users SET membership_status='active' WHERE role='coordinator' AND (membership_status IS NULL OR membership_status='none')");
    }

    // Default site content sections
    $sections = [
        // Home
        'home_welcome_tamil'   => '<p>ஆட்டவாவில் வசிக்கும் மற்றும் வரவிருக்கும் தமிழ் மக்கள் அனைவரையும் ஆட்டவா தமிழ்ச்சங்கம் அன்புடன் வரவேற்கிறது. தமிழ் மொழி மற்றும் கலாச்சாரத்தின் மீதான தங்கள் அன்பைப் பகிர்ந்து கொள்ளும் மக்களில் வளர்ந்து வரும் சமூகம் எங்கள் தமிழ் சமூகம்.</p>',
        'home_welcome_english' => '<p>The Ottawa Tamil Sangam welcomes all the Tamil speaking people in Ottawa. Our Tamil community is a growing community of people who share their love of Tamil language and culture. The Ottawa Tamil Sangam conducts various events and fun activities throughout the year to nurture the Tamil language and community.</p>',
        'home_section_heading' => '<h2>வரவேற்கிறோம்</h2>',
        // Vision & Values
        'vision_mission'       => '<p>Ottawa Tamil Sangam is a not-for-profit community organization founded in 2015, dedicated to serving the Tamil-speaking community in the Ottawa region of Ontario, Canada. Our mission is to celebrate Tamil cultural values, support local community members who speak the Tamil language, and preserve and transfer Tamil culture and its core values to the next generation.</p><p>We strive to provide a welcoming environment where our children are exposed to our language, customs, and traditions while embracing Canadian values. The Ottawa Tamil Sangam is a non-partisan and secular organization that believes strongly in maintaining harmonious interfaith connections.</p>',
        'vision_purpose'       => '<p>Ottawa Tamil Sangam provides a forum for the Tamil-speaking community to network and keep in touch with our language and culture. We believe in creating meaningful connections while having fun together and sharing laughter as a community.</p><ul><li>Promote and preserve the ancient Tamil language, arts, and heritage</li><li>Organize cultural, social, and recreational activities throughout the year</li><li>Provide educational programs for children and adults to learn Tamil language and traditions</li><li>Create networking opportunities for Tamil-speaking families in the National Capital Region</li><li>Celebrate traditional Tamil festivals and important cultural events</li><li>Foster community engagement and volunteer opportunities</li><li>Support integration and adaptation to Canadian life while maintaining cultural identity</li></ul>',
        'vision_looking_forward' => '<p>As we continue to grow and serve the Ottawa Tamil community, we remain committed to creating meaningful experiences that connect our past with our present, and our traditions with our future. We invite all Tamil-speaking families and individuals in the National Capital Region to join us in this journey of cultural celebration, preservation, and community building.</p><p>Together, we are creating a vibrant, supportive community where Tamil culture thrives, children learn their heritage with pride, and families build lasting friendships and memories.</p>',
        // Membership
        'membership_hero_subtitle'  => '<p>Join our growing family! There are tons of benefits for becoming a member, hope you join us in our adventures!</p>',
        'membership_benefits_intro' => '<p>By becoming an Ottawa Tamil Sangam Member you can enjoy the following (but not limited to) privileges:</p>',
        'membership_benefit_events' => '<p>Save on entry fees for all our major events:</p><ul><li>January - Pongal Celebration</li><li>April - Tamil New Year</li><li>July - Community Picnic</li><li>October/November - Diwali Festival</li><li>And many more exciting events!</li></ul>',
        'membership_benefit_movies' => '<p>Enjoy up to 15% reduction on ticket costs for Tamil movies released in Ottawa when purchased through Ottawa Tamil Sangam.</p>',
        'membership_benefit_voting' => '<p>Attend Annual General Body Meetings and hold voting rights to nominate and elect executive committee members of Ottawa Tamil Sangam.</p>',
        'membership_note'           => '<p>All memberships are valid for 12 months from the date of purchase.</p>',
        'membership_pricing_intro'  => '<p>Choose the membership tier that\'s right for you and your family</p>',
        'membership_cta_note'       => '<p>Event ticket links will be sent through a separate email after membership purchase.</p>',
        // Contact
        'contact_hero_subtitle' => '<p>We would love to hear your thoughts and suggestions! Get in touch with us through any of the channels below.</p>',
        'contact_email_card'    => '<p>Feel free to drop us an email at<br/><a href="mailto:ottawatamilsangam@gmail.com">ottawatamilsangam@gmail.com</a></p>',
        'contact_social_card'   => '<p>Connect with us on social media for updates, events, and community discussions.</p>',
        // Committee
        'committee_intro'       => '<p>Meet the dedicated individuals who lead and guide Ottawa Tamil Sangam</p>',
        // Special event pages
        'dheepa_hero_title'     => 'Dheepa Thirunaal Kondattam',
        'dheepa_hero_subtitle'  => 'November 8, 2025 • Event Gallery',
        'praveen_hero_title'    => '8, by Praveen Kumar | Tamil Standup Comedy (6+)',
        'praveen_hero_subtitle' => 'November 30, 2025 • Upcoming Event',
        'praveen_about'         => '<p class="lead-text">Praveen Kumar is back in Canada with his new Standup show "8" after the stupendous success of his world Tour!</p><p>Praveen Kumar (PK), known for his highly successful Tamil stand up comedy shows such as "36 Vayathiniley", "Kancheepuram Mapla," and "Family Man," is set to captivate the audience in Ottawa for the first time with his "8" on Nov 30, 2025, after his widely appreciated shows all over the world.</p>',
        'praveen_highlight'     => '<p>This is PK\'s 5th standup comedy show with Master Mediaworks in Canada and first time in Ottawa.</p>',
        'praveen_past_shows'    => '<ul><li>36 Vayathiniley</li><li>Kancheepuram Mapla</li><li>Family Man</li></ul>',
    ];
    $ins = $db->prepare("INSERT OR IGNORE INTO site_content (section_key, content_html) VALUES (?,?)");
    foreach ($sections as $key => $html) $ins->execute([$key, $html]);

    // Seed committee members if empty
    $cmtCnt = $db->query("SELECT COUNT(*) FROM committee_members")->fetchColumn();
    if ($cmtCnt == 0) {
        $members = [
            ['Sangeetha',    'சங்கீதா',          'President',                'தலைவர்',                          'assets/committee/sangeetha.webp',  1],
            ['Vinoth Kumar', 'வினோத் குமார்',     'Vice President',           'உப தலைவர்',                      'assets/committee/vinoth.webp',     2],
            ['Rabi Samuel',  'ரபி சாமுவேல்',     'Secretary',                'செயலாளர்',                        'assets/committee/rabi.webp',       3],
            ['Annamalai',    'அண்ணாமலை',          'Treasurer',                'பொருளாளர்',                      'assets/committee/annamalai.webp',  4],
            ['Shanthi',      'சாந்தி',            'Cultural Coordinator',     'கலைநிகழ்ச்சி ஒருகிணைப்பாளர்',   'assets/committee/shanthi.webp',    5],
            ['Ezhil Isaac',  'எழில் ஐசக்',        'Social Media Coordinator', 'சமூகவலைதல ஒருகிணைப்பாளர்',      'assets/committee/ezhil.webp',      6],
            ['Sowmya',       'சௌம்யா',            'Member Coordinator',       'உறுப்பினர் ஒருகிணைப்பாளர்',      'assets/committee/sowmya.webp',     7],
            ['Navaraj',      'நவராஜ்',            'Sports Coordinator',       'விளையாட்டு ஒருகிணைப்பாளர்',      'assets/committee/navaraj.webp',    8],
        ];
        $insM = $db->prepare("INSERT INTO committee_members (name_english,name_tamil,role_english,role_tamil,photo_path,display_order) VALUES (?,?,?,?,?,?)");
        foreach ($members as $m) $insM->execute($m);
    }

    // Seed membership tiers if empty
    $tierCnt = $db->query("SELECT COUNT(*) FROM membership_tiers")->fetchColumn();
    if ($tierCnt == 0) {
        $tiers = [
            ['Individual', 'bi-person',      20, '$', 'Perfect for individuals looking to connect with the community',  0, 1],
            ['Student',    'bi-mortarboard', 10, '$', 'Special rate for students with valid student ID',                 0, 2],
            ['Couple',     'bi-people',      30, '$', 'Ideal for couples wanting to participate together',               0, 3],
            ['Family',     'bi-house-heart', 45, '$', 'Best value for families of all sizes',                            1, 4],
        ];
        $insT = $db->prepare("INSERT INTO membership_tiers (name,icon,price,currency,description,is_featured,display_order) VALUES (?,?,?,?,?,?,?)");
        foreach ($tiers as $t) $insT->execute($t);
    }

    // Seed membership register URL if not set
    $db->prepare("INSERT OR IGNORE INTO site_content (section_key,content_html) VALUES (?,?)")
       ->execute(['membership_register_url', 'https://www.eventbrite.ca/e/ottawa-tamil-sangam-membership-annual-12-months-from-date-of-purchase-tickets-876297869517?aff=oddtdtcreator']);

    // Seed contact form recipient email (admin can change in dashboard)
    $db->prepare("INSERT OR IGNORE INTO settings (key,value) VALUES (?,?)")
       ->execute(['contact_recipient_email', 'ottawatamilsangam@gmail.com']);

    // Seed default Zeffy settings (admin must fill in real values)
    $zeffyDefaults = [
        'zeffy_api_key'              => '',
        'zeffy_membership_form_slug' => 'ottawa-tamil-sangams-annual-membership',
    ];
    $insSetting = $db->prepare("INSERT OR IGNORE INTO settings (key, value) VALUES (?,?)");
    foreach ($zeffyDefaults as $k => $v) $insSetting->execute([$k, $v]);

    // Seed benefit panels if empty
    $benCnt = $db->query("SELECT COUNT(*) FROM benefit_panels")->fetchColumn();
    if ($benCnt == 0) {
        $panels = [
            ['bi-ticket-perforated', 'Reduced Entry Fees',   '<p>Save on entry fees for all our major events:</p><ul><li>January - Pongal Celebration</li><li>April - Tamil New Year</li><li>July - Community Picnic</li><li>October/November - Diwali Festival</li><li>And many more exciting events!</li></ul>', 1],
            ['bi-film',              'Movie Ticket Discounts','<p>Enjoy up to 15% reduction on ticket costs for Tamil movies released in Ottawa when purchased through Ottawa Tamil Sangam.</p>', 2],
            ['bi-person-check',      'Voting Rights',         '<p>Attend Annual General Body Meetings and hold voting rights to nominate and elect executive committee members of Ottawa Tamil Sangam.</p>', 3],
            ['bi-grid-1x2',          'Member Dashboard',      '<p>Access your personal dashboard to manage tickets, view event history, and manage your membership.</p>', 4],
        ];
        $insB = $db->prepare("INSERT INTO benefit_panels (icon,title,content,display_order) VALUES (?,?,?,?)");
        foreach ($panels as $p) $insB->execute($p);
    }

    // Seed vision stats if empty
    $statCnt = $db->query("SELECT COUNT(*) FROM vision_stats")->fetchColumn();
    if ($statCnt == 0) {
        $stats = [['2015','Established',1],['100+','Events Annually',2],['500+','Community Members',3]];
        $insVS = $db->prepare("INSERT INTO vision_stats (number_text,label,display_order) VALUES (?,?,?)");
        foreach ($stats as $s) $insVS->execute($s);
    }

    // Seed vision core values if empty
    $valCnt = $db->query("SELECT COUNT(*) FROM vision_core_values")->fetchColumn();
    if ($valCnt == 0) {
        $values = [
            ['Unity',       'Bringing the Tamil community together through shared experiences and cultural celebrations.', 1],
            ['Heritage',    'Preserving and promoting Tamil language, arts, traditions, and cultural practices.', 2],
            ['Inclusivity', 'Welcoming all Tamil-speaking individuals regardless of background, maintaining a secular and non-partisan approach.', 3],
            ['Education',   'Empowering the next generation with knowledge of their cultural roots and language.', 4],
            ['Community',   'Building strong relationships and support networks within the Ottawa Tamil community.', 5],
            ['Integration', 'Balancing Tamil cultural identity with Canadian values and way of life.', 6],
        ];
        $insVC = $db->prepare("INSERT INTO vision_core_values (title,description,display_order) VALUES (?,?,?)");
        foreach ($values as $v) $insVC->execute($v);
    }

    // Seed slideshow photos from images.txt if table is empty
    $slideCnt = $db->query("SELECT COUNT(*) FROM slideshow_photos")->fetchColumn();
    if ($slideCnt == 0) {
        $imgFile = __DIR__ . '/images.txt';
        $photos  = [];
        if (file_exists($imgFile)) {
            foreach (file($imgFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line && !str_starts_with($line, '#')) $photos[] = $line;
            }
        }
        if (empty($photos)) {
            $photos = ['hero1.jpg', 'hero2.jpg', 'hero3.jpg'];
        }
        $insS = $db->prepare("INSERT INTO slideshow_photos (photo_path,is_active,display_order) VALUES (?,1,?)");
        foreach ($photos as $i => $path) $insS->execute([$path, $i + 1]);
    }

    // Seed sample events if empty
    $evtCnt = $db->query("SELECT COUNT(*) FROM events")->fetchColumn();
    if ($evtCnt == 0) {
        $events = [
            ['Dheepa Thirunaal Kondattam','தீப திருநாள் கொண்டாட்டம்','<p>A celebration of the festival of lights with cultural performances, traditional food, and community bonding.</p>','2025-11-08','6:00 PM','Ottawa Convention Centre','','',20,30,0,1],
            ['Pongal Celebration 2026','பொங்கல் கொண்டாட்டம்','<p>Join us for our annual Pongal celebration with traditional cooking, music, and dance performances.</p>','2026-01-15','4:00 PM','Algonquin College, Ottawa','','',15,25,1,1],
            ['Tamil New Year 2026','தமிழ் புத்தாண்டு','<p>Celebrate the Tamil New Year with us! Enjoy cultural programs, traditional food, and community fun.</p>','2026-04-14','2:00 PM','Ottawa City Hall','','',15,25,1,1],
        ];
        $ins2 = $db->prepare("INSERT INTO events (title,title_tamil,description,event_date,event_time,location,image_path,ticket_url,member_price,regular_price,is_upcoming,is_published) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
        foreach ($events as $e) $ins2->execute($e);
    }
}

/** Convenience: escape output */
function e(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

/** Return JSON and exit */
function jsonResponse(mixed $data, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/** Get a private setting value (never exposed publicly) */
function getSetting(string $key, string $fallback = ''): string {
    static $cache = [];
    if (isset($cache[$key])) return $cache[$key];
    try {
        $db   = getDB();
        $stmt = $db->prepare("SELECT value FROM settings WHERE key=?");
        $stmt->execute([$key]);
        $row  = $stmt->fetch();
        $cache[$key] = $row ? $row['value'] : $fallback;
    } catch (Exception) {
        $cache[$key] = $fallback;
    }
    return $cache[$key];
}

/** Save a private setting value */
function putSetting(string $key, string $value): void {
    $db = getDB();
    $db->prepare("INSERT INTO settings (key,value) VALUES (?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value")
       ->execute([$key, $value]);
    // Clear cache
    static $cache = [];
    unset($cache[$key]);
}

/** Get site content section (with fallback) */
function getSiteContent(string $key, string $fallback = ''): string {
    static $cache = [];
    if (isset($cache[$key])) return $cache[$key];
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT content_html FROM site_content WHERE section_key=?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        $cache[$key] = $row ? $row['content_html'] : $fallback;
    } catch (Exception) {
        $cache[$key] = $fallback;
    }
    return $cache[$key];
}
