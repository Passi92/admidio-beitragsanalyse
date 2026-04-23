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

use Beitragsanalyse\classes\Beitragsanalyse;

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

    if (!isset($page)) {
        $gNavigation->addStartUrl(CURRENT_URL, $gL10n->get('PLG_BEITRAGSANALYSE_HEADLINE'), 'bi-bar-chart-fill');
    }

    $pluginBeitragsanalyse = Beitragsanalyse::getInstance();
    $pluginBeitragsanalyse->doRender(isset($page) ? $page : null);

} catch (Throwable $e) {
    echo $e->getMessage();
}
