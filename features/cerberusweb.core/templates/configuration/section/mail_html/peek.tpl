{$form_id = "frmMailHtmlTemplatePeek{uniqid()}"}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="html_template">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

{$tabs_id = "tabs{uniqid()}"}

<div class="cerb-tabs-panel">
	<ul>
		<li><a href="#htmlTemplateEditor{$tabs_id}">Editor</a></li>
		<li><a href="#htmlTemplateCustomFields{$tabs_id}">{'common.custom_fields'|devblocks_translate|capitalize}</a></li>
		<li><a href="#htmlTemplateAttachments{$tabs_id}">{'common.attachments'|devblocks_translate|capitalize}</a></li>
	</ul>
	
	<div id="htmlTemplateEditor{$tabs_id}">
		<fieldset class="peek">
			<legend>Mail Template</legend>
			
			<table cellspacing="0" cellpadding="2" border="0" width="98%">
				<tr>
					<td width="1%" nowrap="nowrap"><b>{'common.name'|devblocks_translate}:</b></td>
					<td width="99%">
						<input type="text" name="name" value="{$model->name}" style="width:98%;" autofocus="true">
					</td>
				</tr>
			</table>
			
		<textarea name="content" style="width:98%;height:200px;border:1px solid rgb(180,180,180);padding:2px;" spellcheck="false">
{if $model->content}{$model->content}{else}&lt;div id="body"&gt;
{literal}{{message_body}}{/literal}
&lt;/div&gt;

&lt;style type="text/css"&gt;
#body {
  font-family: Arial, Verdana, sans-serif;
  font-size: 10pt;
}

a { 
  color: black;
}

blockquote {
  color: rgb(0, 128, 255);
  font-style: italic;
  margin-left: 0px;
  border-left: 1px solid rgb(0, 128, 255);
  padding-left: 5px;
}

blockquote a {
  color: rgb(0, 128, 255);
}
&lt;/style&gt;{/if}</textarea>
	
		</fieldset>
		
		<fieldset class="peek">
			<legend>Signature</legend>
			<textarea name="signature" style="width:98%;height:150px;border:1px solid rgb(180,180,180);padding:2px;" spellcheck="false" placeholder="Leave blank to use the default group signature.">{$model->signature}</textarea>

			<div>
				<button type="button" class="cerb-popupmenu-trigger" onclick="">Insert placeholder &#x25be;</button>
				
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
			
		</fieldset>
	</div>
	
	<div id="htmlTemplateCustomFields{$tabs_id}">
		{if !empty($custom_fields)}
		<fieldset class="peek">
			<legend>{'common.custom_fields'|devblocks_translate}</legend>
			{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
		</fieldset>
		{/if}
		
		{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE context_id=$model->id}
	</div>
	
	<div id="htmlTemplateAttachments{$tabs_id}">
		{$a_map = DAO_AttachmentLink::getLinksAndAttachments(CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE, $model->id)}
		{$links = $a_map.links}
		{$attachments = $a_map.attachments}
		
		<b>{'common.attachments'|devblocks_translate}:</b><br>
		<button type="button" class="chooser_file"><span class="glyphicons glyphicons-paperclip"></span></button>
		<ul class="chooser-container bubbles cerb-attachments-container" style="display:block;">
		{if !empty($links) && !empty($attachments)}
			{foreach from=$links item=link name=links}
			{$attachment = $attachments.{$link->attachment_id}}
			{if !empty($attachment)}
				<li>
					{$attachment->display_name}
					( {$attachment->storage_size|devblocks_prettybytes}	- 
					{if !empty($attachment->mime_type)}{$attachment->mime_type}{else}{'display.convo.unknown_format'|devblocks_translate|capitalize}{/if}
					 )
					<input type="hidden" name="file_ids[]" value="{$attachment->id}">
					<a href="javascript:;" onclick="$(this).parent().remove();"><span class="glyphicons glyphicons-circle-remove"></span></a>
				</li>
			{/if}
			{/foreach}
		{/if}
		</ul>
	</div>
</div>

<div class="status"></div>

{if !empty($model->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to delete this HTML template?
	</div>
	
	<button type="button" class="delete"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();"><span class="glyphicons glyphicons-circle-minus" style="color:rgb(200,0,0);"></span> {'common.cancel'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="buttons" style="margin-top:20px;">
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if !empty($model->id)}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

{if !empty($model->id)}
<div style="float:right;">
	<a href="{devblocks_url}c=profiles&type=html_template&id={$model->id}-{$model->name|devblocks_permalink}{/devblocks_url}">view full record</a>
</div>
<br clear="all">
{/if}
</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFind('#{$form_id}');
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'HTML Template'}");
		$popup.css('overflow', 'inherit');
		
		$popup.find('.cerb-tabs-panel').tabs();
		
		var $content = $popup.find('textarea[name=content]');
		var $signature = $popup.find('textarea[name=signature]');
		var $attachments_container = $popup.find('UL.cerb-attachments-container');
		
		// Buttons
		
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		
		// Attachments
		
		$popup.find('button.chooser_file').each(function() {
			ajax.chooserFile(this,'file_ids');
		});
		
		// markItUp
		
		try {
			var markitupHTMLSettings = $.extend(true, { }, markitupHTMLDefaults);
			var markitupParsedownSettings = $.extend(true, { }, markitupParsedownDefaults);
			
			markitupParsedownSettings.markupSet.splice(
				4,
				0,
				{ name:'Upload an Image', openWith: 
					function(markItUp) {
						var $chooser=genericAjaxPopup('chooser','c=internal&a=chooserOpenFile&single=1',null,true,'750');
						
						$chooser.one('chooser_save', function(event) {
							if(!event.response || 0 == event.response)
								return;
							
							$signature.insertAtCursor("![inline-image](" + event.response[0].url + ")");
	
							// Add an attachment link
							
							if(0 == $attachments_container.find('input:hidden[value=' + event.response[0].id + ']').length) {
								var $li = $('<li/>');
								$li.text(event.response[0].name + ' ( ' + event.response[0].size + ' bytes - ' + event.response[0].type + ' )');
								
								var $hidden = $('<input type="hidden" name="file_ids[]">')
									.val(event.response[0].id)
									.appendTo($li)
									;
								
								var $a = $('<a href="javascript:;"><span class="glyphicons glyphicons-circle-remove"></span></a>');
								$a.click(function() {
									$(this).parent().remove();
								});
								$a.appendTo($li);
								
								$attachments_container.append($li);
							}
						});
					},
					key: 'U',
					className:'image-inline'
				}
			);
			
			markitupHTMLSettings.markupSet.splice(
				13,
				0,
				{ name:'Upload an Image', openWith: 
					function(markItUp) {
						var $chooser=genericAjaxPopup('chooser','c=internal&a=chooserOpenFile&single=1',null,true,'750');
						
						$chooser.one('chooser_save', function(event) {
							if(!event.response || 0 == event.response)
								return;
							
							$content.insertAtCursor("<img src=\"" + event.response[0].url + "\" alt=\"\">");
							
							// Add an attachment link
							
							if(0 == $attachments_container.find('input:hidden[value=' + event.response[0].id + ']').length) {
								var $li = $('<li/>');
								$li.text(event.response[0].name + ' ( ' + event.response[0].size + ' bytes - ' + event.response[0].type + ' )');
								
								var $hidden = $('<input type="hidden" name="file_ids[]">')
									.val(event.response[0].id)
									.appendTo($li)
									;
								
								var $a = $('<a href="javascript:;"><span class="glyphicons glyphicons-circle-remove"></span></a>');
								$a.click(function() {
									$(this).parent().remove();
								});
								$a.appendTo($li);
								
								$attachments_container.append($li);
							}
						});
					},
					key: 'U',
					className:'image-inline'
				}
			);			
			
			delete markitupHTMLSettings.previewParserPath;
			delete markitupHTMLSettings.previewTemplatePath;
			
			markitupHTMLSettings.previewParser = function(content) {
				// Replace 'message_body' with sample text
				content = content.replace('{literal}{{message_body}}{/literal}', '<blockquote>This text is quoted.</blockquote><p>This text contains <b>bold</b>, <i>italics</i>, <a href="javascript:;">links</a>, and <code>code formatting</code>.</p><p><ul><li>These are unordered</li><li>list items</li></ul></p><p>This is an inline image:</p><p><img src="{devblocks_url}c=avatars&w=address&id=0{/devblocks_url}"></p>');
				return content;
			};
			
			delete markitupParsedownSettings.previewParserPath;
			delete markitupParsedownSettings.previewTemplatePath;
			delete markitupParsedownSettings.previewInWindow;
			
			markitupParsedownSettings.previewParserPath = DevblocksAppPath + 'ajax.php?c=profiles&a=handleSectionAction&section=html_template&action=getSignatureParsedownPreview&_csrf_token=' + $('meta[name="_csrf_token"]').attr('content');
			
			$content.markItUp(markitupHTMLSettings);
			$signature.markItUp(markitupParsedownSettings);
			
			var $preview = $popup.find('.markItUpHeader a[title="Preview"]');

			// Default with the preview panel open
			$preview.trigger('mouseup');
			
		} catch(e) {
			if(window.console)
				console.log(e);
		}
		
		// Placeholders
		
		var $placeholder_menu_trigger = $popup.find('button.cerb-popupmenu-trigger');
		var $placeholder_menu = $popup.find('ul.menu').hide();
		
		$placeholder_menu.menu({
			select: function(event, ui) {
				var token = ui.item.attr('data-token');
				var label = ui.item.attr('data-label');
				
				if(undefined == token || undefined == label)
					return;
				
				$signature.focus().insertAtCursor('{literal}{{{/literal}' + token + '{literal}}}{/literal}');
			}
		});
		
		$placeholder_menu_trigger
			.click(
				function(e) {
					$placeholder_menu.toggle();
				}
			)
		;
	});
});
</script>