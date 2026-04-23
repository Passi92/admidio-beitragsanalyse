<?php

namespace Beitragsanalyse\classes;

abstract class PluginAbstract
{
    /**
     * Returns the plugin folder name for use with Admidio's Overview class.
     */
    public static function getPluginPath(): string
    {
        return static::PLUGIN_FOLDER_NAME;
    }

    /**
     * Returns structured config (keyed by field name, each entry has 'type' and 'value'),
     * merging JSON defaults with values stored in adm_plugin_preferences.
     * Expected by BeitragsanalysePreferencesPresenter.
     */
    public static function getPluginConfig(): array
    {
        global $gDb, $gCurrentOrgId;

        $jsonFile = __DIR__ . '/../' . static::PLUGIN_FOLDER_NAME . '.json';
        $config   = [];
        if (file_exists($jsonFile)) {
            $manifest = json_decode(file_get_contents($jsonFile), true);
            $config   = $manifest['defaultConfig'] ?? [];
        }

        $prefix = strtoupper(static::PLUGIN_FOLDER_NAME) . '__';
        $sql    = 'SELECT plp_name, plp_value
                     FROM adm_plugin_preferences
                    WHERE plp_org_id = ?
                      AND plp_name LIKE ?';
        $stmt = $gDb->queryPrepared($sql, [$gCurrentOrgId, $prefix . '%']);

        while ($row = $stmt->fetch()) {
            $key = strtolower(substr($row['plp_name'], strlen($prefix)));
            if (!isset($config[$key])) {
                continue;
            }
            $raw = $row['plp_value'];
            $config[$key]['value'] = ($config[$key]['type'] === 'array')
                ? (json_decode($raw, true) ?? [$raw])
                : $raw;
        }

        return $config;
    }

    /**
     * Returns a flat key => value map of config values, suitable for business logic.
     */
    public function getPluginConfigValues(): array
    {
        $config = static::getPluginConfig();
        $flat   = [];
        foreach ($config as $key => $cfg) {
            $flat[$key] = $cfg['value'];
        }
        return $flat;
    }

    /**
     * Saves a single config value to adm_plugin_preferences (upsert).
     */
    public static function saveConfigValue(string $key, mixed $value): void
    {
        global $gDb, $gCurrentOrgId;

        if (is_array($value)) {
            $value = json_encode($value);
        }

        $prefix  = strtoupper(static::PLUGIN_FOLDER_NAME) . '__';
        $plpName = $prefix . $key;

        $sql  = 'SELECT plp_id FROM adm_plugin_preferences WHERE plp_org_id = ? AND plp_name = ?';
        $stmt = $gDb->queryPrepared($sql, [$gCurrentOrgId, $plpName]);

        if ($row = $stmt->fetch()) {
            $gDb->queryPrepared(
                'UPDATE adm_plugin_preferences SET plp_value = ? WHERE plp_id = ?',
                [$value, $row['plp_id']]
            );
        } else {
            $gDb->queryPrepared(
                'INSERT INTO adm_plugin_preferences (plp_org_id, plp_name, plp_value) VALUES (?, ?, ?)',
                [$gCurrentOrgId, $plpName, $value]
            );
        }
    }
}
