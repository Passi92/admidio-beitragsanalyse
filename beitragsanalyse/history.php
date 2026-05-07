<?php

spl_autoload_register(function ($className) {
    $prefix = 'Beitragsanalyse\\';
    $baseDir = __DIR__ . '/';
    if (strncmp($prefix, $className, strlen($prefix)) !== 0) {
        return;
    }
    $file = $baseDir . str_replace('\\', '/', substr($className, strlen($prefix))) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

use Admidio\Infrastructure\Exception;
use Admidio\Infrastructure\Plugins\Overview;
use Admidio\Infrastructure\Utils\SecurityUtils;
use Admidio\UI\Presenter\PagePresenter;
use Beitragsanalyse\classes\Beitragsanalyse;
use Beitragsanalyse\classes\SnapshotRepository;

/**
 ***********************************************************************************************
 * Beitragsanalyse - history listing of saved per-Sparte snapshots.
 *
 * @copyright Pascal Christmann
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */
try {
    require_once(__DIR__ . '/../../system/common.php');
    require_once(__DIR__ . '/../../system/login_valid.php');

    $gL10n->addLanguageFolderPath(ADMIDIO_PATH . FOLDER_PLUGINS . '/beitragsanalyse/languages');

    // -------------------------------------------------------------------------
    // Access check: same rules as the main analysis page.
    // -------------------------------------------------------------------------
    $configValues = Beitragsanalyse::getInstance()->getPluginConfigValues();

    if (empty($configValues['beitragsanalyse_enabled'])) {
        throw new Exception('SYS_MODULE_DISABLED');
    }

    $viewRoles = (array)($configValues['beitragsanalyse_roles_view_plugin'] ?? ['All']);
    if (!in_array('All', $viewRoles, true) && count($viewRoles) > 0) {
        $userRoles = $gCurrentUser->getRoleMemberships();
        if (empty(array_intersect($viewRoles, $userRoles))) {
            throw new Exception('SYS_NO_RIGHTS');
        }
    }

    // -------------------------------------------------------------------------
    // Handle "delete snapshot" POST (admin only, CSRF-protected)
    // -------------------------------------------------------------------------
    if (isset($_GET['mode']) && $_GET['mode'] === 'delete') {
        if (!$gCurrentUser->isAdministrator()) {
            throw new Exception('SYS_NO_RIGHTS');
        }

        $token = $_POST['adm_csrf_token'] ?? '';
        if ($token === '' || $token !== $gCurrentSession->getCsrfToken()) {
            throw new Exception('SYS_INVALID_PAGE_VIEW');
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id > 0) {
            SnapshotRepository::delete($gCurrentOrgId, $id);
        }

        admRedirect(SecurityUtils::encodeUrl(
            ADMIDIO_URL . FOLDER_PLUGINS . '/beitragsanalyse/history.php',
            ['deleted' => '1']
        ));
    }

    // -------------------------------------------------------------------------
    // Render listing
    // -------------------------------------------------------------------------
    $snapshots = SnapshotRepository::findAll($gCurrentOrgId);

    foreach ($snapshots as &$row) {
        $row['view_url'] = SecurityUtils::encodeUrl(
            ADMIDIO_URL . FOLDER_PLUGINS . '/beitragsanalyse/snapshot.php',
            ['id' => $row['id']]
        );
        $row['total_formatted'] = number_format((float)$row['total'], 2, ',', '.');
        $row['date_formatted']  = date('d.m.Y H:i', strtotime($row['created_at']));
    }
    unset($row);

    $overview = new Overview('beitragsanalyse');
    $overview->assignTemplateVariable('isAdmin', $gCurrentUser->isAdministrator());
    $overview->assignTemplateVariable('csrfToken', $gCurrentSession->getCsrfToken());
    $overview->assignTemplateVariable('snapshots', $snapshots);
    $overview->assignTemplateVariable('deleteUrl', SecurityUtils::encodeUrl(
        ADMIDIO_URL . FOLDER_PLUGINS . '/beitragsanalyse/history.php',
        ['mode' => 'delete']
    ));
    $overview->assignTemplateVariable('backUrl', SecurityUtils::encodeUrl(
        ADMIDIO_URL . FOLDER_PLUGINS . '/beitragsanalyse/index.php'
    ));
    if (!empty($_GET['deleted'])) {
        $overview->assignTemplateVariable('deletedMessage', $gL10n->get('PLG_BEITRAGSANALYSE_DELETED_OK'));
    }

    $gNavigation->addUrl(CURRENT_URL, $gL10n->get('PLG_BEITRAGSANALYSE_HISTORY'));

    $page = new PagePresenter('adm_plugin_beitragsanalyse_history');
    $page->addHtml($overview->html('history.plugin.beitragsanalyse.tpl'));
    $page->show();

} catch (Throwable $e) {
    echo $e->getMessage();
}
