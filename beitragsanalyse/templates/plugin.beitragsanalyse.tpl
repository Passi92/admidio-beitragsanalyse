<div id="plugin-{$name}" class="admidio-plugin-content">
    <h3>
        {$l10n->get('PLG_BEITRAGSANALYSE_HEADLINE')}
        {if $isAdmin}
            <a href="{$urlAdmidio}/adm_plugins/beitragsanalyse/preferences.php"
               class="btn btn-sm btn-secondary float-end"
               title="{$l10n->get('SYS_SETTINGS')}">
                <i class="bi bi-gear-fill"></i>
            </a>
        {/if}
    </h3>

    {* ------------------------------------------------------------------ *}
    {* Info / error state                                                   *}
    {* ------------------------------------------------------------------ *}
    {if isset($message) && $message != ''}
        <p class="text-muted">{$message}</p>
    {else}

    {* ------------------------------------------------------------------ *}
    {* Summary table: one row per Sparte                                   *}
    {* ------------------------------------------------------------------ *}
    <h4 class="mt-3">{$l10n->get('PLG_BEITRAGSANALYSE_SUMMARY_TITLE')}</h4>

    {if count($summary) == 0}
        <p class="text-muted">{$l10n->get('PLG_BEITRAGSANALYSE_NO_DATA')}</p>
    {else}
        <table class="table table-striped table-hover admidio-datatable" id="plg-beitragsanalyse-summary">
            <thead>
                <tr>
                    <th>{$l10n->get('PLG_BEITRAGSANALYSE_SPARTE')}</th>
                    <th class="text-end">{$l10n->get('PLG_BEITRAGSANALYSE_TOTAL_FEES')}</th>
                </tr>
            </thead>
            <tbody>
            {foreach $summary as $row}
                <tr class="{$row.class}">
                    <td>
                        {if $row.sparte == 'PLG_BEITRAGSANALYSE_NO_SPARTE'}
                            {$l10n->get('PLG_BEITRAGSANALYSE_NO_SPARTE')}
                        {elseif $row.sparte == 'PLG_BEITRAGSANALYSE_TOTAL'}
                            {$l10n->get('PLG_BEITRAGSANALYSE_TOTAL')}
                        {else}
                            {$row.sparte}
                        {/if}
                    </td>
                    <td class="text-end">{$row.summe}&nbsp;€</td>
                </tr>
            {/foreach}
            </tbody>
        </table>
    {/if}

    {* ------------------------------------------------------------------ *}
    {* Detail table: one row per person per Sparte                         *}
    {* ------------------------------------------------------------------ *}
    <h4 class="mt-4">{$l10n->get('PLG_BEITRAGSANALYSE_DETAIL_TITLE')}</h4>

    {if count($details) == 0}
        <p class="text-muted">{$l10n->get('PLG_BEITRAGSANALYSE_NO_DATA')}</p>
    {else}
        <table class="table table-striped table-hover table-sm admidio-datatable" id="plg-beitragsanalyse-detail">
            <thead>
                <tr>
                    <th>{$l10n->get('SYS_LASTNAME')}</th>
                    <th>{$l10n->get('SYS_FIRSTNAME')}</th>
                    <th>{$l10n->get('PLG_BEITRAGSANALYSE_SPARTE')}</th>
                    <th class="text-end">{$l10n->get('PLG_BEITRAGSANALYSE_SHARE')}</th>
                    <th>{$l10n->get('PLG_BEITRAGSANALYSE_FAMILY')}</th>
                </tr>
            </thead>
            <tbody>
            {foreach $details as $row}
                <tr>
                    <td>{$row.nachname}</td>
                    <td>{$row.vorname}</td>
                    <td>{$row.sparte}</td>
                    <td class="text-end">{$row.kosten}&nbsp;€</td>
                    <td>{$row.familie}</td>
                </tr>
            {/foreach}
            </tbody>
        </table>
    {/if}

    {/if}
</div>
