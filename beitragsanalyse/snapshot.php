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
 * Beitragsanalyse - per-Sparte view of a single saved snapshot.
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
    // Access check (same as analysis page)
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

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        throw new Exception('SYS_INVALID_PAGE_VIEW');
    }

    $snapshot = SnapshotRepository::find($gCurrentOrgId, $id);
    if ($snapshot === null) {
        throw new Exception('SYS_INVALID_PAGE_VIEW');
    }

    $overview = new Overview('beitragsanalyse');
    $overview->assignTemplateVariable('summary', $snapshot['summary']);
    $overview->assignTemplateVariable('label', $snapshot['label']);
    $overview->assignTemplateVariable('dateFormatted', date('d.m.Y H:i', strtotime($snapshot['created_at'])));
    $overview->assignTemplateVariable('historyUrl', SecurityUtils::encodeUrl(
        ADMIDIO_URL . FOLDER_PLUGINS . '/beitragsanalyse/history.php'
    ));

    $gNavigation->addUrl(CURRENT_URL, $gL10n->get('PLG_BEITRAGSANALYSE_HISTORY'));

    $page = new PagePresenter('adm_plugin_beitragsanalyse_snapshot');
    $page->addHtml($overview->html('snapshot.plugin.beitragsanalyse.tpl'));
    $page->show();

} catch (Throwable $e) {
    echo $e->getMessage();
}
