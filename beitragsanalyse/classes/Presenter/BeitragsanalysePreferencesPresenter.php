<?php

namespace Beitragsanalyse\classes\Presenter;

use Admidio\Infrastructure\Utils\SecurityUtils;
use Admidio\UI\Presenter\FormPresenter;

use Beitragsanalyse\classes\Beitragsanalyse;
use Smarty\Smarty;

/**
 ***********************************************************************************************
 * BeitragsanalysePreferencesPresenter
 *
 * Builds and returns the HTML of the plugin's settings form.  The form is shown by Admidio's
 * Plugin Manager inside the "Plugins" preferences panel.
 *
 * Settings exposed:
 *   1. Plugin enabled / disabled
 *   2. Roles allowed to see the plugin  (multiselect)
 *   3. Role category = Sportgruppen     (single select from DB)
 *   4. Role category = Familienmitglieder (single select from DB)
 *   5. Profile field = Beitrag          (single select from DB)
 *
 * @copyright Pascal Christmann
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */
class BeitragsanalysePreferencesPresenter
{
    /**
     * Generates the preferences form HTML and returns it as a string.
     *
     * @param  Smarty $smarty  Smarty instance provided by the Plugin Manager.
     * @return string          Rendered HTML of the preferences form.
     * @throws \Exception|\Smarty\Exception
     */
    public static function createBeitragsanalyseForm(Smarty $smarty): string
    {
        global $gL10n, $gCurrentSession, $gCurrentOrgId, $gDb;

        $pluginBeitragsanalyse = Beitragsanalyse::getInstance();
        $formValues            = $pluginBeitragsanalyse::getPluginConfig();

        // ---------------------------------------------------------------------
        // Build the FormPresenter
        // ---------------------------------------------------------------------
        $formBeitragsanalyse = new FormPresenter(
            'adm_preferences_form_beitragsanalyse',
            $pluginBeitragsanalyse::getPluginPath() . '/templates/preferences.plugin.beitragsanalyse.tpl',
            SecurityUtils::encodeUrl(
                ADMIDIO_URL . FOLDER_MODULES . '/preferences.php',
                ['mode' => 'save', 'panel' => 'beitragsanalyse']
            ),
            null,
            ['class' => 'form-preferences']
        );

        // ---------------------------------------------------------------------
        // 1. Plugin enabled
        // ---------------------------------------------------------------------
        $formBeitragsanalyse->addSelectBox(
            'beitragsanalyse_enabled',
            $gL10n->get('PLG_BEITRAGSANALYSE_ENABLED'),
            [
                1 => $gL10n->get('SYS_ACTIVATED'),
                0 => $gL10n->get('SYS_DEACTIVATED'),
            ],
            [
                'defaultValue'                  => $formValues['beitragsanalyse_enabled']['value'],
                'showContextDependentFirstEntry' => false,
                'helpTextId'                    => 'PLG_BEITRAGSANALYSE_ENABLED_DESC',
            ]
        );

        // ---------------------------------------------------------------------
        // 2. Roles allowed to view the plugin  (multiselect)
        // ---------------------------------------------------------------------
        $availableRoles = $pluginBeitragsanalyse::getAvailableRoles();
        $formBeitragsanalyse->addSelectBox(
            'beitragsanalyse_roles_view_plugin',
            $gL10n->get('PLG_BEITRAGSANALYSE_ROLES_VIEW_PLUGIN'),
            $availableRoles,
            [
                'defaultValue'          => $formValues['beitragsanalyse_roles_view_plugin']['value'],
                'multiselect'           => true,
                'maximumSelectionNumber' => count($availableRoles),
                'helpTextId'            => 'PLG_BEITRAGSANALYSE_ROLES_VIEW_PLUGIN_DESC',
            ]
        );

        // ---------------------------------------------------------------------
        // 3. Role category for Sportgruppen
        // ---------------------------------------------------------------------
        $formBeitragsanalyse->addSelectBoxFromSql(
            'beitragsanalyse_category_sparten',
            $gL10n->get('PLG_BEITRAGSANALYSE_CATEGORY_SPARTEN'),
            $gDb,
            'SELECT cat_id, cat_name
               FROM ' . TBL_CATEGORIES . '
              WHERE cat_type   = \'ROL\'
                AND cat_org_id = ' . $gCurrentOrgId . '
           ORDER BY cat_name',
            [],
            [
                'defaultValue'                  => $formValues['beitragsanalyse_category_sparten']['value'],
                'showContextDependentFirstEntry' => true,
                'helpTextId'                    => 'PLG_BEITRAGSANALYSE_CATEGORY_SPARTEN_DESC',
            ]
        );

        // ---------------------------------------------------------------------
        // 4. Role category for family memberships
        // ---------------------------------------------------------------------
        $formBeitragsanalyse->addSelectBoxFromSql(
            'beitragsanalyse_category_family',
            $gL10n->get('PLG_BEITRAGSANALYSE_CATEGORY_FAMILY'),
            $gDb,
            'SELECT cat_id, cat_name
               FROM ' . TBL_CATEGORIES . '
              WHERE cat_type   = \'ROL\'
                AND cat_org_id = ' . $gCurrentOrgId . '
           ORDER BY cat_name',
            [],
            [
                'defaultValue'                  => $formValues['beitragsanalyse_category_family']['value'],
                'showContextDependentFirstEntry' => true,   // allows "none / not configured"
                'helpTextId'                    => 'PLG_BEITRAGSANALYSE_CATEGORY_FAMILY_DESC',
            ]
        );

        // ---------------------------------------------------------------------
        // 5. Profile field that holds the Beitrag amount
        //    Only show fields of numeric/decimal types to reduce clutter.
        // ---------------------------------------------------------------------
        $formBeitragsanalyse->addSelectBoxFromSql(
            'beitragsanalyse_field_beitrag',
            $gL10n->get('PLG_BEITRAGSANALYSE_FIELD_BEITRAG'),
            $gDb,
            "SELECT usf_id, usf_name
               FROM " . TBL_USER_FIELDS . "
              WHERE usf_type IN ('DECIMAL', 'NUMBER', 'TEXT')
           ORDER BY usf_name",
            [],
            [
                'defaultValue'                  => $formValues['beitragsanalyse_field_beitrag']['value'],
                'showContextDependentFirstEntry' => true,
                'helpTextId'                    => 'PLG_BEITRAGSANALYSE_FIELD_BEITRAG_DESC',
            ]
        );

        // ---------------------------------------------------------------------
        // Save button
        // ---------------------------------------------------------------------
        $formBeitragsanalyse->addSubmitButton(
            'adm_btn_save',
            $gL10n->get('SYS_SAVE'),
            ['icon' => 'bi-check-lg']
        );

        // Register form with session (CSRF protection) and assign to Smarty
        $gCurrentSession->addFormObject($formBeitragsanalyse);
        return $formBeitragsanalyse->addToSmarty($smarty);
    }
}
