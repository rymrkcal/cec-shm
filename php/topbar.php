<?php

require_once __DIR__ . '/icons.php';

$nav_root = $nav_root ?? '';
$back_href = $back_href ?? '';
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$account_href = $nav_root . 'account.php';
$logout_href = $nav_root . 'logout.php';
$folder = basename(dirname(str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? '')));

if ($folder === 'admin') {
    $clients_href = 'clients.php';
} else {
    $clients_href = $nav_root . 'admin/clients.php';
}
?>
<header class="topbar">
    <span class="brand">CEC SHM</span>
    <div class="topbar-actions">
        <details class="settings-menu">
            <summary class="settings"><?php echo icon_settings(); ?> Settings</summary>
            <div class="settings-panel">
                <?php if ($is_admin) { ?>
                    <a href="<?php echo htmlspecialchars($clients_href); ?>"><?php echo icon_clients(); ?> Client Management</a>
                <?php } ?>
                <a href="<?php echo htmlspecialchars($account_href); ?>"><?php echo icon_account(); ?> My Account</a>
            </div>
        </details>
        <?php if ($back_href !== '') { ?>
            <a class="back" href="<?php echo htmlspecialchars($back_href); ?>">Back</a>
        <?php } ?>
        <a class="logout" href="<?php echo htmlspecialchars($logout_href); ?>">Log out</a>
    </div>
</header>
<script>
(function () {
    var statusUrl = <?php echo json_encode($nav_root . 'session_status.php'); ?>;
    var loginUrl = <?php echo json_encode($nav_root . 'index.php'); ?>;

    function checkSession() {
        fetch(statusUrl, { credentials: 'same-origin', cache: 'no-store' })
            .then(function (res) {
                if (res.status !== 401) {
                    return null;
                }
                return res.json().catch(function () {
                    return { notice: 'signedout' };
                });
            })
            .then(function (data) {
                if (!data) {
                    return;
                }
                var notice = data.notice === 'relogin' ? 'relogin' : 'signedout';
                window.location.href = loginUrl + '?notice=' + notice;
            })
            .catch(function () {});
    }

    setInterval(checkSession, 3000);
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            checkSession();
        }
    });
})();
</script>

