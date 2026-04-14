<form {foreach $attributes as $attribute}{$attribute.attribute}="{$attribute.value}" {/foreach}>
    {include file='form.input.tpl'  data=$elements['adm_csrf_token']}
    {include file='form.select.tpl' data=$elements['beitragsanalyse_enabled']}
    {include file='form.select.tpl' data=$elements['beitragsanalyse_roles_view_plugin']}
    {include file='form.select.tpl' data=$elements['beitragsanalyse_category_sparten']}
    {include file='form.select.tpl' data=$elements['beitragsanalyse_category_family']}
    {include file='form.select.tpl' data=$elements['beitragsanalyse_field_beitrag']}
    {include file='form.button.tpl' data=$elements['adm_btn_save']}
</form>
{$javascript}
