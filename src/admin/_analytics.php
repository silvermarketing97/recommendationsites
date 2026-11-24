<?php
/**
 * Recommendation Sites – self-hosted analytics (no Google, no external services).
 * Data is stored in admin/_private/analytics.sqlite on your own hosting.
 * Privacy-friendly: no cookies; visitors are counted with a daily-rotating anonymous hash.
 */

date_default_timezone_set('America/Bogota');

if (!function_exists('str_contains')) { function str_contains($h, $n) { return $n === '' || strpos($h, $n) !== false; } }
if (!function_exists('str_starts_with')) { function str_starts_with($h, $n) { return strncmp($h, $n, strlen($n)) === 0; } }
if (!function_exists('str_ends_with')) { function str_ends_with($h, $n) { return $n === '' || substr($h, -strlen($n)) === $n; } }

const RS_TRACK_EVENTS = ['referral_click'];

function rs_private_dir() { return __DIR__ . '/_private'; }

function rs_db() {
    static $db = null;
    if ($db) return $db;
    if (!class_exists('PDO') || !in_array('sqlite', PDO::getAvailableDrivers(), true)) {
        throw new RuntimeException('SQLite is not available on this server.');
    }
    $dir = rs_private_dir();
    if (!is_dir($dir)) @mkdir($dir, 0700, true);
    $db = new PDO('sqlite:' . $dir . '/analytics.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec('PRAGMA journal_mode=WAL; PRAGMA synchronous=NORMAL; PRAGMA busy_timeout=3000;');
    $db->exec('CREATE TABLE IF NOT EXISTS hits (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ts INTEGER NOT NULL,
        day TEXT NOT NULL,
        hour INTEGER NOT NULL,
        type TEXT NOT NULL,
        pv TEXT, session TEXT, visitor TEXT,
        path TEXT, title TEXT,
        ref_host TEXT, source TEXT, medium TEXT,
        utm_source TEXT, utm_medium TEXT, utm_campaign TEXT,
        country TEXT, lang TEXT, device TEXT, browser TEXT, os TEXT,
        event TEXT, label TEXT,
        duration INTEGER DEFAULT 0
    )');
    $db->exec('CREATE INDEX IF NOT EXISTS i_day ON hits(day, type)');
    $db->exec('CREATE INDEX IF NOT EXISTS i_ts ON hits(ts)');
    $db->exec('CREATE INDEX IF NOT EXISTS i_pv ON hits(pv)');
    return $db;
}

function rs_salt() {
    $f = rs_private_dir() . '/salt.txt';
    if (!is_file($f)) @file_put_contents($f, bin2hex(random_bytes(16)));
    return (string)@file_get_contents($f);
}

function rs_cut($s, $n = 300) { return mb_substr(trim((string)$s), 0, $n); }

function rs_is_bot($ua) {
    return $ua === '' || preg_match('/bot|crawl|spider|slurp|bingpreview|facebookexternalhit|embedly|quora link|whatsapp|telegrambot|discordbot|preview|headless|lighthouse|pingdom|uptime|monitor|curl|wget|python|java\/|go-http|axios|node-fetch|semrush|ahrefs|mj12|dotbot|petalbot|yandex|baiduspider|bytespider|gptbot|claudebot|ccbot|chatgpt|perplexity/i', $ua);
}

function rs_parse_ua($ua, $sw) {
    $os = 'Other';
    if (preg_match('/iPhone|iPad|iPod/i', $ua)) $os = 'iOS';
    elseif (preg_match('/Android/i', $ua)) $os = 'Android';
    elseif (preg_match('/Windows/i', $ua)) $os = 'Windows';
    elseif (preg_match('/Mac OS X|Macintosh/i', $ua)) $os = 'macOS';
    elseif (preg_match('/CrOS/i', $ua)) $os = 'ChromeOS';
    elseif (preg_match('/Linux/i', $ua)) $os = 'Linux';

    $browser = 'Other';
    if (preg_match('/Edg\//', $ua)) $browser = 'Edge';
    elseif (preg_match('/OPR\/|Opera/', $ua)) $browser = 'Opera';
    elseif (preg_match('/SamsungBrowser/', $ua)) $browser = 'Samsung Internet';
    elseif (preg_match('/Firefox|FxiOS/', $ua)) $browser = 'Firefox';
    elseif (preg_match('/Chrome|CriOS/', $ua)) $browser = 'Chrome';
    elseif (preg_match('/Safari/', $ua)) $browser = 'Safari';

    $sw = (int)$sw;
    if (preg_match('/iPad|Tablet/i', $ua) || (preg_match('/Android/i', $ua) && !preg_match('/Mobile/i', $ua))) $device = 'Tablet';
    elseif (preg_match('/Mobi|iPhone|Android/i', $ua) || ($sw > 0 && $sw < 700)) $device = 'Mobile';
    else $device = 'Desktop';
    return [$device, $browser, $os];
}

function rs_country($tz, $lang) {
    static $map = [
        'America/New_York'=>'US','America/Chicago'=>'US','America/Denver'=>'US','America/Los_Angeles'=>'US','America/Phoenix'=>'US','America/Anchorage'=>'US','Pacific/Honolulu'=>'US','America/Detroit'=>'US','America/Indiana/Indianapolis'=>'US','America/Boise'=>'US','America/Kentucky/Louisville'=>'US',
        'America/Toronto'=>'CA','America/Vancouver'=>'CA','America/Edmonton'=>'CA','America/Winnipeg'=>'CA','America/Halifax'=>'CA','America/St_Johns'=>'CA','America/Regina'=>'CA','America/Montreal'=>'CA',
        'Europe/London'=>'GB','Europe/Dublin'=>'IE','Europe/Belfast'=>'GB',
        'Australia/Sydney'=>'AU','Australia/Melbourne'=>'AU','Australia/Brisbane'=>'AU','Australia/Perth'=>'AU','Australia/Adelaide'=>'AU','Australia/Hobart'=>'AU','Australia/Darwin'=>'AU','Australia/Canberra'=>'AU',
        'Pacific/Auckland'=>'NZ','America/Bogota'=>'CO','America/Mexico_City'=>'MX','America/Lima'=>'PE','America/Santiago'=>'CL','America/Argentina/Buenos_Aires'=>'AR','America/Caracas'=>'VE','America/Guayaquil'=>'EC','America/Sao_Paulo'=>'BR','America/Panama'=>'PA','America/Costa_Rica'=>'CR','America/Santo_Domingo'=>'DO','America/Puerto_Rico'=>'PR','America/Guatemala'=>'GT',
        'Europe/Madrid'=>'ES','Europe/Paris'=>'FR','Europe/Berlin'=>'DE','Europe/Rome'=>'IT','Europe/Amsterdam'=>'NL','Europe/Lisbon'=>'PT','Europe/Brussels'=>'BE','Europe/Zurich'=>'CH','Europe/Stockholm'=>'SE','Europe/Oslo'=>'NO','Europe/Copenhagen'=>'DK','Europe/Warsaw'=>'PL','Europe/Bucharest'=>'RO','Europe/Athens'=>'GR','Europe/Istanbul'=>'TR','Europe/Moscow'=>'RU','Europe/Kiev'=>'UA','Europe/Kyiv'=>'UA',
        'Asia/Kolkata'=>'IN','Asia/Calcutta'=>'IN','Asia/Manila'=>'PH','Asia/Karachi'=>'PK','Asia/Dhaka'=>'BD','Asia/Jakarta'=>'ID','Asia/Singapore'=>'SG','Asia/Tokyo'=>'JP','Asia/Shanghai'=>'CN','Asia/Hong_Kong'=>'HK','Asia/Dubai'=>'AE','Asia/Riyadh'=>'SA','Asia/Ho_Chi_Minh'=>'VN','Asia/Bangkok'=>'TH','Asia/Seoul'=>'KR','Asia/Kuala_Lumpur'=>'MY',
        'Africa/Lagos'=>'NG','Africa/Johannesburg'=>'ZA','Africa/Nairobi'=>'KE','Africa/Cairo'=>'EG','Africa/Accra'=>'GH','Africa/Casablanca'=>'MA',
    ];
    if (isset($map[$tz])) return $map[$tz];
    if (preg_match('/^[a-z]{2,3}[-_]([A-Z]{2})$/', (string)$lang, $m)) return $m[1];
    return '';
}

function rs_source($refHost, $utmSource, $siteHost) {
    if ($utmSource !== '') return [ucfirst(strtolower($utmSource)), 'Campaign'];
    if ($refHost === '') return ['Direct', 'Direct'];
    if ($refHost === $siteHost || str_ends_with($refHost, '.' . $siteHost) || $refHost === 'recommendationsites.com' || str_ends_with($refHost, '.temporary.site')) return ['Internal', 'Internal'];
    $search = ['google'=>'Google','bing'=>'Bing','yahoo'=>'Yahoo','duckduckgo'=>'DuckDuckGo','ecosia'=>'Ecosia','yandex'=>'Yandex','baidu'=>'Baidu','search.brave'=>'Brave Search','startpage'=>'Startpage','aol'=>'AOL'];
    foreach ($search as $k => $name) if (str_contains($refHost, $k)) return [$name, 'Search'];
    $social = ['facebook'=>'Facebook','fb.'=>'Facebook','instagram'=>'Instagram','t.co'=>'X / Twitter','twitter'=>'X / Twitter','x.com'=>'X / Twitter','linkedin'=>'LinkedIn','lnkd.in'=>'LinkedIn','pinterest'=>'Pinterest','reddit'=>'Reddit','youtube'=>'YouTube','tiktok'=>'TikTok','whatsapp'=>'WhatsApp','telegram'=>'Telegram','quora'=>'Quora','snapchat'=>'Snapchat','threads'=>'Threads'];
    foreach ($social as $k => $name) if (str_contains($refHost, $k)) return [$name, 'Social'];
    $ai = ['chatgpt'=>'ChatGPT','openai'=>'ChatGPT','perplexity'=>'Perplexity','claude.ai'=>'Claude','gemini'=>'Gemini','copilot'=>'Copilot'];
    foreach ($ai as $k => $name) if (str_contains($refHost, $k)) return [$name, 'AI'];
    return [$refHost, 'Referral'];
}

function rs_collect() {
    $raw = file_get_contents('php://input', false, null, 0, 4000);
    $d = json_decode((string)$raw, true);
    if (!is_array($d)) { http_response_code(400); return; }
    $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
    if (rs_is_bot($ua)) { http_response_code(204); return; }

    $type = $d['t'] ?? '';
    $pv = rs_cut($d['v'] ?? '', 40);
    $db = rs_db();

    if ($type === 'end') {
        $dur = max(0, min(3600, (int)($d['d'] ?? 0)));
        if ($pv !== '' && $dur > 0) {
            $st = $db->prepare("UPDATE hits SET duration = MAX(duration, ?) WHERE pv = ? AND type = 'pageview'");
            $st->execute([$dur, $pv]);
        }
        http_response_code(204);
        return;
    }
    if (!in_array($type, ['pv', 'ev'], true)) { http_response_code(400); return; }

    $path = rs_cut($d['p'] ?? '/', 300);
    if ($path === '' || $path[0] !== '/' || str_starts_with($path, '/admin')) { http_response_code(204); return; }

    $siteHost = preg_replace('/^www\./', '', strtolower($_SERVER['HTTP_HOST'] ?? ''));
    $refHost = '';
    if (!empty($d['r'])) {
        $h = parse_url((string)$d['r'], PHP_URL_HOST);
        if ($h) $refHost = preg_replace('/^www\./', '', strtolower($h));
    }
    $us = rs_cut($d['us'] ?? '', 60); $um = rs_cut($d['um'] ?? '', 60); $uc = rs_cut($d['uc'] ?? '', 100);
    [$source, $medium] = rs_source($refHost, $us, $siteHost);
    [$device, $browser, $os] = rs_parse_ua($ua, $d['sw'] ?? 0);
    $lang = rs_cut($d['lang'] ?? '', 20);
    $country = rs_country(rs_cut($d['tz'] ?? '', 60), $lang);
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $now = time();
    $visitor = substr(hash('sha256', date('Y-m-d', $now) . rs_salt() . $ip . $ua), 0, 16);

    $st = $db->prepare('INSERT INTO hits (ts, day, hour, type, pv, session, visitor, path, title, ref_host, source, medium, utm_source, utm_medium, utm_campaign, country, lang, device, browser, os, event, label)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $event = $type === 'ev' ? rs_cut($d['n'] ?? '', 40) : null;
    if ($type === 'ev' && !in_array($event, RS_TRACK_EVENTS, true)) { http_response_code(204); return; }
    $st->execute([
        $now, date('Y-m-d', $now), (int)date('G', $now), $type === 'pv' ? 'pageview' : 'event',
        $pv, rs_cut($d['s'] ?? '', 40), $visitor, $path, rs_cut($d['ti'] ?? '', 200),
        $refHost, $source, $medium, $us, $um, $uc, $country, $lang, $device, $browser, $os,
        $event, $type === 'ev' ? rs_cut($d['l'] ?? '', 60) : null,
    ]);
    http_response_code(204);
}

/* ------------------------- Reporting ------------------------- */

function rs_range($range, $from = null, $to = null) {
    $today = new DateTimeImmutable('today');
    switch ($range) {
        case 'today':     $a = $today; $b = $today; break;
        case 'yesterday': $a = $today->modify('-1 day'); $b = $a; break;
        case '7d':        $a = $today->modify('-6 days'); $b = $today; break;
        case '90d':       $a = $today->modify('-89 days'); $b = $today; break;
        case '12m':       $a = $today->modify('-364 days'); $b = $today; break;
        case 'custom':
            $a = DateTimeImmutable::createFromFormat('Y-m-d', (string)$from) ?: $today->modify('-29 days');
            $b = DateTimeImmutable::createFromFormat('Y-m-d', (string)$to) ?: $today;
            if ($a > $b) [$a, $b] = [$b, $a];
            break;
        default:          $a = $today->modify('-29 days'); $b = $today;
    }
    $days = (int)$a->diff($b)->days + 1;
    $pa = $a->modify("-{$days} days"); $pb = $a->modify('-1 day');
    return [$a->format('Y-m-d'), $b->format('Y-m-d'), $pa->format('Y-m-d'), $pb->format('Y-m-d'), $days];
}

function rs_kpis($db, $a, $b) {
    $q = fn($sql) => $db->prepare($sql);
    $st = $q("SELECT COUNT(*) pv, COUNT(DISTINCT visitor) visitors, COUNT(DISTINCT session) sessions,
              AVG(CASE WHEN duration > 0 THEN duration END) avgdur
              FROM hits WHERE type='pageview' AND day BETWEEN ? AND ?");
    $st->execute([$a, $b]); $r = $st->fetch(PDO::FETCH_ASSOC);
    $st = $q("SELECT COUNT(*) FROM (SELECT session FROM hits WHERE type='pageview' AND day BETWEEN ? AND ? GROUP BY session HAVING COUNT(*)=1)");
    $st->execute([$a, $b]); $bounces = (int)$st->fetchColumn();
    $st = $q("SELECT COUNT(*) FROM hits WHERE type='event' AND event='referral_click' AND day BETWEEN ? AND ?");
    $st->execute([$a, $b]); $clicks = (int)$st->fetchColumn();
    $sessions = (int)$r['sessions'];
    return [
        'pageviews' => (int)$r['pv'],
        'visitors' => (int)$r['visitors'],
        'sessions' => $sessions,
        'avgDuration' => (int)round((float)$r['avgdur']),
        'pagesPerSession' => $sessions ? round($r['pv'] / $sessions, 2) : 0,
        'bounceRate' => $sessions ? round($bounces / $sessions * 100, 1) : 0,
        'clicks' => $clicks,
        'ctr' => $sessions ? round($clicks / $sessions * 100, 1) : 0,
    ];
}

function rs_top($db, $col, $a, $b, $limit = 10, $where = "type='pageview'", $extra = '') {
    $sql = "SELECT $col AS name, COUNT(*) views, COUNT(DISTINCT visitor) visitors $extra
            FROM hits WHERE $where AND day BETWEEN ? AND ? AND $col IS NOT NULL AND $col <> ''
            GROUP BY $col ORDER BY views DESC LIMIT " . (int)$limit;
    $st = $db->prepare($sql); $st->execute([$a, $b]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

function rs_report($range, $from = null, $to = null) {
    $db = rs_db();
    [$a, $b, $pa, $pb, $days] = rs_range($range, $from, $to);
    $hourly = ($a === $b);

    if ($hourly) {
        $st = $db->prepare("SELECT hour k, COUNT(*) pv, COUNT(DISTINCT visitor) v FROM hits WHERE type='pageview' AND day=? GROUP BY hour");
        $st->execute([$a]);
        $rows = []; foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) $rows[(int)$r['k']] = $r;
        $series = []; for ($h = 0; $h < 24; $h++) $series[] = ['label' => sprintf('%02d:00', $h), 'pageviews' => (int)($rows[$h]['pv'] ?? 0), 'visitors' => (int)($rows[$h]['v'] ?? 0)];
    } else {
        $st = $db->prepare("SELECT day k, COUNT(*) pv, COUNT(DISTINCT visitor) v FROM hits WHERE type='pageview' AND day BETWEEN ? AND ? GROUP BY day");
        $st->execute([$a, $b]);
        $rows = []; foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) $rows[$r['k']] = $r;
        $series = [];
        for ($d = new DateTimeImmutable($a); $d <= new DateTimeImmutable($b); $d = $d->modify('+1 day')) {
            $k = $d->format('Y-m-d');
            $series[] = ['label' => $k, 'pageviews' => (int)($rows[$k]['pv'] ?? 0), 'visitors' => (int)($rows[$k]['v'] ?? 0)];
        }
    }

    $pages = rs_top($db, 'path', $a, $b, 15, "type='pageview'", ", MAX(title) title, CAST(AVG(CASE WHEN duration>0 THEN duration END) AS INTEGER) avgdur");
    $st = $db->prepare("SELECT path name, COUNT(*) views FROM hits WHERE type='pageview' AND day BETWEEN ? AND ? AND pv IN (SELECT MIN(pv) FROM hits WHERE type='pageview' AND day BETWEEN ? AND ? GROUP BY session) GROUP BY path ORDER BY views DESC LIMIT 10");
    $st->execute([$a, $b, $a, $b]); $entry = $st->fetchAll(PDO::FETCH_ASSOC);

    return [
        'range' => ['from' => $a, 'to' => $b, 'days' => $days, 'hourly' => $hourly],
        'kpis' => rs_kpis($db, $a, $b),
        'previous' => rs_kpis($db, $pa, $pb),
        'series' => $series,
        'pages' => $pages,
        'entryPages' => $entry,
        'sources' => rs_top($db, 'source', $a, $b, 12, "type='pageview' AND medium <> 'Internal'"),
        'channels' => rs_top($db, 'medium', $a, $b, 8, "type='pageview' AND medium <> 'Internal'"),
        'referrers' => rs_top($db, 'ref_host', $a, $b, 12, "type='pageview' AND medium IN ('Referral','Social','Search','AI')"),
        'campaigns' => rs_top($db, 'utm_campaign', $a, $b, 10),
        'countries' => rs_top($db, 'country', $a, $b, 15),
        'devices' => rs_top($db, 'device', $a, $b, 5),
        'browsers' => rs_top($db, 'browser', $a, $b, 8),
        'os' => rs_top($db, 'os', $a, $b, 8),
        'languages' => rs_top($db, 'lang', $a, $b, 10),
        'clickPlacements' => rs_top($db, 'label', $a, $b, 10, "type='event' AND event='referral_click'"),
        'clickPages' => rs_top($db, 'path', $a, $b, 10, "type='event' AND event='referral_click'"),
        'realtime' => rs_realtime($db),
        'generatedAt' => date('c'),
    ];
}

function rs_realtime($db = null) {
    $db = $db ?: rs_db();
    $since = time() - 300;
    $st = $db->prepare("SELECT COUNT(DISTINCT visitor) FROM hits WHERE ts >= ?");
    $st->execute([$since]); $active = (int)$st->fetchColumn();
    $st = $db->prepare("SELECT path name, COUNT(DISTINCT visitor) visitors FROM hits WHERE type='pageview' AND ts >= ? GROUP BY path ORDER BY visitors DESC LIMIT 5");
    $st->execute([$since]);
    $st2 = $db->prepare("SELECT ts, path, country, device, source FROM hits WHERE type='pageview' ORDER BY id DESC LIMIT 12");
    $st2->execute();
    return ['active' => $active, 'pages' => $st->fetchAll(PDO::FETCH_ASSOC), 'recent' => $st2->fetchAll(PDO::FETCH_ASSOC)];
}
