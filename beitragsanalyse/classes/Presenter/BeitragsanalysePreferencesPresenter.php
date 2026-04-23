<?php

namespace Beitragsanalyse\classes\Presenter;

use Admidio\Infrastructure\Plugins\Overview;
use Admidio\Infrastructure\Utils\SecurityUtils;
use Admidio\UI\Presenter\FormPresenter;

use Beitragsanalyse\classes\Beitragsanalyse;

/**
 ***********************************************************************************************
 * BeitragsanalysePreferencesPresenter
 *
 * Builds the plugin settings form and assigns it to an Overview's Smarty instance.
 * Render by calling $overview->html('preferences.plugin.beitragsanalyse.tpl') afterwards.
 *
 * @copyright Pascal Christmann
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */
class BeitragsanalysePreferencesPresenter
{
    /**
     * Builds the preferences form and assigns all Smarty variables to the given Overview.
     * Call $overview->html('preferences.plugin.beitragsanalyse.tpl') to render.
     *
     * @throws \Exception|\Smarty\Exception
     */
    public static function buildForm(Overview $overview): void
    {
        global $gL10n, $gCurrentSession, $gCurrentOrgId, $gDb;

        $formValues = Beitragsanalyse::getPluginConfig();
        $smarty     = $overview->createSmartyObject();

        $templateFile = ADMIDIO_PATH . FOLDER_PLUGINS . '/beitragsanalyse/templates/preferences.plugin.beitragsanalyse.tpl';
        $actionUrl    = SecurityUtils::encodeUrl(
            ADMIDIO_URL . FOLDER_PLUGINS . '/beitragsanalyse/preferences.php',
            ['mode' => 'save']
        );

        $form = new FormPresenter(
            'adm_preferences_form_beitragsanalyse',
            $templateFile,
            $actionUrl,
            null,
            ['class' => 'form-preferences']
        );

        // 1. Plugin enabled
        $form->addSelectBox(
            'beitragsanalyse_enabled',
            $gL10n->get('PLG_BEITRAGSANALYSE_ENABLED'),
            [1 => $gL10n->get('SYS_ACTIVATED'), 0 => $gL10n->get('SYS_DEACTIVATED')],
            [
                'defaultValue'                  => $formValues['beitragsanalyse_enabled']['value'],
                'showContextDependentFirstEntry' => false,
                'helpTextId'                    => 'PLG_BEITRAGSANALYSE_ENABLED_DESC',
            ]
        );

        // 2. Roles allowed to view the plugin (multiselect)
        $availableRoles = Beitragsanalyse::getAvailableRoles();
        $form->addSelectBox(
            'beitragsanalyse_roles_view_plugin',
            $gL10n->get('PLG_BEITRAGSANALYSE_ROLES_VIEW_PLUGIN'),
            $availableRoles,
            [
                'defaultValue'           => $formValues['beitragsanalyse_roles_view_plugin']['value'],
                'multiselect'            => true,
                'maximumSelectionNumber' => count($availableRoles),
                'helpTextId'             => 'PLG_BEITRAGSANALYSE_ROLES_VIEW_PLUGIN_DESC',
            ]
        );

        // 3. Role category for Sportgruppen
        $form->addSelectBoxFromSql(
            'beitragsanalyse_category_sparten',
            $gL10n->get('PLG_BEITRAGSANALYSE_CATEGORY_SPARTEN'),
            $gDb,
            ['query'  => 'SELECT cat_id, cat_name FROM ' . TBL_CATEGORIES . '
                           WHERE cat_type = ? AND cat_org_id = ? ORDER BY cat_name',
             'params' => ['ROL', $gCurrentOrgId]],
            [
                'defaultValue'                  => $formValues['beitragsanalyse_category_sparten']['value'],
                'showContextDependentFirstEntry' => true,
                'helpTextId'                    => 'PLG_BEITRAGSANALYSE_CATEGORY_SPARTEN_DESC',
            ]
        );

        // 4. Role category for family memberships
        $form->addSelectBoxFromSql(
            'beitragsanalyse_category_family',
            $gL10n->get('PLG_BEITRAGSANALYSE_CATEGORY_FAMILY'),
            $gDb,
            ['query'  => 'SELECT cat_id, cat_name FROM ' . TBL_CATEGORIES . '
                           WHERE cat_type = ? AND cat_org_id = ? ORDER BY cat_name',
             'params' => ['ROL', $gCurrentOrgId]],
            [
                'defaultValue'                  => $formValues['beitragsanalyse_category_family']['value'],
                'showContextDependentFirstEntry' => true,
                'helpTextId'                    => 'PLG_BEITRAGSANALYSE_CATEGORY_FAMILY_DESC',
            ]
        );

        // 5. Profile field holding the fee amount
        $form->addSelectBoxFromSql(
            'beitragsanalyse_field_beitrag',
            $gL10n->get('PLG_BEITRAGSANALYSE_FIELD_BEITRAG'),
            $gDb,
            ['query'  => "SELECT usf_id, usf_name FROM " . TBL_USER_FIELDS . "
                           WHERE usf_type IN ('DECIMAL', 'NUMBER', 'TEXT') ORDER BY usf_name",
             'params' => []],
            [
                'defaultValue'                  => $formValues['beitragsanalyse_field_beitrag']['value'],
                'showContextDependentFirstEntry' => true,
                'helpTextId'                    => 'PLG_BEITRAGSANALYSE_FIELD_BEITRAG_DESC',
            ]
        );

        $form->addSubmitButton('adm_btn_save', $gL10n->get('SYS_SAVE'), ['icon' => 'bi-check-lg']);

        $gCurrentSession->addFormObject($form);
        $form->addToSmarty($smarty);
    }
}
