{$page_context = CerberusContexts::CONTEXT_TASK}
{$page_context_id = $task->id}

<div style="float:left;">
	<h1>{$task->title}</h1>
</div>

<div style="float:right;">
	{$ctx = Extension_DevblocksContext::get($page_context)}
	{include file="devblocks:cerberusweb.core::search/quick_search.tpl" view=$ctx->getSearchView() return_url="{devblocks_url}c=search&context={$ctx->manifest->params.alias}{/devblocks_url}"}
</div>

<div style="clear:both;"></div>

<div class="cerb-profile-toolbar">
	<form class="toolbar" action="{devblocks_url}{/devblocks_url}" method="post" style="margin-bottom:5px;">
		<input type="hidden" name="c" value="tasks">
		<input type="hidden" name="a" value="">
		<input type="hidden" name="id" value="{$page_context_id}">
		<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">
		
		<!-- Toolbar -->
		<span>
		{$object_watchers = DAO_ContextLink::getContextLinks($page_context, array($page_context_id), CerberusContexts::CONTEXT_WORKER)}
		{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=$page_context context_id=$page_context_id full=true}
		</span>

		<!-- Macros -->
		{devblocks_url assign=return_url full=true}c=profiles&type=task&id={$page_context_id}-{$task->title|devblocks_permalink}{/devblocks_url}
		{include file="devblocks:cerberusweb.core::internal/macros/display/button.tpl" context=$page_context context_id=$page_context_id macros=$macros return_url=$return_url}		

		<!-- Edit -->
		<button type="button" id="btnDisplayTaskEdit" title="{'common.edit'|devblocks_translate|capitalize}"><span class="glyphicons glyphicons-cogwheel"></span></button>

		<button type="button" title="{'common.refresh'|devblocks_translate|capitalize}" onclick="document.location='{devblocks_url}c=profiles&type=task&id={$page_context_id}-{$task->title|devblocks_permalink}{/devblocks_url}';">&nbsp;<span class="glyphicons glyphicons-refresh"></span></a>&nbsp;</button>
	</form>
	
	{if $pref_keyboard_shortcuts}
	<small>
		{'common.keyboard'|devblocks_translate|lower}:
		(<b>e</b>) {'common.edit'|devblocks_translate|lower}
		{if !empty($macros)}(<b>m</b>) {'common.macros'|devblocks_translate|lower} {/if}
		(<b>1-9</b>) change tab
	</small> 
	{/if}
</div>

<fieldset class="properties">
	<legend>Task</legend>
	
	<div style="margin-left:15px;">
	{foreach from=$properties item=v key=k name=props}
		<div class="property">
			{if $k == 'status'}
				<b>{'common.status'|devblocks_translate|capitalize}:</b>
				{if $task->is_completed}
					<span class="glyphicons glyphicons-circle-ok" style="vertical-align:middle;"></span> <span style="color:rgb(0,150,0);font-weight:bold;">{'task.is_completed'|devblocks_translate|capitalize}</span>
				{else}
					{'status.open'|devblocks_translate|capitalize}
				{/if}
			{elseif $k == 'due_date'}
				<b>{'task.due_date'|devblocks_translate|capitalize}:</b>
				<abbr title="{$task->due_date|devblocks_date}" style="{if !$task->is_completed && $task->due_date < time()}font-weight:bold;color:rgb(150,0,0);{/if}">{$task->due_date|devblocks_prettytime}</abbr>
			{else}
				{include file="devblocks:cerberusweb.core::internal/custom_fields/profile_cell_renderer.tpl"}
			{/if}
		</div>
		{if $smarty.foreach.props.iteration % 3 == 0 && !$smarty.foreach.props.last}
			<br clear="all">
		{/if}
	{/foreach}
	<br clear="all">
	</div>
</fieldset>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/profile_fieldsets.tpl" properties=$properties_custom_fieldsets}

{include file="devblocks:cerberusweb.core::internal/profiles/profile_record_links.tpl" properties=$properties_links}

<div>
{include file="devblocks:cerberusweb.core::internal/notifications/context_profile.tpl" context=$page_context context_id=$page_context_id}
</div>

<div>
{include file="devblocks:cerberusweb.core::internal/macros/behavior/scheduled_behavior_profile.tpl" context=$page_context context_id=$page_context_id}
</div>

<div id="profileTaskTabs">
	<ul>
		{$tabs = [comments,activity,links]}

		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextComments&point={$point}&context={$page_context}&id={$page_context_id}{/devblocks_url}">{'common.comments'|devblocks_translate|capitalize} <div class="tab-badge">{DAO_Comment::count($page_context, $page_context_id)|default:0}</div></a></li>
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabActivityLog&scope=target&point={$point}&context={$page_context}&context_id={$page_context_id}{/devblocks_url}">{'common.activity_log'|devblocks_translate|capitalize}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextLinks&point={$point}&context={$page_context}&id={$page_context_id}{/devblocks_url}">{'common.links'|devblocks_translate} <div class="tab-badge">{DAO_ContextLink::count($page_context, $page_context_id)|default:0}</div></a></li>
		
		{foreach from=$tab_manifests item=tab_manifest}
			{$tabs[] = $tab_manifest->params.uri}
			<li><a href="{devblocks_url}ajax.php?c=profiles&a=showTab&ext_id={$tab_manifest->id}&point={$point}&context={$page_context}&context_id={$page_context_id}{/devblocks_url}"><i>{$tab_manifest->params.title|devblocks_translate}</i></a></li>
		{/foreach}
	</ul>
</div> 
<br>

<script type="text/javascript">
$(function() {
	var tabOptions = Devblocks.getDefaultjQueryUiTabOptions();
	tabOptions.active = Devblocks.getjQueryUiTabSelected('profileTaskTabs');
	
	var tabs = $("#profileTaskTabs").tabs(tabOptions);

	$('#btnDisplayTaskEdit').bind('click', function() {
		$popup = genericAjaxPopup('peek','c=internal&a=showPeekPopup&context={$page_context}&context_id={$page_context_id}',null,false,'500');
		$popup.one('task_save', function(event) {
			event.stopPropagation();
			document.location.href = '{devblocks_url}c=profiles&type=task&id={$page_context_id}{/devblocks_url}';
		});
	});
	
	{include file="devblocks:cerberusweb.core::internal/macros/display/menu_script.tpl" selector_button=null selector_menu=null}
	
});
</script>

<script type="text/javascript">
{if $pref_keyboard_shortcuts}
$(document).keypress(function(event) {
	if(event.altKey || event.ctrlKey || event.shiftKey || event.metaKey)
		return;
	
	if($(event.target).is(':input'))
		return;

	hotkey_activated = true;
	
	switch(event.which) {
		case 49:  // (1) tab cycle
		case 50:  // (2) tab cycle
		case 51:  // (3) tab cycle
		case 52:  // (4) tab cycle
		case 53:  // (5) tab cycle
		case 54:  // (6) tab cycle
		case 55:  // (7) tab cycle
		case 56:  // (8) tab cycle
		case 57:  // (9) tab cycle
		case 58:  // (0) tab cycle
			try {
				idx = event.which-49;
				$tabs = $("#profileTaskTabs").tabs();
				$tabs.tabs('option', 'active', idx);
			} catch(ex) { } 
			break;
		case 101:  // (E) edit
			try {
				$('#btnDisplayTaskEdit').click();
			} catch(ex) { } 
			break;
		case 109:  // (M) macros
			try {
				$('#btnDisplayMacros').click();
			} catch(ex) { } 
			break;
		default:
			// We didn't find any obvious keys, try other codes
			hotkey_activated = false;
			break;
	}
	
	if(hotkey_activated)
		event.preventDefault();
});
{/if}
</script>

{include file="devblocks:cerberusweb.core::internal/profiles/profile_common_scripts.tpl"}