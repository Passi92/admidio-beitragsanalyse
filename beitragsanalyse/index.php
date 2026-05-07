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
use Admidio\Infrastructure\Utils\SecurityUtils;
use Beitragsanalyse\classes\Beitragsanalyse;
use Beitragsanalyse\classes\SnapshotRepository;

/**
 ***********************************************************************************************
 * Beitragsanalyse
 *
 * Distributes member fees (Beiträge) proportionally across sport groups (Sparten)
 * and displays per-group totals as well as a per-member detail view.
 *
 * @copyright Pascal Christmann
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */
try {
    require_once(__DIR__ . '/../../system/common.php');

    $gL10n->addLanguageFolderPath(ADMIDIO_PATH . FOLDER_PLUGINS . '/beitragsanalyse/languages');

    // -------------------------------------------------------------------------
    // Handle "save snapshot" POST (admin only, CSRF-protected)
    // -------------------------------------------------------------------------
    if (isset($_GET['mode']) && $_GET['mode'] === 'save') {
        require_once(__DIR__ . '/../../system/login_valid.php');

        if (!$gCurrentUser->isAdministrator()) {
            throw new Exception('SYS_NO_RIGHTS');
        }

        $token = $_POST['adm_csrf_token'] ?? '';
        if ($token === '' || $token !== $gCurrentSession->getCsrfToken()) {
            throw new Exception('SYS_INVALID_PAGE_VIEW');
        }

        $stats = Beitragsanalyse::getInstance()->getCurrentSummary();
        if ($stats === null) {
            throw new Exception('PLG_BEITRAGSANALYSE_NOT_CONFIGURED');
        }

        $label = isset($_POST['label']) ? (string)$_POST['label'] : '';

        SnapshotRepository::save(
            $gCurrentOrgId,
            $gCurrentUser->getValue('usr_id'),
            $label,
            $stats['summary'],
            (float)$stats['total']
        );

        admRedirect(SecurityUtils::encodeUrl(
            ADMIDIO_URL . FOLDER_PLUGINS . '/beitragsanalyse/index.php',
            ['saved' => '1']
        ));
    }

    if (!isset($page)) {
        $gNavigation->addStartUrl(CURRENT_URL, $gL10n->get('PLG_BEITRAGSANALYSE_HEADLINE'), 'bi-bar-chart-fill');
    }

    $pluginBeitragsanalyse = Beitragsanalyse::getInstance();
    $pluginBeitragsanalyse->doRender(isset($page) ? $page : null);

} catch (Throwable $e) {
    echo $e->getMessage();
}
