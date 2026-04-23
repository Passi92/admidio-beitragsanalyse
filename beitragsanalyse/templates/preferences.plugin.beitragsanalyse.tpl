<div id="plugin-beitragsanalyse-preferences" class="admidio-plugin-content">
<h3>{$l10n->get('SYS_SETTINGS')}: {$l10n->get('PLG_BEITRAGSANALYSE_HEADLINE')}</h3>
{if isset($savedMessage)}<div class="alert alert-success">{$savedMessage}</div>{/if}
<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>
    {include file='sys-template-parts/form.input.tpl'  data=$elements['adm_csrf_token']}
    {include file='sys-template-parts/form.select.tpl' data=$elements['beitragsanalyse_enabled']}
    {include file='sys-template-parts/form.select.tpl' data=$elements['beitragsanalyse_roles_view_plugin']}
    {include file='sys-template-parts/form.select.tpl' data=$elements['beitragsanalyse_category_sparten']}
    {include file='sys-template-parts/form.select.tpl' data=$elements['beitragsanalyse_category_family']}
    {include file='sys-template-parts/form.select.tpl' data=$elements['beitragsanalyse_field_beitrag']}
    {include file='sys-template-parts/form.button.tpl' data=$elements['adm_btn_save']}
    <div class="form-alert" style="display: none;">&nbsp;</div>
</form>
{$javascript}
</div>
