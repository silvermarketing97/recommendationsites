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
    $db->exec('CREATE TABLE IF NOT EXISTS bots (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ts INTEGER NOT NULL, day TEXT NOT NULL, hour INTEGER NOT NULL,
        name TEXT, category TEXT, path TEXT, status INTEGER, ip TEXT
    )');
    $db->exec('CREATE INDEX IF NOT EXISTS b_day ON bots(day)');
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

/** Identify a robot from its user agent. Returns [name, category] or null for humans. */
function rs_bot_info($ua) {
    $ua = (string)$ua;
    if (trim($ua) === '') return ['No user agent', 'Script / unknown'];
    static $list = [
        // name => [regex, category]
        'Googlebot' => ['/Googlebot|Google-InspectionTool|GoogleOther|Storebot-Google/i', 'Search engine'],
        'Google Ads/Other' => ['/AdsBot-Google|Mediapartners-Google|APIs-Google|FeedFetcher-Google|Google-Read-Aloud|Google-Extended/i', 'Search engine'],
        'Bingbot' => ['/bingbot|BingPreview|msnbot|adidxbot/i', 'Search engine'],
        'Applebot' => ['/Applebot/i', 'Search engine'],
        'DuckDuckBot' => ['/DuckDuckBot|DuckAssistBot/i', 'Search engine'],
        'YandexBot' => ['/Yandex/i', 'Search engine'],
        'Baiduspider' => ['/Baiduspider/i', 'Search engine'],
        'Yahoo Slurp' => ['/Slurp/i', 'Search engine'],
        'Seznam' => ['/SeznamBot/i', 'Search engine'],
        'GPTBot (OpenAI)' => ['/GPTBot|OAI-SearchBot|ChatGPT-User/i', 'AI'],
        'ClaudeBot (Anthropic)' => ['/ClaudeBot|Claude-Web|Claude-User|Claude-SearchBot|anthropic-ai/i', 'AI'],
        'PerplexityBot' => ['/Perplexity/i', 'AI'],
        'Bytespider (TikTok)' => ['/Bytespider/i', 'AI'],
        'CCBot (Common Crawl)' => ['/CCBot/i', 'AI'],
        'Meta AI' => ['/meta-externalagent|Meta-ExternalFetcher|FacebookBot/i', 'AI'],
        'Amazonbot' => ['/Amazonbot/i', 'AI'],
        'Cohere / You.com / others' => ['/cohere-ai|YouBot|Diffbot|ImagesiftBot|Timpibot|omgili/i', 'AI'],
        'AhrefsBot' => ['/AhrefsBot|AhrefsSiteAudit/i', 'SEO tool'],
        'SemrushBot' => ['/SemrushBot|SiteAuditBot/i', 'SEO tool'],
        'MJ12bot (Majestic)' => ['/MJ12bot/i', 'SEO tool'],
        'DotBot (Moz)' => ['/DotBot|rogerbot/i', 'SEO tool'],
        'Screaming Frog' => ['/Screaming Frog/i', 'SEO tool'],
        'DataForSeoBot' => ['/DataForSeoBot|serpstatbot|SEOkicks|BLEXBot|Barkrowler|MegaIndex/i', 'SEO tool'],
        'PetalBot (Huawei)' => ['/PetalBot/i', 'Search engine'],
        'Facebook preview' => ['/facebookexternalhit|facebookcatalog/i', 'Social preview'],
        'X/Twitter preview' => ['/Twitterbot/i', 'Social preview'],
        'LinkedIn preview' => ['/LinkedInBot/i', 'Social preview'],
        'WhatsApp preview' => ['/WhatsApp/i', 'Social preview'],
        'Telegram preview' => ['/TelegramBot/i', 'Social preview'],
        'Discord / Slack preview' => ['/Discordbot|Slackbot|Slack-ImgProxy/i', 'Social preview'],
        'Pinterest' => ['/Pinterest/i', 'Social preview'],
        'Uptime monitor' => ['/UptimeRobot|Pingdom|StatusCake|Site24x7|BetterStack|monitor/i', 'Monitoring'],
        'Lighthouse / PageSpeed' => ['/Lighthouse|PageSpeed|Chrome-Lighthouse|GTmetrix/i', 'Monitoring'],
        'curl / wget' => ['/^curl|Wget/i', 'Script / unknown'],
        'Python script' => ['/python|aiohttp|scrapy/i', 'Script / unknown'],
        'Other script' => ['/Go-http-client|okhttp|Java\/|axios|node-fetch|libwww|HttpClient|Apache-HttpClient|PostmanRuntime|Headless/i', 'Script / unknown'],
    ];
    foreach ($list as $name => [$re, $cat]) if (preg_match($re, $ua)) return [$name, $cat];
    if (preg_match('/bot|crawl|spider|scrape|fetch|preview|checker|scan/i', $ua)) {
        if (preg_match('/([A-Za-z0-9._-]*(?:bot|crawler|spider)[A-Za-z0-9._-]*)/i', $ua, $m)) return [mb_substr($m[1], 0, 40), 'Other bot'];
        return ['Other bot', 'Other bot'];
    }
    return null;
}

function rs_log_bot($path, $status) {
    $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
    $info = rs_bot_info($ua);
    if (!$info) return;
    $now = time();
    $ipHash = substr(hash('sha256', rs_salt() . ($_SERVER['REMOTE_ADDR'] ?? '')), 0, 12);
    $st = rs_db()->prepare('INSERT INTO bots (ts, day, hour, name, category, path, status, ip) VALUES (?,?,?,?,?,?,?,?)');
    $st->execute([$now, date('Y-m-d', $now), (int)date('G', $now), $info[0], $info[1], rs_cut($path, 300), (int)$status, $ipHash]);
    if (mt_rand(1, 500) === 1) rs_db()->exec('DELETE FROM bots WHERE ts < ' . ($now - 400 * 86400)); // keep ~13 months
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

    // Robots per period (same buckets as the chart)
    if ($hourly) {
        $st = $db->prepare("SELECT hour k, COUNT(*) n FROM bots WHERE day=? GROUP BY hour"); $st->execute([$a]);
        $bm = []; foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) $bm[(int)$r['k']] = (int)$r['n'];
        foreach ($series as $i => &$row) { $row['bots'] = $bm[$i] ?? 0; } unset($row);
    } else {
        $st = $db->prepare("SELECT day k, COUNT(*) n FROM bots WHERE day BETWEEN ? AND ? GROUP BY day"); $st->execute([$a, $b]);
        $bm = []; foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) $bm[$r['k']] = (int)$r['n'];
        foreach ($series as &$row) { $row['bots'] = $bm[$row['label']] ?? 0; } unset($row);
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
        'bots' => rs_bots_report($db, $a, $b, $pa, $pb),
        'health' => rs_health($db, $series),
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


function rs_bots_report($db, $a, $b, $pa, $pb) {
    $cnt = function ($x, $y) use ($db) { $st = $db->prepare("SELECT COUNT(*) FROM bots WHERE day BETWEEN ? AND ?"); $st->execute([$x, $y]); return (int)$st->fetchColumn(); };
    $st = $db->prepare("SELECT name, category, COUNT(*) views, MAX(ts) last, SUM(status >= 400) errors FROM bots WHERE day BETWEEN ? AND ? GROUP BY name ORDER BY views DESC LIMIT 25");
    $st->execute([$a, $b]); $byName = $st->fetchAll(PDO::FETCH_ASSOC);
    $st = $db->prepare("SELECT category name, COUNT(*) views, COUNT(DISTINCT name) visitors FROM bots WHERE day BETWEEN ? AND ? GROUP BY category ORDER BY views DESC");
    $st->execute([$a, $b]); $byCat = $st->fetchAll(PDO::FETCH_ASSOC);
    $st = $db->prepare("SELECT path name, COUNT(*) views, COUNT(DISTINCT name) visitors FROM bots WHERE day BETWEEN ? AND ? GROUP BY path ORDER BY views DESC LIMIT 12");
    $st->execute([$a, $b]); $paths = $st->fetchAll(PDO::FETCH_ASSOC);
    $st = $db->prepare("SELECT path name, COUNT(*) views, COUNT(DISTINCT name) visitors FROM bots WHERE status >= 400 AND day BETWEEN ? AND ? GROUP BY path ORDER BY views DESC LIMIT 10");
    $st->execute([$a, $b]); $errors = $st->fetchAll(PDO::FETCH_ASSOC);
    $last = function ($re) use ($db) { $st = $db->prepare("SELECT MAX(ts) FROM bots WHERE name LIKE ?"); $st->execute([$re]); return (int)$st->fetchColumn(); };
    return [
        'total' => $cnt($a, $b), 'previous' => $cnt($pa, $pb),
        'byName' => $byName, 'byCategory' => $byCat, 'paths' => $paths, 'errors' => $errors,
        'lastGoogle' => $last('Googlebot%'), 'lastBing' => $last('Bingbot%'),
    ];
}

function rs_health($db, $series) {
    $st = $db->query("SELECT MAX(ts) FROM hits WHERE type='pageview'"); $lastHuman = (int)$st->fetchColumn();
    $st = $db->query("SELECT MIN(ts) FROM hits"); $first = (int)$st->fetchColumn();
    $st = $db->prepare("SELECT COUNT(DISTINCT visitor) FROM hits WHERE type='pageview' AND day = ?"); $st->execute([date('Y-m-d')]); $today = (int)$st->fetchColumn();
    $zero = 0; foreach ($series as $r) if (($r['pageviews'] ?? 0) === 0) $zero++;
    // consecutive days without human visits, counting back from today
    $streak = 0;
    for ($i = 0; $i < 60; $i++) {
        $d = date('Y-m-d', strtotime("-$i day"));
        $st = $db->prepare("SELECT COUNT(*) FROM hits WHERE type='pageview' AND day=?"); $st->execute([$d]);
        if ((int)$st->fetchColumn() > 0) break;
        $streak++;
    }
    return ['lastHuman' => $lastHuman, 'firstData' => $first, 'todayVisitors' => $today, 'zeroBuckets' => $zero, 'daysWithoutVisits' => $streak, 'now' => time()];
}
