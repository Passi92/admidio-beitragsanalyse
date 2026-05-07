<div id="plugin-beitragsanalyse-snapshot" class="admidio-plugin-content">
    <h3>
        {$l10n->get('PLG_BEITRAGSANALYSE_SNAPSHOT_HEADLINE', [$dateFormatted])}
        {if $label}<small class="text-muted">&mdash; {$label}</small>{/if}
    </h3>

    <p>
        <a href="{$historyUrl}" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i>
            {$l10n->get('PLG_BEITRAGSANALYSE_HISTORY')}
        </a>
    </p>

    <h4 class="mt-3">{$l10n->get('PLG_BEITRAGSANALYSE_SUMMARY_TITLE')}</h4>

    {if count($summary) == 0}
        <p class="text-muted">{$l10n->get('PLG_BEITRAGSANALYSE_NO_DATA')}</p>
    {else}
        <table class="table table-striped table-hover admidio-datatable" id="plg-beitragsanalyse-snapshot-summary">
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
</div>
