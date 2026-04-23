<?php

spl_autoload_register(function ($className) {
    $prefix  = 'Beitragsanalyse\\';
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
use Beitragsanalyse\classes\Presenter\BeitragsanalysePreferencesPresenter;

try {
    require_once(__DIR__ . '/../../system/common.php');
    require_once(__DIR__ . '/../../system/login_valid.php');

    $gL10n->addLanguageFolderPath(ADMIDIO_PATH . FOLDER_PLUGINS . '/beitragsanalyse/languages');

    if (!$gCurrentUser->isAdministrator()) {
        throw new Exception('SYS_NO_RIGHTS');
    }

    // -------------------------------------------------------------------------
    // Handle save
    // -------------------------------------------------------------------------
    if (isset($_GET['mode']) && $_GET['mode'] === 'save') {
        $gCurrentSession->getFormObject($_POST['adm_csrf_token']);

        $arrayFields = ['beitragsanalyse_roles_view_plugin'];
        $fields      = [
            'beitragsanalyse_enabled',
            'beitragsanalyse_roles_view_plugin',
            'beitragsanalyse_category_sparten',
            'beitragsanalyse_category_family',
            'beitragsanalyse_field_beitrag',
        ];

        foreach ($fields as $field) {
            $value = $_POST[$field] ?? (in_array($field, $arrayFields) ? [] : '');
            Beitragsanalyse::saveConfigValue($field, $value);
        }

        admRedirect(SecurityUtils::encodeUrl(
            ADMIDIO_URL . FOLDER_PLUGINS . '/beitragsanalyse/preferences.php',
            ['saved' => '1']
        ));
    }

    // -------------------------------------------------------------------------
    // Render form
    // -------------------------------------------------------------------------
    $overview = new Overview('beitragsanalyse');
    BeitragsanalysePreferencesPresenter::buildForm($overview);

    if (isset($_GET['saved'])) {
        $overview->assignTemplateVariable('savedMessage', $gL10n->get('SYS_SAVE_DATA'));
    }

    $gNavigation->addUrl(CURRENT_URL, $gL10n->get('SYS_SETTINGS') . ': ' . $gL10n->get('PLG_BEITRAGSANALYSE_HEADLINE'));

    $page = new PagePresenter('adm_plugin_beitragsanalyse_preferences');
    $page->addHtml($overview->html('preferences.plugin.beitragsanalyse.tpl'));
    $page->show();

} catch (Throwable $e) {
    echo $e->getMessage();
}
