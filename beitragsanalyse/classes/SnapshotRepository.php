<?php

namespace Beitragsanalyse\classes;

/**
 ***********************************************************************************************
 * SnapshotRepository
 *
 * Persists per-Sparte summary snapshots produced by Beitragsanalyse::computeStats()
 * to its own table (created on demand). Per-member detail rows are NOT saved.
 *
 * @copyright Pascal Christmann
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */
class SnapshotRepository
{
    public const TABLE = 'adm_plugin_beitragsanalyse_snapshots';

    private static bool $schemaChecked = false;

    /**
     * Creates the snapshots table on first use. Idempotent and cheap; the result is
     * cached per request so repeated calls in the same render are free.
     */
    public static function ensureSchema(): void
    {
        if (self::$schemaChecked) {
            return;
        }

        global $gDb;

        $sql = 'CREATE TABLE IF NOT EXISTS ' . self::TABLE . ' (
            pbs_id          INT           NOT NULL AUTO_INCREMENT,
            pbs_org_id      INT           NOT NULL,
            pbs_created_at  DATETIME      NOT NULL,
            pbs_created_by  INT           NULL,
            pbs_label       VARCHAR(255)  NULL,
            pbs_total       DECIMAL(12,2) NOT NULL,
            pbs_payload     MEDIUMTEXT    NOT NULL,
            PRIMARY KEY (pbs_id),
            KEY idx_pbs_org_created (pbs_org_id, pbs_created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';

        $gDb->queryPrepared($sql);
        self::$schemaChecked = true;
    }

    /**
     * Inserts a new snapshot and returns its id.
     *
     * @param array<int,array{sparte:string,summe:string,class:string}> $summary
     */
    public static function save(int $orgId, ?int $userId, ?string $label, array $summary, float $total): int
    {
        global $gDb;

        self::ensureSchema();

        $label = ($label !== null) ? trim($label) : '';
        if ($label === '') {
            $label = null;
        }

        $sql = 'INSERT INTO ' . self::TABLE . '
                  (pbs_org_id, pbs_created_at, pbs_created_by, pbs_label, pbs_total, pbs_payload)
                VALUES (?, ?, ?, ?, ?, ?)';

        $gDb->queryPrepared($sql, [
            $orgId,
            date('Y-m-d H:i:s'),
            $userId,
            $label,
            number_format(round($total, 2), 2, '.', ''),
            json_encode($summary, JSON_UNESCAPED_UNICODE),
        ]);

        return (int)$gDb->lastInsertId();
    }

    /**
     * Returns all snapshots for the given org, newest first.
     *
     * @return list<array{id:int,created_at:string,label:?string,total:string}>
     */
    public static function findAll(int $orgId): array
    {
        global $gDb;

        self::ensureSchema();

        $sql = 'SELECT pbs_id, pbs_created_at, pbs_label, pbs_total
                  FROM ' . self::TABLE . '
                 WHERE pbs_org_id = ?
              ORDER BY pbs_created_at DESC, pbs_id DESC';

        $stmt = $gDb->queryPrepared($sql, [$orgId]);
        $rows = [];
        while ($row = $stmt->fetch()) {
            $rows[] = [
                'id'         => (int)$row['pbs_id'],
                'created_at' => (string)$row['pbs_created_at'],
                'label'      => $row['pbs_label'],
                'total'      => (string)$row['pbs_total'],
            ];
        }
        return $rows;
    }

    /**
     * Returns one snapshot (with decoded payload) or null if not found / wrong org.
     *
     * @return array{id:int,created_at:string,label:?string,total:string,summary:array}|null
     */
    public static function find(int $orgId, int $id): ?array
    {
        global $gDb;

        self::ensureSchema();

        $sql = 'SELECT pbs_id, pbs_created_at, pbs_label, pbs_total, pbs_payload
                  FROM ' . self::TABLE . '
                 WHERE pbs_id = ? AND pbs_org_id = ?';

        $stmt = $gDb->queryPrepared($sql, [$id, $orgId]);
        $row  = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $summary = json_decode((string)$row['pbs_payload'], true);
        if (!is_array($summary)) {
            $summary = [];
        }

        return [
            'id'         => (int)$row['pbs_id'],
            'created_at' => (string)$row['pbs_created_at'],
            'label'      => $row['pbs_label'],
            'total'      => (string)$row['pbs_total'],
            'summary'    => $summary,
        ];
    }

    public static function delete(int $orgId, int $id): void
    {
        global $gDb;

        self::ensureSchema();

        $sql = 'DELETE FROM ' . self::TABLE . ' WHERE pbs_id = ? AND pbs_org_id = ?';
        $gDb->queryPrepared($sql, [$id, $orgId]);
    }
}
