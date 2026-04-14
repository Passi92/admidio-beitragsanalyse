<?php

namespace Beitragsanalyse\classes;

use Admidio\Infrastructure\Plugins\Overview;
use Admidio\Infrastructure\Plugins\PluginAbstract;
use Throwable;

/**
 ***********************************************************************************************
 * Beitragsanalyse - main plugin class
 *
 * Distributes each member's Beitrag proportionally across their active Sparten roles.
 * Family memberships are detected via a dedicated category: the single Beitrag value of
 * the family is divided equally among all family members, then again across their Sparten.
 *
 * Logic mirrors the standalone stats.py script so both produce identical numbers.
 *
 * @copyright Pascal Christmann
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */
class Beitragsanalyse extends PluginAbstract
{
    /** Folder name of this plugin inside the Admidio plugins directory. */
    public const PLUGIN_FOLDER_NAME = 'beitragsanalyse';

    private static ?self $instance = null;

    // -----------------------------------------------------------------------------------------
    // Singleton
    // -----------------------------------------------------------------------------------------

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // -----------------------------------------------------------------------------------------
    // Rendering
    // -----------------------------------------------------------------------------------------

    /**
     * Checks access rights, reads settings, runs the computation and passes data to the
     * Smarty template via the Overview presenter.
     *
     * @param object|null $page  HtmlPage when embedded in a full page, null for sidebar use.
     */
    public function doRender(?object $page = null): void
    {
        global $gValidLogin, $gCurrentUser, $gL10n;

        $pluginPath = self::getPluginPath();
        $overview   = new Overview($pluginPath);

        // --- Plugin enabled? ---
        $configValues = $this->getPluginConfigValues();

        if (empty($configValues['beitragsanalyse_enabled'])) {
            $overview->assignTemplateVariable('message', $gL10n->get('SYS_MODULE_DISABLED'));
            $this->output($overview, $page);
            return;
        }

        // --- Login required ---
        if (!$gValidLogin) {
            $overview->assignTemplateVariable('message', $gL10n->get('SYS_NO_RIGHTS'));
            $this->output($overview, $page);
            return;
        }

        // --- Role-based access ---
        $viewRoles = (array)($configValues['beitragsanalyse_roles_view_plugin'] ?? ['All']);
        if (!in_array('All', $viewRoles, true) && count($viewRoles) > 0) {
            $userRoles = $gCurrentUser->getRoleMemberships();
            if (empty(array_intersect($viewRoles, $userRoles))) {
                $overview->assignTemplateVariable('message', $gL10n->get('SYS_NO_RIGHTS'));
                $this->output($overview, $page);
                return;
            }
        }

        // --- Required settings must be configured ---
        $spartenCatId   = (int)($configValues['beitragsanalyse_category_sparten'] ?? 0);
        $familyCatId    = (int)($configValues['beitragsanalyse_category_family']  ?? 0);
        $beitragFieldId = (int)($configValues['beitragsanalyse_field_beitrag']    ?? 0);

        if ($spartenCatId === 0 || $beitragFieldId === 0) {
            $overview->assignTemplateVariable(
                'message',
                $gL10n->get('PLG_BEITRAGSANALYSE_NOT_CONFIGURED')
            );
            $this->output($overview, $page);
            return;
        }

        // --- Compute and render ---
        $stats = $this->computeStats($spartenCatId, $familyCatId, $beitragFieldId);

        $overview->assignTemplateVariable('summary', $stats['summary']);
        $overview->assignTemplateVariable('details', $stats['details']);

        $this->output($overview, $page);
    }

    /**
     * Handles the two output modes: embedded in a full HtmlPage vs. standalone/sidebar.
     */
    private function output(Overview $overview, ?object $page): void
    {
        if ($page !== null) {
            $page->addHtml($overview->html());
        } else {
            echo $overview->html();
        }
    }

    // -----------------------------------------------------------------------------------------
    // Core computation (mirrors stats.py logic)
    // -----------------------------------------------------------------------------------------

    /**
     * Distributes member fees across Sparten roles.
     *
     * Individual members:
     *   fee / number_of_sparten  →  assigned to each Sparte
     *
     * Family members (all share one fee stored on any family member):
     *   family_fee / family_size  →  per-member share
     *   per-member share / member_sparten_count  →  per-Sparte share
     *
     * @param int $spartenCatId   ID of the role category used as "Sportgruppen"
     * @param int $familyCatId    ID of the role category for family memberships (0 = disabled)
     * @param int $beitragFieldId ID of the profile field holding the fee amount
     * @return array{summary: list<array>, details: list<array>}
     */
    private function computeStats(int $spartenCatId, int $familyCatId, int $beitragFieldId): array
    {
        global $gDb;

        $today = date('Y-m-d');

        // ---- 1. Sparten roles ---------------------------------------------------------------
        $spartenRoles = [];   // rol_id => rol_name
        $sql = 'SELECT rol_id, rol_name
                  FROM ' . TBL_ROLES . '
                 WHERE rol_cat_id = ?
                   AND rol_valid  = 1
              ORDER BY rol_name';
        $stmt = $gDb->queryPrepared($sql, [$spartenCatId]);
        while ($row = $stmt->fetch()) {
            $spartenRoles[(int)$row['rol_id']] = $row['rol_name'];
        }

        if (empty($spartenRoles)) {
            return ['summary' => [], 'details' => []];
        }

        // ---- 2. Active Sparten memberships --------------------------------------------------
        $spartenMemberships = [];   // userId => [rolId, ...]
        $roleIds      = array_keys($spartenRoles);
        $placeholders = implode(',', array_fill(0, count($roleIds), '?'));
        $sql = 'SELECT mem_usr_id, mem_rol_id
                  FROM ' . TBL_MEMBERS . '
                 WHERE mem_rol_id IN (' . $placeholders . ')
                   AND mem_begin <= ?
                   AND mem_end   >= ?';
        $params = array_merge($roleIds, [$today, $today]);
        $stmt   = $gDb->queryPrepared($sql, $params);
        while ($row = $stmt->fetch()) {
            $spartenMemberships[(int)$row['mem_usr_id']][] = (int)$row['mem_rol_id'];
        }

        // ---- 3. Family memberships (optional) -----------------------------------------------
        $familyMemberships = [];    // userId  => familyRolId
        $familyRoleMembers = [];    // rolId   => [userId, ...]
        $familyRoleNames   = [];    // rolId   => rol_name

        if ($familyCatId > 0) {
            $sql = 'SELECT m.mem_usr_id, m.mem_rol_id, r.rol_name
                      FROM ' . TBL_MEMBERS . ' m
                      JOIN ' . TBL_ROLES   . ' r ON r.rol_id = m.mem_rol_id
                     WHERE r.rol_cat_id = ?
                       AND r.rol_valid  = 1
                       AND m.mem_begin <= ?
                       AND m.mem_end   >= ?';
            $stmt = $gDb->queryPrepared($sql, [$familyCatId, $today, $today]);
            while ($row = $stmt->fetch()) {
                $uid = (int)$row['mem_usr_id'];
                $rid = (int)$row['mem_rol_id'];
                $familyMemberships[$uid]   = $rid;
                $familyRoleMembers[$rid][] = $uid;
                $familyRoleNames[$rid]     = $row['rol_name'];
            }
        }

        // ---- 4. Beitrag values for all relevant users ----------------------------------------
        $allUserIds = array_unique(
            array_merge(array_keys($spartenMemberships), array_keys($familyMemberships))
        );
        $beitragValues = [];    // userId => float (only > 0)

        if (!empty($allUserIds)) {
            $placeholders = implode(',', array_fill(0, count($allUserIds), '?'));
            $sql = 'SELECT usd_usr_id, usd_value
                      FROM ' . TBL_USER_DATA . '
                     WHERE usd_usf_id  = ?
                       AND usd_usr_id IN (' . $placeholders . ')';
            $params = array_merge([$beitragFieldId], $allUserIds);
            $stmt   = $gDb->queryPrepared($sql, $params);
            while ($row = $stmt->fetch()) {
                $v = (float)str_replace(',', '.', (string)$row['usd_value']);
                if ($v > 0.0) {
                    $beitragValues[(int)$row['usd_usr_id']] = $v;
                }
            }
        }

        // ---- 5. User names ------------------------------------------------------------------
        $userNames = [];    // userId => ['first' => ..., 'last' => ...]

        if (!empty($allUserIds)) {
            $placeholders = implode(',', array_fill(0, count($allUserIds), '?'));
            // Admidio stores first/last name as profile field values with
            // internal names FIRST_NAME / LAST_NAME in adm_user_fields.
            $sql = 'SELECT u.usr_id,
                           fn.usd_value AS first_name,
                           ln.usd_value AS last_name
                      FROM ' . TBL_USERS . ' u
                 LEFT JOIN ' . TBL_USER_DATA   . ' fn
                        ON fn.usd_usr_id = u.usr_id
                       AND fn.usd_usf_id = (
                               SELECT usf_id FROM ' . TBL_USER_FIELDS . '
                                WHERE usf_name_intern = \'FIRST_NAME\' LIMIT 1
                           )
                 LEFT JOIN ' . TBL_USER_DATA   . ' ln
                        ON ln.usd_usr_id = u.usr_id
                       AND ln.usd_usf_id = (
                               SELECT usf_id FROM ' . TBL_USER_FIELDS . '
                                WHERE usf_name_intern = \'LAST_NAME\' LIMIT 1
                           )
                     WHERE u.usr_id IN (' . $placeholders . ')';
            $stmt = $gDb->queryPrepared($sql, $allUserIds);
            while ($row = $stmt->fetch()) {
                $userNames[(int)$row['usr_id']] = [
                    'first' => $row['first_name'] ?? '',
                    'last'  => $row['last_name']  ?? '',
                ];
            }
        }

        // ---- 6. Fee distribution ------------------------------------------------------------
        $details      = [];
        $sparteSums   = array_fill_keys(array_values($spartenRoles), 0.0);
        $noSparteCost = 0.0;

        // Individual members (not in any family role)
        foreach ($spartenMemberships as $userId => $userRoleIds) {
            if (isset($familyMemberships[$userId])) {
                continue;   // handled in the family block below
            }

            $beitrag = $beitragValues[$userId] ?? 0.0;
            if ($beitrag <= 0.0) {
                continue;
            }

            $count = count($userRoleIds);
            if ($count === 0) {
                $noSparteCost += $beitrag;
            } else {
                $perSparte = $beitrag / $count;
                foreach ($userRoleIds as $rolId) {
                    $sparteName           = $spartenRoles[$rolId];
                    $sparteSums[$sparteName] += $perSparte;
                    $details[] = [
                        'vorname'  => $userNames[$userId]['first'] ?? '',
                        'nachname' => $userNames[$userId]['last']  ?? '',
                        'sparte'   => $sparteName,
                        'kosten'   => round($perSparte, 2),
                        'familie'  => '',
                    ];
                }
            }
        }

        // Family groups
        foreach ($familyRoleMembers as $familyRolId => $memberIds) {
            // The shared family fee is the first non-zero Beitrag found among members.
            // (Matches stats.py behaviour: only one member carries the fee in the DB.)
            $totalCost = null;
            foreach ($memberIds as $uid) {
                if (isset($beitragValues[$uid])) {
                    $totalCost = $beitragValues[$uid];
                    break;
                }
            }
            if ($totalCost === null) {
                continue;
            }

            $familyName = $familyRoleNames[$familyRolId] ?? '';
            $perMember  = $totalCost / count($memberIds);

            foreach ($memberIds as $uid) {
                $userRoleIds = $spartenMemberships[$uid] ?? [];
                $count       = count($userRoleIds);

                if ($count === 0) {
                    $noSparteCost += $perMember;
                } else {
                    $perSparte = $perMember / $count;
                    foreach ($userRoleIds as $rolId) {
                        $sparteName              = $spartenRoles[$rolId];
                        $sparteSums[$sparteName] += $perSparte;
                        $details[] = [
                            'vorname'  => $userNames[$uid]['first'] ?? '',
                            'nachname' => $userNames[$uid]['last']  ?? '',
                            'sparte'   => $sparteName,
                            'kosten'   => round($perSparte, 2),
                            'familie'  => $familyName,
                        ];
                    }
                }
            }
        }

        // Sort detail rows by Nachname, then Vorname for readability
        usort($details, static function (array $a, array $b): int {
            return strcmp($a['nachname'] . $a['vorname'], $b['nachname'] . $b['vorname']);
        });

        // Format money values as German decimal strings (e.g. "123,45")
        foreach ($details as &$row) {
            $row['kosten'] = number_format($row['kosten'], 2, ',', '.');
        }
        unset($row);

        // ---- 7. Summary table ---------------------------------------------------------------
        $summary = [];
        $total   = 0.0;

        foreach ($sparteSums as $sparteName => $sum) {
            $summary[] = [
                'sparte' => $sparteName,
                'summe'  => number_format(round($sum, 2), 2, ',', '.'),
                'class'  => '',
            ];
            $total += $sum;
        }

        if ($noSparteCost > 0.005) {
            $summary[] = [
                'sparte' => 'PLG_BEITRAGSANALYSE_NO_SPARTE',  // translated in template
                'summe'  => number_format(round($noSparteCost, 2), 2, ',', '.'),
                'class'  => 'text-muted',
            ];
            $total += $noSparteCost;
        }

        $summary[] = [
            'sparte' => 'PLG_BEITRAGSANALYSE_TOTAL',          // translated in template
            'summe'  => number_format(round($total, 2), 2, ',', '.'),
            'class'  => 'fw-bold',
        ];

        return ['summary' => $summary, 'details' => $details];
    }

    // -----------------------------------------------------------------------------------------
    // Static helpers used by the PreferencesPresenter
    // -----------------------------------------------------------------------------------------

    /**
     * Returns role categories of the given type as id => name map.
     * Used to populate the Sparten- and Family-category dropdowns in settings.
     *
     * @param string $type  Admidio category type, e.g. 'ROL'
     */
    public static function getAvailableCategories(string $type = 'ROL'): array
    {
        global $gDb, $gCurrentOrgId;

        $sql = 'SELECT cat_id, cat_name
                  FROM ' . TBL_CATEGORIES . '
                 WHERE cat_type   = ?
                   AND cat_org_id = ?
              ORDER BY cat_name';
        $stmt       = $gDb->queryPrepared($sql, [$type, $gCurrentOrgId]);
        $categories = [];
        while ($row = $stmt->fetch()) {
            $categories[(int)$row['cat_id']] = $row['cat_name'];
        }
        return $categories;
    }

    /**
     * Returns numeric/decimal profile fields as id => name map.
     * Used to populate the Beitrag-field dropdown in settings.
     */
    public static function getAvailableProfileFields(): array
    {
        global $gDb;

        // Include field types that can hold a monetary amount.
        // Adjust usf_type values to match your Admidio version if needed.
        $sql = "SELECT usf_id, usf_name
                  FROM " . TBL_USER_FIELDS . "
                 WHERE usf_type IN ('DECIMAL', 'NUMBER', 'TEXT')
              ORDER BY usf_name";
        $stmt   = $gDb->queryPrepared($sql);
        $fields = [];
        while ($row = $stmt->fetch()) {
            $fields[(int)$row['usf_id']] = $row['usf_name'];
        }
        return $fields;
    }

    /**
     * Returns all valid roles of the current organisation as id => name map.
     * Used to populate the "visible for roles" multiselect in settings.
     *
     * @param bool  $idsOnly  When true, return only an array of IDs (used for default population).
     */
    public static function getAvailableRoles(bool $idsOnly = false): array
    {
        global $gDb, $gCurrentOrgId;

        $sql = 'SELECT rol_id, rol_name
                  FROM ' . TBL_ROLES . '
                 WHERE rol_valid  = 1
                   AND rol_org_id = ?
              ORDER BY rol_name';
        $stmt  = $gDb->queryPrepared($sql, [$gCurrentOrgId]);
        $roles = [];
        while ($row = $stmt->fetch()) {
            if ($idsOnly) {
                $roles[] = (int)$row['rol_id'];
            } else {
                $roles[(int)$row['rol_id']] = $row['rol_name'];
            }
        }
        return $roles;
    }
}
