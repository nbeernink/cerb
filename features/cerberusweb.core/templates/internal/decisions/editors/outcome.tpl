<form id="frmDecisionOutcome{$id}" onsubmit="return false;">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="">
{if isset($id)}<input type="hidden" name="id" value="{$id}">{/if}
{if isset($parent_id)}<input type="hidden" name="parent_id" value="{$parent_id}">{/if}
{if isset($type)}<input type="hidden" name="type" value="{$type}">{/if}
{if isset($trigger_id)}<input type="hidden" name="trigger_id" value="{$trigger_id}">{/if}
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<b>{'common.title'|devblocks_translate|capitalize}:</b><br>
<input type="text" name="title" value="{$model->title}" style="width:100%;"><br>
<br>

{$seq = 0}

{if empty($model->params.groups)}
	<fieldset>
		<legend>
			If <a href="javascript:;">all&#x25be;</a> of these conditions are satisfied
			<a href="javascript:;" onclick="$(this).closest('fieldset').remove();"><span class="glyphicons glyphicons-circle-minus" style="color:rgb(200,0,0);"></span></a>
		</legend>
		<input type="hidden" name="nodes[]" value="all">
		
		<ul class="rules" style="margin:0px;list-style:none;padding:0px 0px 2px 0px;"></ul>
	</fieldset>

{else}
	{foreach from=$model->params.groups item=group_data}
	<fieldset>
		<legend>
			If <a href="javascript:;">{if !empty($group_data.any)}any{else}all{/if}&#x25be;</a> of these conditions are satisfied
			<a href="javascript:;" onclick="$(this).closest('fieldset').remove();"><span class="glyphicons glyphicons-circle-minus" style="color:rgb(200,0,0);"></span></a>
		</legend>
		<input type="hidden" name="nodes[]" value="{if !empty($group_data.any)}any{else}all{/if}">
		
		<ul class="rules" style="margin:0px;list-style:none;padding:0px 0px 2px 0px;">
			{if isset($group_data.conditions) && is_array($group_data.conditions)}
			{foreach from=$group_data.conditions item=params}
				<li style="padding-bottom:5px;" id="condition{$seq}">
					<input type="hidden" name="nodes[]" value="{$seq}">
					<input type="hidden" name="condition{$seq}[condition]" value="{$params.condition}">
					<a href="javascript:;" onclick="$(this).closest('li').remove();"><span class="glyphicons glyphicons-circle-minus" style="color:rgb(200,0,0);"></span></a>
					<b style="cursor:move;">{$conditions.{$params.condition}.label}</b>&nbsp;
					<div style="margin-left:20px;">
						{$event->renderCondition({$params.condition},$trigger,$params,$seq)}
					</div>
				</li>
				{$seq = $seq + 1}
			{/foreach}
			{/if}
		</ul>
	</fieldset>
	{/foreach}
{/if}

<div id="divDecisionOutcomeToolbar{$id}" style="display:none;">
	<div class="tester"></div>
	
	<button type="button" class="cerb-popupmenu-trigger" onclick="">Insert placeholder &#x25be;</button>
	<button type="button" class="tester">{'common.test'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="genericAjaxPopup('help', 'c=internal&a=showSnippetHelpPopup', { my:'left top' , at:'left+20 top+20'}, false, '600');">Help</button>

	{$types = $values._types}
	{function tree level=0}
		{foreach from=$keys item=data key=idx}
			{if is_array($data)}
				<li>
					<div>{$idx|capitalize}</div>
					<ul>
						{tree keys=$data level=$level+1}
					</ul>
				</li>
			{else}
				{$type = $types.{$data->key}}
				<li data-token="{$data->key}{if $type == Model_CustomField::TYPE_DATE}|date{/if}" data-label="{$data->label}"><div style="font-weight:bold;">{$data->l|capitalize}</div></li>
			{/if}
		{/foreach}
	{/function}
	
	<ul class="menu" style="width:150px;">
	{tree keys=$placeholders}
	</ul>
</div>

</form>

<form id="frmDecisionOutcomeAdd{$id}" action="javascript:;" onsubmit="return false;">
<input type="hidden" name="seq" value="{$seq}">
<input type="hidden" name="condition" value="">
{if isset($trigger_id)}<input type="hidden" name="trigger_id" value="{$trigger_id}">{/if}
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset>
	<legend>Add Condition</legend>

	<button type="button" class="condition cerb-popupmenu-trigger">Add Condition &#x25be;</button>
	<button type="button" class="group">Add Group</button>
	<ul class="cerb-popupmenu" style="border:0;">
		<li style="background:none;">
			<input type="text" size="16" class="input_search filter">
		</li>
		{foreach from=$conditions key=token item=condition}
		<li><a href="javascript:;" token="{$token}">{$condition.label}</a></li>
		{/foreach}
	</ul>
</fieldset>
</form>

{if isset($id)}
<fieldset class="delete" style="display:none;">
	<legend>Delete this outcome?</legend>
	<p>Are you sure you want to permanently delete this outcome and its children?</p>
	<button type="button" class="green" onclick="genericAjaxPost('frmDecisionOutcome{$id}','','c=internal&a=saveDecisionDeletePopup',function() { genericAjaxPopupDestroy('node_outcome{$id}'); genericAjaxGet('decisionTree{$trigger_id}','c=internal&a=showDecisionTree&id={$trigger_id}'); });"> {'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" class="red" onclick="$(this).closest('fieldset').hide().next('form.toolbar').show();"> {'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

{$status_div = "status_{uniqid()}"}

<form class="toolbar">
	{if !isset($id)}
		<button type="button" onclick="genericAjaxPost('frmDecisionOutcome{$id}','','c=internal&a=saveDecisionPopup',function() { genericAjaxPopupDestroy('node_outcome{$id}'); genericAjaxGet('decisionTree{$trigger_id}','c=internal&a=showDecisionTree&id={$trigger_id}'); });"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{else}
		<button type="button" onclick="genericAjaxPost('frmDecisionOutcome{$id}','','c=internal&a=saveDecisionPopup',function() { genericAjaxPopupDestroy('node_outcome{$id}'); genericAjaxGet('decisionTree{$trigger_id}','c=internal&a=showDecisionTree&id={$trigger_id}'); });"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_and_close'|devblocks_translate|capitalize}</button>
		<button type="button" onclick="genericAjaxPost('frmDecisionOutcome{$id}','','c=internal&a=saveDecisionPopup',function() { Devblocks.showSuccess('#{$status_div}', 'Saved!'); genericAjaxGet('decisionTree{$trigger_id}','c=internal&a=showDecisionTree&id={$trigger_id}'); });"><span class="glyphicons glyphicons-circle-arrow-right" style="color:rgb(0,180,0);"></span> {'common.save_and_continue'|devblocks_translate|capitalize}</button>
		<button type="button" onclick="genericAjaxPopup('simulate_behavior','c=internal&a=showBehaviorSimulatorPopup&trigger_id={$trigger_id}','reuse',false,'500');"> <span class="glyphicons glyphicons-cogwheel"></span> Simulator</button>
		<button type="button" onclick="$(this).closest('form').hide().prev('fieldset.delete').show();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>
	{/if}
</form>

<div id="{$status_div}" style="display:none;"></div>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFetch('node_outcome{$id}');
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{if empty($id)}New {/if}Outcome");
		$popup.find('input:text').first().focus();
		$popup.css('overflow', 'inherit');

		var $frm = $popup.find('form#frmDecisionOutcome{$id}');
		var $legend = $popup.find('fieldset legend');
		var $menu = $popup.find('fieldset ul.cerb-popupmenu:first'); 

		$frm.find('fieldset UL.rules')
			.sortable({ 'items':'li', 'placeholder':'ui-state-highlight', 'handle':'> b', 'connectWith':'#frmDecisionOutcome{$id} fieldset ul.rules' })
			;

		var $funcGroupAnyToggle = function(e) {
			var $any = $(this).closest('fieldset').find('input:hidden:first');
			
			if("any" == $any.val()) {
				$(this).html("all&#x25be;");
				$any.val('all');
			} else {
				$(this).html("any&#x25be;");
				$any.val('any');
			}
		}
		
		$legend.find('a').click($funcGroupAnyToggle);

		$popup.find('BUTTON.chooser_worker.unbound').each(function() {
			var seq = $(this).closest('fieldset').find('input:hidden[name="conditions[]"]').val();
			ajax.chooser(this,'cerberusweb.contexts.worker','condition'+seq+'[worker_id]', { autocomplete:true });
			$(this).removeClass('unbound');
		});

		var $frmAdd = $popup.find('#frmDecisionOutcomeAdd{$id}');

		$frmAdd.find('button.group')
			.click(function(e) {
				var $group = $('<fieldset></fieldset>');
				$group.append('<legend>If <a href="javascript:;">all&#x25be;</a> of these conditions are satisfied <a href="javascript:;" onclick="$(this).closest(\'fieldset\').remove();"><span class="glyphicons glyphicons-circle-minus" style="color:rgb(200,0,0);"></span></a></legend>');
				$group.append('<input type="hidden" name="nodes[]" value="all">');
				$group.append('<ul class="rules" style="margin:0px;list-style:none;padding:0px;padding-bottom:5px;"></ul>');
				$group.find('legend > a').click($funcGroupAnyToggle);
				$frm.append($group);

				$frm.find('fieldset UL.rules')
					.sortable({ 'items':'li', 'placeholder':'ui-state-highlight', 'handle':'> b', 'connectWith':'#frmDecisionOutcome{$id} fieldset ul.rules' })
					;
			})
			;
		
		// Placeholders
		
		$popup.delegate(':text.placeholders, textarea.placeholders', 'focus', function(e) {
			var $toolbar = $('#divDecisionOutcomeToolbar{$id}');
			var src = (null==e.srcElement) ? e.target : e.srcElement;
			if(0 == $(src).nextAll('#divDecisionOutcomeToolbar{$id}').length) {
				$toolbar.find('div.tester').html('');
				$toolbar.find('ul.menu').hide();
				$toolbar.show().insertAfter(src);
			}
		});
		
		// Placeholder menu
		
		var $divPlaceholderMenu = $('#divDecisionOutcomeToolbar{$id}');
		
		var $placeholder_menu_trigger = $divPlaceholderMenu.find('button.cerb-popupmenu-trigger');
		var $placeholder_menu = $divPlaceholderMenu.find('ul.menu').hide();
		
		// Quick insert token menu
		
		$placeholder_menu.menu({
			select: function(event, ui) {
				var token = ui.item.attr('data-token');
				var label = ui.item.attr('data-label');
				
				if(undefined == token || undefined == label)
					return;
				
				var $toolbar = $('DIV#divDecisionOutcomeToolbar{$id}');
				var $field = null;
				
				if($toolbar.data('src')) {
					$field = $toolbar.data('src');
				
				} else {
					$field = $toolbar.prev(':text, textarea');
				}
				
				if(null == $field)
					return;
				
				$field.focus().insertAtCursor('{literal}{{{/literal}' + token + '{literal}}}{/literal}');
			}
		});
		
		$divPlaceholderMenu.find('button.tester').click(function(e) {
			var divTester = $divPlaceholderMenu.find('div.tester').first();
			
			var $toolbar = $('DIV#divDecisionOutcomeToolbar{$id}');
			var $field = $toolbar.prev(':text, textarea');
			
			if(null == $field)
				return;
			
			var regexpName = /^(.*?)(\[.*?\])$/;
			var hits = regexpName.exec($field.attr('name'));
			
			if(null == hits || hits.length < 3)
				return;
			
			var strNamespace = hits[1];
			var strName = hits[2];
			
			genericAjaxPost($(this).closest('form').attr('id'), divTester, 'c=internal&a=testDecisionEventSnippets&prefix=' + strNamespace + '&field=' + strName);
		});
		
		$placeholder_menu_trigger
			.click(
				function(e) {
					$placeholder_menu.toggle();
				}
			)
			.bind('remove',
				function(e) {
					$placeholder_menu.remove();
				}
			)
		;
		
		// Quick insert condition menu

		var $menu_trigger = $frmAdd.find('button.condition.cerb-popupmenu-trigger');
		var $menu = $frmAdd.find('ul.cerb-popupmenu');
		$menu_trigger.data('menu', $menu);

		$menu_trigger
			.click(
				function(e) {
					var $menu = $(this).data('menu');

					if($menu.is(':visible')) {
						$menu.hide();
						return;
					}
					
					$menu
						.show()
						.find('> li input:text')
						.focus()
						.select()
						;
				}
			)
		;

		$menu.find('> li > input.filter').keyup(
			function(e) {
				var term = $(this).val().toLowerCase();
				var $menu = $(this).closest('ul.cerb-popupmenu');
				$menu.find('> li a').each(function(e) {
					if(-1 != $(this).html().toLowerCase().indexOf(term)) {
						$(this).parent().show();
					} else {
						$(this).parent().hide();
					}
				});
			}
		);

		$menu.find('> li').click(function(e) {
			e.stopPropagation();
			if(!$(e.target).is('li'))
				return;

			$(this).find('a').trigger('click');
		});

		$menu.find('> li > a').click(function() {
			var token = $(this).attr('token');
			var $frmDecAdd = $('#frmDecisionOutcomeAdd{$id}');
			$frmDecAdd.find('input[name=condition]').val(token);
			var $this = $(this);
			
			genericAjaxPost('frmDecisionOutcomeAdd{$id}','','c=internal&a=doDecisionAddCondition',function(html) {
				var $ul = $('#frmDecisionOutcome{$id} UL.rules:last');
				
				var seq = parseInt($frmDecAdd.find('input[name=seq]').val());
				if(null == seq)
					seq = 0;

				var $html = $('<div style="margin-left:20px;"/>').html(html);
				
				var $container = $('<li style="padding-bottom:5px;"/>').attr('id','condition'+seq);
				$container.append($('<input type="hidden" name="nodes[]">').attr('value', seq));
				$container.append($('<input type="hidden">').attr('name', 'condition'+seq+'[condition]').attr('value',token));
				$container.append($('<a href="javascript:;" onclick="$(this).closest(\'li\').remove();"><span class="glyphicons glyphicons-circle-minus" style="color:rgb(200,0,0);"></span></a>'));
				$container.append('&nbsp;');
				$container.append($('<b style="cursor:move;"/>').text($this.text()));
				$container.append('&nbsp;');

				$ul.append($container);
				$container.append($html);

				$html.find('BUTTON.chooser_worker.unbound').each(function() {
					ajax.chooser(this,'cerberusweb.contexts.worker','condition'+seq+'[worker_id]', { autocomplete:true });
					$(this).removeClass('unbound');
				});
				
				$menu.find('input:text:first').focus().select();

				// [TODO] This can take too long to increment when packets are arriving quickly
				$frmDecAdd.find('input[name=seq]').val(1+seq);
			});
		});

	}); // end popup_open
	
});
</script>