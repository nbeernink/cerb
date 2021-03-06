<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formBatchUpdate" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="task">
<input type="hidden" name="action" value="startBulkUpdateJson">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="ids" value="{$ids}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset class="peek">
	<legend>{'common.bulk_update.with'|devblocks_translate|capitalize}</legend>
	<label><input type="radio" name="filter" value="" {if empty($ids)}checked{/if}> {'common.bulk_update.filter.all'|devblocks_translate}</label> 
	{if !empty($ids)}
		<label><input type="radio" name="filter" value="checks" {if !empty($ids)}checked{/if}> {'common.bulk_update.filter.checked'|devblocks_translate}</label> 
	{else}
		<label><input type="radio" name="filter" value="sample"> {'common.bulk_update.filter.random'|devblocks_translate} </label><input type="text" name="filter_sample_size" size="5" maxlength="4" value="100" class="input_number">
	{/if}
</fieldset>

<fieldset class="peek">
	<legend>Set Fields</legend>
	<table cellspacing="0" cellpadding="2" width="100%">
		<tr>
			<td width="0%" nowrap="nowrap" align="right">{'task.due_date'|devblocks_translate|capitalize}:</td>
			<td width="100%">
				<input type="text" name="due" size="35" value=""><button type="button" onclick="devblocksAjaxDateChooser(this.form.due,'#dateBulkTaskDue');"><span class="glyphicons glyphicons-calendar"></span></button>
				<div id="dateBulkTaskDue"></div>
			</td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" align="right">{'common.status'|devblocks_translate|capitalize}:</td>
			<td width="100%">
				<select name="status">
					<option value=""></option>
					<option value="0">{'status.open'|devblocks_translate}</option>
					<option value="1">{'status.completed'|devblocks_translate}</option>
					{if $active_worker->hasPriv('core.tasks.actions.delete')}
					<option value="2">{'status.deleted'|devblocks_translate}</option>
					{/if}
				</select>
				<button type="button" onclick="this.form.status.selectedIndex = 1;">{'status.open'|devblocks_translate|lower}</button>
				<button type="button" onclick="this.form.status.selectedIndex = 2;">{'status.completed'|devblocks_translate|lower}</button>
				{if $active_worker->hasPriv('core.tasks.actions.delete')}
				<button type="button" onclick="this.form.status.selectedIndex = 3;">{'status.deleted'|devblocks_translate|lower}</button>
				{/if}
			</td>
		</tr>
		
		{if 1}
		<tr>
			<td width="0%" nowrap="nowrap" align="right" valign="top">{'common.owner'|devblocks_translate|capitalize}:</td>
			<td width="100%">
				<button type="button" class="chooser-abstract" data-field-name="owner" data-context="{CerberusContexts::CONTEXT_WORKER}" data-single="true" data-query="" data-autocomplete="if-null"><span class="glyphicons glyphicons-search"></span></button>
				<ul class="bubbles chooser-container"></ul>
			</td>
		</tr>
		{/if}

		{if $active_worker->hasPriv('core.watchers.assign')}
		<tr>
			<td width="0%" nowrap="nowrap" align="right" valign="top">Add watchers:</td>
			<td width="100%">
				<div>
					<button type="button" class="chooser-abstract" data-field-name="do_watcher_add_ids[]" data-context="{CerberusContexts::CONTEXT_WORKER}" data-query="isDisabled:n" data-autocomplete="true"><span class="glyphicons glyphicons-search"></span></button>
					<ul class="bubbles chooser-container" style="display:block;"></ul>
				</div>
			</td>
		</tr>
		{/if}
		
		{if $active_worker->hasPriv('core.watchers.unassign')}
		<tr>
			<td width="0%" nowrap="nowrap" align="right" valign="top">Remove watchers:</td>
			<td width="100%">
				<div>
					<button type="button" class="chooser-abstract" data-field-name="do_watcher_remove_ids[]" data-context="{CerberusContexts::CONTEXT_WORKER}" data-query="isDisabled:n" data-autocomplete="true"><span class="glyphicons glyphicons-search"></span></button>
					<ul class="bubbles chooser-container" style="display:block;"></ul>
				</div>
			</td>
		</tr>
		{/if}
		
	</table>
</fieldset>

{if !empty($custom_fields)}
<fieldset class="peek">
	<legend>Set Custom Fields</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=true}
</fieldset>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_TASK bulk=true}

{include file="devblocks:cerberusweb.core::internal/macros/behavior/bulk.tpl" macros=$macros}

<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
<br>
</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFind('#formBatchUpdate');
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'common.bulk_update'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		
		$popup.find('button.chooser-abstract').cerbChooserTrigger();
		
		$popup.find('button.submit').click(function() {
			genericAjaxPost('formBatchUpdate', '', null, function(json) {
				if(json.cursor) {
					// Pull the cursor
					var $tips = $('#{$view_id}_tips').html('');
					var $spinner = $('<span class="cerb-ajax-spinner"/>').appendTo($tips);
					genericAjaxGet($tips, 'c=internal&a=viewBulkUpdateWithCursor&view_id={$view_id}&cursor=' + json.cursor);
				}
				
				genericAjaxPopupClose($popup);
			});
		});
	});
});
</script>