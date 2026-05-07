<div id="plugin-beitragsanalyse-history" class="admidio-plugin-content">
    <h3>{$l10n->get('PLG_BEITRAGSANALYSE_HISTORY_TITLE')}</h3>

    {if isset($deletedMessage)}<div class="alert alert-success">{$deletedMessage}</div>{/if}

    <p>
        <a href="{$backUrl}" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i>
            {$l10n->get('PLG_BEITRAGSANALYSE_BACK_TO_ANALYSIS')}
        </a>
    </p>

    {if count($snapshots) == 0}
        <p class="text-muted">{$l10n->get('PLG_BEITRAGSANALYSE_NO_SNAPSHOTS')}</p>
    {else}
        <table class="table table-striped table-hover admidio-datatable" id="plg-beitragsanalyse-history">
            <thead>
                <tr>
                    <th>{$l10n->get('PLG_BEITRAGSANALYSE_DATE')}</th>
                    <th>{$l10n->get('PLG_BEITRAGSANALYSE_LABEL')}</th>
                    <th class="text-end">{$l10n->get('PLG_BEITRAGSANALYSE_TOTAL')}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            {foreach $snapshots as $row}
                <tr>
                    <td>
                        <a href="{$row.view_url}">{$row.date_formatted}</a>
                    </td>
                    <td>
                        <a href="{$row.view_url}">{if $row.label}{$row.label}{else}<span class="text-muted">&mdash;</span>{/if}</a>
                    </td>
                    <td class="text-end">{$row.total_formatted}&nbsp;€</td>
                    <td class="text-end">
                        <a href="{$row.view_url}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i>
                        </a>
                        {if $isAdmin}
                            <form method="post" action="{$deleteUrl}" class="d-inline"
                                  onsubmit="return confirm('{$l10n->get('PLG_BEITRAGSANALYSE_DELETE_CONFIRM')|escape:'javascript'}');">
                                <input type="hidden" name="adm_csrf_token" value="{$csrfToken}">
                                <input type="hidden" name="id" value="{$row.id}">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="{$l10n->get('SYS_DELETE')}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        {/if}
                    </td>
                </tr>
            {/foreach}
            </tbody>
        </table>
    {/if}
</div>
