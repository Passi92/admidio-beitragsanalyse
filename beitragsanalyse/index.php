<?php

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

    $pluginBeitragsanalyse = Beitragsanalyse::getInstance();
    $pluginBeitragsanalyse->doRender(isset($page) ? $page : null);

} catch (Throwable $e) {
    echo $e->getMessage();
}
