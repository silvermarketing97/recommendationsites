<?php
// Generates _site/admin/_private/config.php from GitHub secrets during deploy.
// Nothing here is stored in git.
$user  = trim((string)getenv('ADMIN_USERNAME'));
$email = trim((string)getenv('ADMIN_EMAIL'));
$pass  = (string)getenv('ADMIN_PASSWORD');
$token = trim((string)getenv('ADMIN_GITHUB_TOKEN'));
$out   = $argv[1] ?? '_site/admin/_private/config.php';

if (($user === '' && $email === '') || $pass === '' || $token === '') {
    fwrite(STDERR, "Admin login secrets missing: skipping config (login page will show setup notice).\n");
    exit(0);
}
$users = [[
    'username' => $user,
    'email'    => $email,
    'hash'     => password_hash($pass, PASSWORD_DEFAULT),
]];
// Optional second user
$u2 = trim((string)getenv('ADMIN2_USERNAME'));
$p2 = (string)getenv('ADMIN2_PASSWORD');
if ($u2 !== '' && $p2 !== '') {
    $users[] = ['username' => $u2, 'email' => trim((string)getenv('ADMIN2_EMAIL')), 'hash' => password_hash($p2, PASSWORD_DEFAULT)];
}
$config = ['users' => $users, 'github_token' => $token];
@mkdir(dirname($out), 0700, true);
file_put_contents($out, "<?php\n// Generated at deploy. Do not edit.\nreturn " . var_export($config, true) . ";\n");
echo "Admin config written for " . count($users) . " user(s).\n";
