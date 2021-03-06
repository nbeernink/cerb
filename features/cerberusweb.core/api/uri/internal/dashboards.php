<?php
/***********************************************************************
 | Cerb(tm) developed by Webgroup Media, LLC.
 |-----------------------------------------------------------------------
 | All source code & content (c) Copyright 2002-2016, Webgroup Media LLC
 |   unless specifically noted otherwise.
 |
 | This source code is released under the Devblocks Public License.
 | The latest version of this license can be found here:
 | http://cerb.io/license
 |
 | By using this software, you acknowledge having read this license
 | and agree to be bound thereby.
 | ______________________________________________________________________
 |	http://cerb.io	    http://webgroup.media
 ***********************************************************************/

if(class_exists('Extension_PageSection')):
class PageSection_InternalDashboards extends Extension_PageSection {
	function render() {}
	
	function renderWidgetAction() {
		@$widget_id = DevblocksPlatform::importGPC($_REQUEST['widget_id'], 'integer', 0);
		@$nocache = DevblocksPlatform::importGPC($_REQUEST['nocache'], 'boolean', false);
		Extension_WorkspaceWidget::renderWidgetFromCache($widget_id, true, $nocache);
	}
	
	function showWidgetPopupAction() {
		@$widget_id = DevblocksPlatform::importGPC($_REQUEST['widget_id'], 'integer', 0);
		
		$tpl = DevblocksPlatform::getTemplateService();

		if(empty($widget_id)) {
			// [TODO] Verify this ID
			@$workspace_tab_id = DevblocksPlatform::importGPC($_REQUEST['workspace_tab_id'], 'integer', 0);
			$tpl->assign('workspace_tab_id', $workspace_tab_id);
			
			$widget_extensions = Extension_WorkspaceWidget::getAll(false);
			$tpl->assign('widget_extensions', $widget_extensions);
			
			$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/add.tpl');
			
		} else {
			if(null != ($widget = DAO_WorkspaceWidget::get($widget_id))) {
				$tpl->assign('widget', $widget);
				
				if(null != ($extension = Extension_WorkspaceWidget::get($widget->extension_id))) {
					$tpl->assign('extension', $extension);
					$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/peek.tpl');
				}
			}
		}
	}
	
	function saveWidgetPopupAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$label = DevblocksPlatform::importGPC($_REQUEST['label'], 'string', 'Widget');
		@$cache_ttl = DevblocksPlatform::importGPC($_REQUEST['cache_ttl'], 'integer', 0);
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'integer', 0);

		if(!empty($id) && !empty($do_delete)) {
			DAO_WorkspaceWidget::delete($id);
			
		} else {
			$fields = array(
				DAO_WorkspaceWidget::LABEL => $label,
				DAO_WorkspaceWidget::CACHE_TTL => DevblocksPlatform::intClamp($cache_ttl, 0, 604800),
			);
			
			if(null != ($widget = DAO_WorkspaceWidget::get($id))) {
				DAO_WorkspaceWidget::update($widget->id, $fields);
				
				if(null != ($widget_extension = Extension_WorkspaceWidget::get($widget->extension_id))) {
					$widget_extension->saveConfig($widget);
				}
			}
		}
	}
	
	function addWidgetPopupJsonAction() {
		@$extension_id = DevblocksPlatform::importGPC($_REQUEST['extension_id'], 'string', null);
		@$workspace_tab_id = DevblocksPlatform::importGPC($_REQUEST['workspace_tab_id'], 'integer', 0);
		
		header('Content-Type: application/json');
		
		if(empty($extension_id) || null == ($extension = Extension_WorkspaceWidget::get($extension_id))) {
			echo json_encode(false);
			return;
		}
		
		if(empty($workspace_tab_id)) {
			echo json_encode(false);
			return;
		}
		
		$widget_id = DAO_WorkspaceWidget::create(array(
			DAO_WorkspaceWidget::LABEL => 'New widget',
			DAO_WorkspaceWidget::EXTENSION_ID => $extension_id,
			DAO_WorkspaceWidget::WORKSPACE_TAB_ID => $workspace_tab_id,
			DAO_WorkspaceWidget::POS => '0000',
			DAO_WorkspaceWidget::CACHE_TTL => 60,
		));
		
		echo json_encode(array(
			'widget_id' => $widget_id,
			'widget_extension_id' => $extension_id,
			'widget_tab_id' => $workspace_tab_id,
		));
	}
	
	function addWidgetImportJsonAction() {
		@$import_json = DevblocksPlatform::importGPC($_REQUEST['import_json'], 'string', null);
		@$workspace_tab_id = DevblocksPlatform::importGPC($_REQUEST['workspace_tab_id'], 'integer', 0);
		@$configure = DevblocksPlatform::importGPC($_REQUEST['configure'],'array', array());
		
		header('Content-Type: application/json');

		try {
		
			if(empty($workspace_tab_id))
				throw new Exception("Invalid workspace tab target");
			
			if(false == ($widget_json = json_decode($import_json, true)))
				throw new Exception("Invalid JSON");
			
			if(!isset($widget_json['widget']['extension_id']))
				throw new Exception("JSON doesn't contain widget extension info");
			
			if(!isset($widget_json['widget']['params']))
				throw new Exception("JSON doesn't contain widget params");
				
			@$extension_id = $widget_json['widget']['extension_id'];
			
			if(empty($extension_id) || null == ($extension = Extension_WorkspaceWidget::get($extension_id)))
				throw new Exception("Invalid widget extension");

			// [TODO] Do more error checking on acceptable params
			
			// Allow prompted configuration of the widget import
			
			@$configure_fields = $widget_json['widget']['configure'];
			
			// Are there configurable fields in this import file?
			if(is_array($configure_fields) && !empty($configure_fields)) {
				// If the worker has provided the configuration, make the changes to JSON array
				if(!empty($configure)) {
					foreach($configure_fields as $config_field_idx => $config_field) {
						if(!isset($config_field['path']))
							continue;
						
						if(!isset($configure[$config_field_idx]))
							continue;
						
						$ptr =& DevblocksPlatform::jsonGetPointerFromPath($widget_json, $config_field['path']);
						$ptr = $configure[$config_field_idx];
					}
					
				// If the worker hasn't been prompted, do that now
				} else {
					$tpl = DevblocksPlatform::getTemplateService();
					$tpl->assign('import_json', $import_json);
					$tpl->assign('import_fields', $configure_fields);
					$config_html = $tpl->fetch('devblocks:cerberusweb.core::internal/import/prompted/configure_json_import.tpl');
					
					echo json_encode(array(
						'config_html' => $config_html,
					));
					return;
				}
			}
			
			$widget_id = DAO_WorkspaceWidget::create(array(
				DAO_WorkspaceWidget::LABEL => @$widget_json['widget']['label'] ?: 'New widget',
				DAO_WorkspaceWidget::EXTENSION_ID => $extension_id,
				DAO_WorkspaceWidget::WORKSPACE_TAB_ID => $workspace_tab_id,
				DAO_WorkspaceWidget::POS => '0000',
				DAO_WorkspaceWidget::CACHE_TTL => @$widget_json['widget']['cache_ttl'] ?: 60,
				DAO_WorkspaceWidget::PARAMS_JSON => json_encode($widget_json['widget']['params'])
			));
			
			echo json_encode(array(
				'widget_id' => $widget_id,
				'widget_extension_id' => $extension_id,
				'widget_tab_id' => $workspace_tab_id,
			));
			
		} catch (Exception $e) {
			echo json_encode(array(false, $e->getMessage()));
			return;
		}
	}
	
	function showWidgetExportPopupAction() {
		@$widget_id = DevblocksPlatform::importGPC($_REQUEST['widget_id'], 'integer', 0);

		if(null == ($widget = DAO_WorkspaceWidget::get($widget_id)))
			return;
		
		$tpl = DevblocksPlatform::getTemplateService();

		$tpl->assign('widget', $widget);
		
		$widget_json = json_encode(array(
			'widget' => array(
				'label' => $widget->label,
				'extension_id' => $widget->extension_id,
				'cache_ttl' => $widget->cache_ttl,
				'params' => $widget->params,
			),
		));
		
		$tpl->assign('widget_json', DevblocksPlatform::strFormatJson($widget_json));
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/export.tpl');
	}
	
	function showWidgetExportDataPopupAction() {
		@$widget_id = DevblocksPlatform::importGPC($_REQUEST['widget_id'], 'integer', 0);

		if(null == ($widget = DAO_WorkspaceWidget::get($widget_id)))
			return;
		
		if(null == ($widget_extension = Extension_WorkspaceWidget::get($widget->extension_id)))
			return;
		
		if(!($widget_extension instanceof ICerbWorkspaceWidget_ExportData))
			return;
		
		$tpl = DevblocksPlatform::getTemplateService();

		$tpl->assign('widget', $widget);
		$tpl->assign('widget_extension', $widget_extension);
		
		$tpl->assign('export_data', array(
			'csv' => $widget_extension->exportData($widget, 'csv'),
			'json' => $widget_extension->exportData($widget, 'json'),
		));
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/export_data.tpl');
	}
	
	function getContextFieldsJsonAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'], 'string', null);
		
		header('Content-Type: application/json');
		
		if(null == ($context_ext = Extension_DevblocksContext::get($context))) {
			echo json_encode(false);
			return;
		}

		$view_class = $context_ext->getViewClass();
		
		if(null == ($view = new $view_class())) { /* @var $view C4_AbstractView */
			echo json_encode(false);
			return;
		}
		
		$results = array();
		$params_avail = $view->getParamsAvailable();

		if(is_array($params_avail))
		foreach($params_avail as $param) { /* @var $param DevblocksSearchField */
			if(empty($param->db_label))
				continue;
		
			$results[] = array(
				'key' => $param->token,
				'label' => mb_convert_case($param->db_label, MB_CASE_LOWER),
				'type' => $param->type
			);
		}
		
		echo json_encode($results);
	}
	
	function getContextPlaceholdersJsonAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'], 'string', null);
		
		header('Content-Type: application/json');
		
		$labels = array();
		$values = array();
		
		CerberusContexts::getContext($context, null, $labels, $values, null, true);
		
		if(empty($labels)) {
			echo json_encode(false);
			return;
		}
		
		$types = @$values['_types'] ?: array();
		$results = array();
		
		foreach($labels as $k => $v) {
			$results[] = array(
				'key' => $k,
				'label' => $v,
				'type' => @$types[$k] ?: '',
			);
		}
		
		echo json_encode($results);
	}
	
	function setWidgetPositionsAction() {
		@$workspace_tab_id = DevblocksPlatform::importGPC($_REQUEST['workspace_tab_id'], 'integer', 0);
		@$columns = DevblocksPlatform::importGPC($_REQUEST['column'], 'array', array());

		if(is_array($columns))
		foreach($columns as $idx => $widget_ids) {
			foreach(DevblocksPlatform::parseCsvString($widget_ids) as $n => $widget_id) {
				$pos = sprintf("%d%03d", $idx, $n);
				
				DAO_WorkspaceWidget::update($widget_id, array(
					DAO_WorkspaceWidget::POS => $pos,
				));
			}
			
			// [TODO] Kill cache on dashboard
		}
	}
	
	function getWidgetDatasourceConfigAction() {
		@$widget_id = DevblocksPlatform::importGPC($_REQUEST['widget_id'], 'string', '');
		@$params_prefix = DevblocksPlatform::importGPC($_REQUEST['params_prefix'], 'string', null);
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'], 'string', '');

		if(null == ($widget = DAO_WorkspaceWidget::get($widget_id)))
			return;
		
		if(null == ($datasource_ext = Extension_WorkspaceWidgetDatasource::get($ext_id)))
			return;
		
		$datasource_ext->renderConfig($widget, $widget->params, $params_prefix);
	}
}
endif;

if(class_exists('Extension_WorkspaceTab')):
class WorkspaceTab_Dashboards extends Extension_WorkspaceTab {
	public function renderTabConfig(Model_WorkspacePage $page, Model_WorkspaceTab $tab) {
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('workspace_page', $page);
		$tpl->assign('workspace_tab', $tab);
		
		// Render template
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/tabs/dashboard/config.tpl');
	}
	
	function saveTabConfig(Model_WorkspacePage $page, Model_WorkspaceTab $tab) {
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array');

		@$prev_column_count = intval($tab->params['num_columns']);
		@$new_column_count = DevblocksPlatform::intClamp(intval($params['num_columns']), 1, 4);

		// Rebalance widgets if we're reducing the columns
		if($new_column_count < $prev_column_count) {
			$widgets = DAO_WorkspaceWidget::getByTab($tab->id);
			$columns_left = $new_column_count;
			
			for($col_idx = 0; $col_idx < $new_column_count; $col_idx++) {
				$idx = 0;
				$items = array_splice($widgets, 0, ceil(count($widgets)/$columns_left));
				
				foreach($items as $item) {
					$pos = sprintf("%d%03d", $col_idx, $idx++);
					DAO_WorkspaceWidget::update($item->id, array(DAO_WorkspaceWidget::POS => $pos));
				}
				
				$columns_left--;
			}
		}
		
		DAO_WorkspaceTab::update($tab->id, array(
			DAO_WorkspaceTab::PARAMS_JSON => json_encode($params),
		));
	}
	
	public function renderTab(Model_WorkspacePage $page, Model_WorkspaceTab $tab) {
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('workspace_page', $page);
		$tpl->assign('workspace_tab', $tab);
		
		$widget_extensions = Extension_WorkspaceWidget::getAll();
		$tpl->assign('widget_extensions', $widget_extensions);
		
		// Get by workspace tab
		// [TODO] Cache
		$widgets = DAO_WorkspaceWidget::getWhere(
			sprintf("%s = %d",
				DAO_WorkspaceWidget::WORKSPACE_TAB_ID,
				$tab->id
			),
			DAO_WorkspaceWidget::POS,
			true
		);

		$columns = array();

		// [TODO] If the col_idx is greater than the number of cols on this dashboard,
		//   move widget to first col
		
		if(is_array($widgets))
		foreach($widgets as $widget) { /* @var $widget Model_WorkspaceWidget */
			$pos = !empty($widget->pos) ? $widget->pos : '0000';
			$col_idx = substr($pos,0,1);
			$n = substr($pos,1);
			
			if(!isset($columns[$col_idx]))
				$columns[$col_idx] = array();
			
			$columns[$col_idx][$widget->id] = $widget;
		}

		unset($widgets);
		
		$tpl->assign('columns', $columns);
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/tab.tpl');
	}
	
	function exportTabConfigJson(Model_WorkspacePage $page, Model_WorkspaceTab $tab) {
		$json = array(
			'tab' => array(
				'name' => $tab->name,
				'extension_id' => $tab->extension_id,
				'params' => $tab->params,
				'widgets' => array(),
			),
		);
		
		$widgets = DAO_WorkspaceWidget::getByTab($tab->id);
		
		foreach($widgets as $widget) {
			$widget_json = array(
				'widget' => array(
					'label' => $widget->label,
					'extension_id' => $widget->extension_id,
					'pos' => $widget->pos,
					'params' => $widget->params,
				),
			);
			
			$json['tab']['widgets'][] = $widget_json;
		}
		
		return json_encode($json);
	}
	
	function importTabConfigJson($json, Model_WorkspaceTab $tab) {
		if(empty($tab->id) || !is_array($json) || !isset($json['tab']))
			return false;
		
		if(!isset($json['tab']['widgets']) || !is_array($json['tab']['widgets']))
			return false;
		
		foreach($json['tab']['widgets'] as $widget) {
			DAO_WorkspaceWidget::create(array(
				DAO_WorkspaceWidget::LABEL => $widget['widget']['label'],
				DAO_WorkspaceWidget::EXTENSION_ID => $widget['widget']['extension_id'],
				DAO_WorkspaceWidget::POS => $widget['widget']['pos'],
				DAO_WorkspaceWidget::PARAMS_JSON => json_encode($widget['widget']['params']),
				DAO_WorkspaceWidget::WORKSPACE_TAB_ID => $tab->id,
				DAO_WorkspaceWidget::UPDATED_AT => time(),
			));
		}
		
		return true;
	}
}
endif;

class WorkspaceWidget_Gauge extends Extension_WorkspaceWidget implements ICerbWorkspaceWidget_ExportData {
	private function _loadData(Model_WorkspaceWidget &$widget) {
		@$datasource_extid = $widget->params['datasource'];

		if(empty($datasource_extid)) {
			return false;
		}
		
		if(null == ($datasource_ext = Extension_WorkspaceWidgetDatasource::get($datasource_extid)))
			return false;
		
		$data = $datasource_ext->getData($widget, $widget->params);
		
		if(!empty($data))
			$widget->params = $data;
		
		return true;
	}
	
	function render(Model_WorkspaceWidget $widget) {
		$tpl = DevblocksPlatform::getTemplateService();

		if(false == ($this->_loadData($widget))) {
			echo "This gauge doesn't have a data source. Configure it and select one.";
			return;
		}
		
		$tpl->assign('widget', $widget);
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/gauge/gauge.tpl');
	}
	
	// Config
	
	function renderConfig(Model_WorkspaceWidget $widget) {
		if(empty($widget))
			return;
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		// Widget
		
		$tpl->assign('widget', $widget);
		
		// Limit to widget
		
		$datasource_mfts = Extension_WorkspaceWidgetDatasource::getAll(false, $this->manifest->id);
		$tpl->assign('datasource_mfts', $datasource_mfts);
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/gauge/config.tpl');
	}
	
	function saveConfig(Model_WorkspaceWidget $widget) {
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', array());
		
		// Convert the serialized model to proper JSON before saving
		
		if(isset($params['worklist_model_json'])) {
			$worklist_model = json_decode($params['worklist_model_json'], true);
			unset($params['worklist_model_json']);
			
			if(empty($worklist_model) && isset($params['context'])) {
				if(false != ($context_ext = Extension_DevblocksContext::get($params['context']))) {
					if(false != (@$worklist_model = json_decode(C4_AbstractViewLoader::serializeViewToAbstractJson($context_ext->getChooserView(), $context_ext->id), true))) {
						$worklist_model['context'] = $context_ext->id;
					}
				}
			}
			
			$params['worklist_model'] = $worklist_model;
		}
		
		if(isset($params['threshold_values']))
		foreach($params['threshold_values'] as $idx => $val) {
			if(empty($val)) {
				unset($params['threshold_values'][$idx]);
				continue;
			}
			
			@$label = $params['threshold_labels'][$idx];
			
			if(empty($label))
				$params['threshold_labels'][$idx] = $val;
			
			@$color = $params['threshold_colors'][$idx];
			
			if(empty($color))
				$params['threshold_colors'][$idx] = sprintf("#%s%s%s",
					dechex(mt_rand(0,255)),
					dechex(mt_rand(0,255)),
					dechex(mt_rand(0,255))
				);
		}
		
		DAO_WorkspaceWidget::update($widget->id, array(
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		));

		// Clear caches
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(sprintf("widget%d_datasource", $widget->id));
	}
	
	function exportData(Model_WorkspaceWidget $widget, $format=null) {
		if(false == ($this->_loadData($widget))) {
			return;
		}
		
		switch(strtolower($format)) {
			case 'csv':
				return $this->_exportDataAsCsv($widget);
				break;
				
			default:
			case 'json':
				return $this->_exportDataAsJson($widget);
				break;
		}
		
		return false;
	}
	
	private function _exportDataAsCsv(Model_WorkspaceWidget $widget) {
		$results = array(
			'Label' => $widget->label,
			'Value' => $widget->params['metric_value'],
			'Type' => $widget->params['metric_type'],
			'Prefix' => $widget->params['metric_prefix'],
			'Suffix' => $widget->params['metric_suffix'],
		);

		$fp = fopen("php://temp", 'r+');
		
		fputcsv($fp, array_keys($results));
		fputcsv($fp, array_values($results));
		
		rewind($fp);

		$output = "";
		
		while(!feof($fp)) {
			$output .= fgets($fp);
		}
		
		fclose($fp);
		
		return $output;
	}
	
	private function _exportDataAsJson(Model_WorkspaceWidget $widget) {
		$results = array(
			'widget' => array(
				'label' => $widget->label,
				'type' => 'gauge',
				'version' => 'Cerb ' . APP_VERSION,
				'metric' => array(
					'value' => $widget->params['metric_value'],
					'type' => $widget->params['metric_type'],
					'prefix' => $widget->params['metric_prefix'],
					'suffix' => $widget->params['metric_suffix'],
				),
				'thresholds' => array(),
			),
		);
		
		if(isset($widget->params['threshold_labels']) && is_array($widget->params['threshold_labels']))
		foreach(array_keys($widget->params['threshold_labels']) as $idx) {
			if(
				empty($widget->params['threshold_labels'][$idx])
				|| !isset($widget->params['threshold_values'][$idx])
			)
				continue;
		
			$results['widget']['thresholds'][] = array(
				'label' => $widget->params['threshold_labels'][$idx],
				'value' => $widget->params['threshold_values'][$idx],
				'color' => $widget->params['threshold_colors'][$idx],
			);
		}
		
		return DevblocksPlatform::strFormatJson(json_encode($results));
	}
};

class WorkspaceWidget_Calendar extends Extension_WorkspaceWidget implements ICerbWorkspaceWidget_ExportData {
	function render(Model_WorkspaceWidget $widget) {
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::getTemplateService();

		@$month = DevblocksPlatform::importGPC($_REQUEST['month'], 'integer', null);
		@$year = DevblocksPlatform::importGPC($_REQUEST['year'], 'integer', null);
		
		$tpl->assign('widget', $widget);
		
		@$calendar_id = $widget->params['calendar_id'];
		
		if(empty($calendar_id) || null == ($calendar = DAO_Calendar::get($calendar_id))) { /* @var Model_Calendar $calendar */
			echo "A calendar isn't linked to this widget. Configure it to select one.";
			return;
		}
		
		$calendar_properties = DevblocksCalendarHelper::getCalendar($month, $year);
		$calendar_events = $calendar->getEvents($calendar_properties['date_range_from'], $calendar_properties['date_range_to']);
		
		// Contexts (for creating events)

		if(CerberusContexts::isWriteableByActor($calendar->owner_context, $calendar->owner_context_id, $active_worker)) {
			$create_contexts = $calendar->getCreateContexts();
			$tpl->assign('create_contexts', $create_contexts);
		}
		
		// Template scope
		
		$tpl->assign('calendar', $calendar);
		$tpl->assign('calendar_events', $calendar_events);
		$tpl->assign('calendar_properties', $calendar_properties);
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/calendar/calendar.tpl');
	}
	
	// Config
	
	function renderConfig(Model_WorkspaceWidget $widget) {
		if(empty($widget))
			return;
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		// Widget
		
		$tpl->assign('widget', $widget);
		
		// Calendars
		
		$calendars = DAO_Calendar::getAll();
		$tpl->assign('calendars', $calendars);
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/calendar/config.tpl');
	}
	
	function saveConfig(Model_WorkspaceWidget $widget) {
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', array());
		
		DAO_WorkspaceWidget::update($widget->id, array(
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		));
	}
	
	// Export
	
	function exportData(Model_WorkspaceWidget $widget, $format=null) {
		switch(strtolower($format)) {
			case 'csv':
				return $this->_exportDataAsCsv($widget);
				break;
				
			default:
			case 'json':
				return $this->_exportDataAsJson($widget);
				break;
		}
		
		return false;
	}
	
	private function _exportDataAsCsv(Model_WorkspaceWidget $widget) {
		@$calendar_id = $widget->params['calendar_id'];
		
		if(false == ($calendar = DAO_Calendar::get($calendar_id)))
			return false;
		
		$calendar_properties = DevblocksCalendarHelper::getCalendar(null, null);
		
		$fp = fopen("php://temp", 'r+');
		
		$headings = array(
			'Date',
			'Label',
			'Start',
			'End',
			'Is Available',
			'Color',
			//Link',
		);
		
		fputcsv($fp, $headings);
		
		// [TODO] This needs to use the selected month/year from widget
		$calendar_events = $calendar->getEvents($calendar_properties['date_range_from'], $calendar_properties['date_range_to']);
		
		foreach($calendar_events as $events) {
			foreach($events as $event) {
				fputcsv($fp, array(
					date('r', $event['ts']),
					$event['label'],
					$event['ts'],
					$event['ts_end'],
					$event['is_available'],
					$event['color'],
					//$event['link'], // [TODO] Translate ctx:// links
				));
			}
		}
		
		unset($calendar_events);
		
		rewind($fp);

		$output = "";
		
		while(!feof($fp)) {
			$output .= fgets($fp);
		}
		
		fclose($fp);
		
		return $output;
	}
	
	private function _exportDataAsJson(Model_WorkspaceWidget $widget) {
		@$calendar_id = $widget->params['calendar_id'];
		
		if(false == ($calendar = DAO_Calendar::get($calendar_id)))
			return false;
		
		$calendar_properties = DevblocksCalendarHelper::getCalendar(null, null);
		
		// [TODO] This needs to use the selected month/year from widget
		$calendar_events = $calendar->getEvents($calendar_properties['date_range_from'], $calendar_properties['date_range_to']);
		
		$json_events = array();
		
		// [TODO] This should export a fully formed calendar (headings, weeks, days)
		// [TODO] The widget export should give the date range used as well
		
		foreach($calendar_events as $events) {
			foreach($events as $event) {
				$json_events[] = array(
					'label' => $event['label'],
					'date' => date('r', $event['ts']),
					'ts' => $event['ts'],
					'ts_end' => $event['ts_end'],
					'is_available' => $event['is_available'],
					'color' => $event['color'],
					//'link' => $event['link'], // [TODO] Translate ctx:// links
				);
			}
		}
		
		unset($calendar_events);
		
		$results = array(
			'widget' => array(
				'label' => $widget->label,
				'type' => 'calendar',
				'version' => 'Cerb ' . APP_VERSION,
				'events' => $json_events,
			),
		);
		
		return DevblocksPlatform::strFormatJson(json_encode($results));
	}
};

class WorkspaceWidget_Clock extends Extension_WorkspaceWidget implements ICerbWorkspaceWidget_ExportData {
	function render(Model_WorkspaceWidget $widget) {
		$tpl = DevblocksPlatform::getTemplateService();

		@$timezone = $widget->params['timezone'];
		
		if(empty($timezone)) {
			echo "This clock doesn't have a timezone. Configure it and set one.";
			return;
		}
		
		$datetimezone = new DateTimeZone($timezone);
		$datetime = new DateTime('now', $datetimezone);

		$offset = $datetimezone->getOffset($datetime);
		$tpl->assign('offset', $offset);
		
		$tpl->assign('widget', $widget);
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/clock/clock.tpl');
	}
	
	// Config
	
	function renderConfig(Model_WorkspaceWidget $widget) {
		if(empty($widget))
			return;
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		// Widget
		
		$tpl->assign('widget', $widget);
		
		// Timezones
		
		$date = DevblocksPlatform::getDateService();
		
		$timezones = $date->getTimezones();
		$tpl->assign('timezones', $timezones);
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/clock/config.tpl');
	}
	
	function saveConfig(Model_WorkspaceWidget $widget) {
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', array());
		
		DAO_WorkspaceWidget::update($widget->id, array(
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		));
	}
	
	// Export
	
	function exportData(Model_WorkspaceWidget $widget, $format=null) {
		switch(strtolower($format)) {
			case 'csv':
				return $this->_exportDataAsCsv($widget);
				break;
				
			default:
			case 'json':
				return $this->_exportDataAsJson($widget);
				break;
		}
		
		return false;
	}
	
	private function _exportDataAsCsv(Model_WorkspaceWidget $widget) {
		@$timezone = $widget->params['timezone'];
		$datetimezone = new DateTimeZone($timezone);
		$datetime = new DateTime('now', $datetimezone);
		
		$results = array(
			'Label' => $widget->label,
			'Timezone' => $widget->params['timezone'],
			'Timestamp' => $datetime->getTimestamp(),
			'Output' => $datetime->format('r'),
		);

		$fp = fopen("php://temp", 'r+');
		
		fputcsv($fp, array_keys($results));
		fputcsv($fp, array_values($results));
		
		rewind($fp);

		$output = "";
		
		while(!feof($fp)) {
			$output .= fgets($fp);
		}
		
		fclose($fp);
		
		return $output;
	}
	
	private function _exportDataAsJson(Model_WorkspaceWidget $widget) {
		@$timezone = $widget->params['timezone'];
		$datetimezone = new DateTimeZone($timezone);
		$datetime = new DateTime('now', $datetimezone);
		
		$results = array(
			'widget' => array(
				'label' => $widget->label,
				'type' => 'clock',
				'version' => 'Cerb ' . APP_VERSION,
				'time' => array(
					'timezone' => $widget->params['timezone'],
					'timestamp' => $datetime->getTimestamp(),
					'output' => $datetime->format('r'),
				),
			),
		);
		
		return DevblocksPlatform::strFormatJson(json_encode($results));
	}
};

class WorkspaceWidget_Counter extends Extension_WorkspaceWidget implements ICerbWorkspaceWidget_ExportData {
	private function _loadData(Model_WorkspaceWidget &$widget) {
		@$datasource_extid = $widget->params['datasource'];

		if(empty($datasource_extid)) {
			return false;
		}
		
		if(null == ($datasource_ext = Extension_WorkspaceWidgetDatasource::get($datasource_extid)))
			return false;
		
		$data = $datasource_ext->getData($widget, $widget->params);

		if(!empty($data))
			$widget->params = $data;
		
		return true;
	}
	
	function render(Model_WorkspaceWidget $widget) {
		$tpl = DevblocksPlatform::getTemplateService();

		if(false == ($this->_loadData($widget))) {
			echo "This counter doesn't have a data source. Configure it and select one.";
			return;
		}
		
		$tpl->assign('widget', $widget);
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/counter/counter.tpl');
	}
	
	// Config
	
	function renderConfig(Model_WorkspaceWidget $widget) {
		if(empty($widget))
			return;
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		// Widget
		
		$tpl->assign('widget', $widget);
		
		// Limit to widget
		
		$datasource_mfts = Extension_WorkspaceWidgetDatasource::getAll(false, $this->manifest->id);
		$tpl->assign('datasource_mfts', $datasource_mfts);
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/counter/config.tpl');
	}
	
	function saveConfig(Model_WorkspaceWidget $widget) {
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', array());
		
		// Convert the serialized model to proper JSON before saving
		
		if(isset($params['worklist_model_json'])) {
			$worklist_model = json_decode($params['worklist_model_json'], true);
			unset($params['worklist_model_json']);
			
			if(empty($worklist_model) && isset($params['context'])) {
				if(false != ($context_ext = Extension_DevblocksContext::get($params['context']))) {
					if(false != (@$worklist_model = json_decode(C4_AbstractViewLoader::serializeViewToAbstractJson($context_ext->getChooserView(), $context_ext->id), true))) {
						$worklist_model['context'] = $context_ext->id;
					}
				}
			}
			
			$params['worklist_model'] = $worklist_model;
		}
		
		DAO_WorkspaceWidget::update($widget->id, array(
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		));

		// Clear caches
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(sprintf("widget%d_datasource", $widget->id));
	}
	
	// Export
	
	function exportData(Model_WorkspaceWidget $widget, $format=null) {
		if(false == ($this->_loadData($widget))) {
			return;
		}
		
		switch(strtolower($format)) {
			case 'csv':
				return $this->_exportDataAsCsv($widget);
				break;
				
			default:
			case 'json':
				return $this->_exportDataAsJson($widget);
				break;
		}
		
		return false;
	}
	
	private function _exportDataAsCsv(Model_WorkspaceWidget $widget) {
		$results = array(
			'Label' => $widget->label,
			'Value' => $widget->params['metric_value'],
			'Type' => $widget->params['metric_type'],
			'Prefix' => $widget->params['metric_prefix'],
			'Suffix' => $widget->params['metric_suffix'],
		);

		$fp = fopen("php://temp", 'r+');
		
		fputcsv($fp, array_keys($results));
		fputcsv($fp, array_values($results));
		
		rewind($fp);

		$output = "";
		
		while(!feof($fp)) {
			$output .= fgets($fp);
		}
		
		fclose($fp);
		
		return $output;
	}
	
	private function _exportDataAsJson(Model_WorkspaceWidget $widget) {
		$results = array(
			'widget' => array(
				'label' => $widget->label,
				'type' => 'counter',
				'version' => 'Cerb ' . APP_VERSION,
				'metric' => array(
					'value' => $widget->params['metric_value'],
					'type' => $widget->params['metric_type'],
					'prefix' => $widget->params['metric_prefix'],
					'suffix' => $widget->params['metric_suffix'],
				),
			),
		);
		
		return DevblocksPlatform::strFormatJson(json_encode($results));
	}
};

class WorkspaceWidget_Countdown extends Extension_WorkspaceWidget implements ICerbWorkspaceWidget_ExportData {
	function render(Model_WorkspaceWidget $widget) {
		$tpl = DevblocksPlatform::getTemplateService();

		if(!isset($widget->params['target_timestamp'])) {
			echo "This countdown doesn't have a target date. Configure it and set one.";
			return;
		}
		
		$tpl->assign('widget', $widget);
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/countdown/countdown.tpl');
	}
	
	// Config
	
	function renderConfig(Model_WorkspaceWidget $widget) {
		if(empty($widget))
			return;
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		// Widget
		
		$tpl->assign('widget', $widget);
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/countdown/config.tpl');
	}
	
	function saveConfig(Model_WorkspaceWidget $widget) {
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', array());
		
		if(isset($params['target_timestamp'])) {
			@$timestamp = intval(strtotime($params['target_timestamp']));
			$params['target_timestamp'] = $timestamp;
		}
		
		DAO_WorkspaceWidget::update($widget->id, array(
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		));
	}
	
	// Export
	
	function exportData(Model_WorkspaceWidget $widget, $format=null) {
		switch(strtolower($format)) {
			case 'csv':
				return $this->_exportDataAsCsv($widget);
				break;
				
			default:
			case 'json':
				return $this->_exportDataAsJson($widget);
				break;
		}
		
		return false;
	}
	
	private function _exportDataAsCsv(Model_WorkspaceWidget $widget) {
		@$diff = max(0, intval($widget->params['target_timestamp']) - time());
		
		$results = array(
			'Label' => $widget->label,
			'Timestamp' => $widget->params['target_timestamp'],
			'Output' => DevblocksPlatform::strSecsToString($diff, 2),
		);

		$fp = fopen("php://temp", 'r+');
		
		fputcsv($fp, array_keys($results));
		fputcsv($fp, array_values($results));
		
		rewind($fp);

		$output = "";
		
		while(!feof($fp)) {
			$output .= fgets($fp);
		}
		
		fclose($fp);
		
		return $output;
	}
	
	private function _exportDataAsJson(Model_WorkspaceWidget $widget) {
		@$diff = max(0, intval($widget->params['target_timestamp']) - time());
		
		$results = array(
			'widget' => array(
				'label' => $widget->label,
				'type' => 'countdown',
				'version' => 'Cerb ' . APP_VERSION,
				'countdown' => array(
					'output' => DevblocksPlatform::strSecsToString($diff, 2),
					'timestamp' => $widget->params['target_timestamp'],
				),
			),
		);
		
		return DevblocksPlatform::strFormatJson(json_encode($results));
	}
};

class WorkspaceWidget_Chart extends Extension_WorkspaceWidget implements ICerbWorkspaceWidget_ExportData {
	private function _loadData(Model_WorkspaceWidget &$widget) {
		@$series = $widget->params['series'];

		if(empty($series)) {
			return false;
		}
		
		// Multiple datasources
		if(is_array($series))
		foreach($series as $series_idx => $series_params) {
			@$datasource_extid = $series_params['datasource'];

			if(empty($datasource_extid))
				continue;
			
			if(null == ($datasource_ext = Extension_WorkspaceWidgetDatasource::get($datasource_extid)))
				continue;

			$params_prefix = sprintf("[series][%d]", $series_idx);

			$data = $datasource_ext->getData($widget, $series_params, $params_prefix);

			if(!empty($data))
				$widget->params['series'][$series_idx] = $data;
		}
		
		return true;
	}
	
	function render(Model_WorkspaceWidget $widget) {
		$tpl = DevblocksPlatform::getTemplateService();

		if(false == ($this->_loadData($widget))) {
			echo "This chart doesn't have any data sources. Configure it and select one.";
			return;
		}
		
		$tpl->assign('widget', $widget);
		
		switch($widget->params['chart_type']) {
			case 'bar':
				$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/chart/bar_chart.tpl');
				break;
				
			default:
			case 'line':
				$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/chart/line_chart.tpl');
				break;
		}
	}
	
	// Config
	
	function renderConfig(Model_WorkspaceWidget $widget) {
		if(empty($widget))
			return;
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		// Widget
		
		$tpl->assign('widget', $widget);
		
		// Datasource Extensions
		
		$datasource_mfts = Extension_WorkspaceWidgetDatasource::getAll(false, $this->manifest->id);
		$tpl->assign('datasource_mfts', $datasource_mfts);
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/chart/config.tpl');
	}
	
	function saveConfig(Model_WorkspaceWidget $widget) {
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', array());
		
		foreach($params['series'] as $idx => $series) {
			// [TODO] The extension should be able to filter the properties here (on all widgets)
			// [TODO] $datasource = $series['datasource'];
			
			// Convert the serialized model to proper JSON before saving
		
			if(isset($series['worklist_model_json'])) {
				$worklist_model = json_decode($series['worklist_model_json'], true);
				unset($series['worklist_model_json']);
				
				if(empty($worklist_model) && isset($series['context'])) {
					if(false != ($context_ext = Extension_DevblocksContext::get($series['context']))) {
						if(false != (@$worklist_model = json_decode(C4_AbstractViewLoader::serializeViewToAbstractJson($context_ext->getChooserView(), $context_ext->id), true))) {
							$worklist_model['context'] = $context_ext->id;
						}
					}
				}
				
				$series['worklist_model'] = $worklist_model;
				$params['series'][$idx] = $series;
			}
			
			if(isset($series['line_color'])) {
				if(false != ($rgb = $this->_hex2RGB($series['line_color']))) {
					$params['series'][$idx]['fill_color'] = sprintf("rgba(%d,%d,%d,0.15)", $rgb['r'], $rgb['g'], $rgb['b']);
				}
			}
		}
		
		DAO_WorkspaceWidget::update($widget->id, array(
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		));
	}
	
	// Source: http://www.php.net/manual/en/function.hexdec.php#99478
	private function _hex2RGB($hex_color) {
		$hex_color = preg_replace("/[^0-9A-Fa-f]/", '', $hex_color); // Gets a proper hex string
		$rgb = array();
	  
		// If a proper hex code, convert using bitwise operation. No overhead... faster
		if (strlen($hex_color) == 6) {
			$color_value = hexdec($hex_color);
			$rgb['r'] = 0xFF & ($color_value >> 0x10);
			$rgb['g'] = 0xFF & ($color_value >> 0x8);
			$rgb['b'] = 0xFF & $color_value;
			 
		// If shorthand notation, need some string manipulations
		} elseif (strlen($hex_color) == 3) {
			$rgb['r'] = hexdec(str_repeat(substr($hex_color, 0, 1), 2));
			$rgb['g'] = hexdec(str_repeat(substr($hex_color, 1, 1), 2));
			$rgb['b'] = hexdec(str_repeat(substr($hex_color, 2, 1), 2));
			 
		} else {
			return false;
		}
	  
		return $rgb;
	}
	
	// Export
	
	function exportData(Model_WorkspaceWidget $widget, $format=null) {
		if(false == ($this->_loadData($widget))) {
			return;
		}
		
		switch(strtolower($format)) {
			case 'csv':
				return $this->_exportDataAsCsv($widget);
				break;
				
			default:
			case 'json':
				return $this->_exportDataAsJson($widget);
				break;
		}
		
		return false;
	}
	
	private function _exportDataAsCsv(Model_WorkspaceWidget $widget) {
		$series = $widget->params['series'];
		
		$results = array();
		
		$results[] = array(
			'Series #',
			'Series Label',
			'Data X Label',
			'Data X Value',
			'Data Y Label',
			'Data Y Value',
		);
		
		if(is_array($series))
		foreach($series as $series_idx => $series_params) {
			if(!isset($series_params['data']) || empty($series_params['data']))
				continue;
		
			$data = $series_params['data'];
			
			if(is_array($data))
			foreach($data as $k => $v) {
				$row = array(
					'series' => $series_idx,
					'series_label' => $series_params['label'],
					'x_label' => $v['x_label'],
					'x' => $v['x'],
					'y_label' => $v['y_label'],
					'y' => $v['y'],
				);

				$results[] = $row;
			}
		}
		
		$fp = fopen("php://temp", 'r+');
		
		foreach($results as $result) {
			fputcsv($fp, $result);
		}
		
		rewind($fp);

		$output = "";
		
		while(!feof($fp)) {
			$output .= fgets($fp);
		}
		
		fclose($fp);
		
		return $output;
	}
	
	private function _exportDataAsJson(Model_WorkspaceWidget $widget) {
		$series = $widget->params['series'];
		
		$results = array(
			'widget' => array(
				'label' => $widget->label,
				'type' => 'chart',
				'version' => 'Cerb ' . APP_VERSION,
				'series' => array(),
			),
		);
		
		if(is_array($series))
		foreach($series as $series_idx => $series_params) {
			if(!isset($series_params['data']) || empty($series_params['data']))
				continue;
		
			$data = $series_params['data'];
			
			$results['widget']['series'][$series_idx] = array(
				'id' => $series_idx,
				'label' => $series_params['label'],
				'data' => array(),
			);
			
			if(is_array($data))
			foreach($data as $k => $v) {
				$row = array(
					'x' => $v['x'],
					'x_label' => $v['x_label'],
					'y' => $v['y'],
					'y_label' => $v['y_label'],
				);
				
				$results['widget']['series'][$series_idx]['data'][] = $row;
			}
		}
		
		return DevblocksPlatform::strFormatJson(json_encode($results));
	}
};

class WorkspaceWidget_Subtotals extends Extension_WorkspaceWidget implements ICerbWorkspaceWidget_ExportData {
	function render(Model_WorkspaceWidget $widget) {
		$view_id = sprintf("widget%d_worklist", $widget->id);

		if(null == ($view = self::getViewFromParams($widget, $widget->params, $view_id)))
			return;
		
		if(!($view instanceof IAbstractView_Subtotals))
			return;
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);
		$tpl->assign('view', $view);

		$fields = $view->getSubtotalFields();
		$tpl->assign('subtotal_fields', $fields);

		if(empty($view->renderSubtotals) || !isset($fields[$view->renderSubtotals])) {
			echo "You need to enable subtotals on the worklist in this widget's configuration.";
			return;
		}
		
		$counts = $view->getSubtotalCounts($view->renderSubtotals);

		if(null != ($limit_to = $widget->params['limit_to'])) {
			$counts = array_slice($counts, 0, $limit_to, true);
		}
		
		switch(@$widget->params['style']) {
			case 'pie':
				$wedge_colors = array(
					'#57970A',
					'#007CBD',
					'#7047BA',
					'#8B0F98',
					'#CF2C1D',
					'#E97514',
					'#FFA100',
					'#3E6D07',
					'#345C05',
					'#005988',
					'#004B73',
					'#503386',
					'#442B71',
					'#640A6D',
					'#55085C',
					'#951F14',
					'#7E1A11',
					'#A8540E',
					'#8E470B',
					'#B87400',
					'#9C6200',
					'#CCCCCC',
				);
				$widget->params['wedge_colors'] = $wedge_colors;

				$wedge_labels = array();
				$wedge_values = array();
				
				DevblocksPlatform::sortObjects($counts, '[hits]', false);
				
				foreach($counts as $data) {
					$wedge_labels[] = $data['label'];
					$wedge_values[] = intval($data['hits']);
				}

				$widget->params['wedge_labels'] = $wedge_labels;
				$widget->params['wedge_values'] = $wedge_values;
				
				$widget->params['show_legend'] = true;
				$widget->params['metric_type'] = 'number';
				
				$tpl->assign('widget', $widget);
				
				$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/pie_chart/pie_chart.tpl');
				break;
				
			default:
			case 'list':
				$tpl->assign('subtotal_counts', $counts);
				$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/subtotals/subtotals.tpl');
				break;
		}
		
	}
	
	function renderConfig(Model_WorkspaceWidget $widget) {
		if(empty($widget))
			return;
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		// Widget
		
		$tpl->assign('widget', $widget);
		
		// Contexts
		
		$context_mfts = Extension_DevblocksContext::getAll(false, 'workspace');
		$tpl->assign('context_mfts', $context_mfts);
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/subtotals/config.tpl');
	}
	
	function saveConfig(Model_WorkspaceWidget $widget) {
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', array());
		
		// Convert the serialized model to proper JSON before saving
		
		if(isset($params['worklist_model_json'])) {
			$worklist_model = json_decode($params['worklist_model_json'], true);
			unset($params['worklist_model_json']);
			
			if(empty($worklist_model) && isset($params['context'])) {
				if(false != ($context_ext = Extension_DevblocksContext::get($params['context']))) {
					if(false != (@$worklist_model = json_decode(C4_AbstractViewLoader::serializeViewToAbstractJson($context_ext->getChooserView(), $context_ext->id), true))) {
						$worklist_model['context'] = $context_ext->id;
					}
				}
			}
			
			$params['worklist_model'] = $worklist_model;
		}
		
		// Save the widget
		
		DAO_WorkspaceWidget::update($widget->id, array(
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		));
	}
	
	// Export
	
	function exportData(Model_WorkspaceWidget $widget, $format=null) {
		if(false == $this->_exportDataLoad($widget)) {
			return;
		}
		
		switch(strtolower($format)) {
			case 'csv':
				return $this->_exportDataAsCsv($widget);
				break;
				
			default:
			case 'json':
				return $this->_exportDataAsJson($widget);
				break;
		}
		
		return false;
	}
	
	private function _exportDataLoad(Model_WorkspaceWidget &$widget) {
		$view_id = sprintf("widget%d_worklist", $widget->id);
		
		if(null == ($view = self::getViewFromParams($widget, $widget->params, $view_id)))
			return false;

		if(!($view instanceof IAbstractView_Subtotals))
			return false;
		
		$fields = $view->getSubtotalFields();
		
		if(empty($view->renderSubtotals) || !isset($fields[$view->renderSubtotals])) {
			return false;
		}
		
		$counts = $view->getSubtotalCounts($view->renderSubtotals);

		if(null != (@$limit_to = $widget->params['limit_to'])) {
			$counts = array_slice($counts, 0, $limit_to, true);
		}
		
		DevblocksPlatform::sortObjects($counts, '[hits]', false);
		
		$widget->params['counts'] = $counts;
		return true;
	}
	
	private function _exportDataAsCsv(Model_WorkspaceWidget $widget) {
		@$counts = $widget->params['counts'];
		
		if(!is_array($counts))
			return false;
		
		$results = array();
		
		$results[] = array(
			'Label',
			'Count',
		);
		
		foreach($counts as $count) {
			$results[] = array(
				$count['label'],
				$count['hits'],
			);
		}
		
		$fp = fopen("php://temp", 'r+');
		
		foreach($results as $result) {
			fputcsv($fp, $result);
		}
		
		rewind($fp);

		$output = "";
		
		while(!feof($fp)) {
			$output .= fgets($fp);
		}
		
		fclose($fp);
		
		return $output;
	}
	
	private function _exportDataAsJson(Model_WorkspaceWidget $widget) {
		@$counts = $widget->params['counts'];
		
		if(!is_array($counts))
			return false;
		
		$results = array(
			'widget' => array(
				'label' => $widget->label,
				'type' => 'subtotals',
				'version' => 'Cerb ' . APP_VERSION,
				'counts' => array(),
			),
		);

		foreach($counts as $count) {
			$results['widget']['counts'][] = array(
				'label' => $count['label'],
				'count' => $count['hits'],
			);
		}
		
		return DevblocksPlatform::strFormatJson(json_encode($results));
	}
};

class WorkspaceWidget_Worklist extends Extension_WorkspaceWidget implements ICerbWorkspaceWidget_ExportData {
	public function getView(Model_WorkspaceWidget $widget) {
		$view_id = sprintf("widget%d_worklist", $widget->id);
		
		if(null == ($view = Extension_WorkspaceWidget::getViewFromParams($widget, $widget->params, $view_id)))
			return false;
		
		return $view;
	}
	
	function render(Model_WorkspaceWidget $widget) {
		if(false == ($view = $this->getView($widget)))
			return;
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view->id);
		$tpl->assign('view', $view);

		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/worklist/render.tpl');
	}
	
	function renderConfig(Model_WorkspaceWidget $widget) {
		if(empty($widget))
			return;
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		// Widget
		
		$tpl->assign('widget', $widget);
		
		// Contexts
		
		$context_mfts = Extension_DevblocksContext::getAll(false, 'workspace');
		$tpl->assign('context_mfts', $context_mfts);
		
		// Grab the latest view and copy it to _config

		if(false !== ($view = $this->getView($widget))) {
			$view->id .= '_config';
			$view->is_ephemeral = true;
		}
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/worklist/config.tpl');
	}
	
	function saveConfig(Model_WorkspaceWidget $widget) {
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', array());

		// Convert the serialized model to proper JSON before saving
		
		if(isset($params['worklist_model_json'])) {
			$worklist_model = json_decode($params['worklist_model_json'], true);
			unset($params['worklist_model_json']);
			
			if(empty($worklist_model) && isset($params['context'])) {
				if(false != ($context_ext = Extension_DevblocksContext::get($params['context']))) {
					if(false != (@$worklist_model = json_decode(C4_AbstractViewLoader::serializeViewToAbstractJson($context_ext->getChooserView(), $context_ext->id), true))) {
						$worklist_model['context'] = $context_ext->id;
					}
				}
			}
			
			$params['worklist_model'] = $worklist_model;
		}
		
		// Save
		DAO_WorkspaceWidget::update($widget->id, array(
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		));
	}
	
	function exportData(Model_WorkspaceWidget $widget, $format=null) {
		if(false == ($view = $this->getView($widget)))
			return false;
		
		switch(strtolower($format)) {
			case 'csv':
				return $this->_exportDataAsCSV($widget, $view);
				break;
				
			case 'json':
			default:
				return $this->_exportDataAsJson($widget, $view);
				break;
		}
	}
	
	private function _exportDataLoadAsContexts(Model_WorkspaceWidget $widget, $view) {
		$results = array();
		
		@$context_ext = Extension_DevblocksContext::getByViewClass(get_class($view));

		if(empty($context_ext))
			return array();
		
		$models = $view->getDataAsObjects();
		
		/*
		 * [TODO] This should be able to reuse lazy loads (e.g. every calendar_event may share
		 * a calendar_event.calendar_id link to the same record
		 *
		 */
		
		foreach($models as $model) {
			$labels = array();
			$values = array();
			CerberusContexts::getContext($context_ext->id, $model, $labels, $values, null, true);
			
			unset($values['_loaded']);
			
			$dict = new DevblocksDictionaryDelegate($values);
			
			if(isset($context_ext->params['context_expand_export'])) {
				@$context_expand = DevblocksPlatform::parseCsvString($context_ext->params['context_expand_export']);
				
				foreach($context_expand as $expand)
					$dict->$expand;
			}
			
			$values = $dict->getDictionary();
			
			foreach($values as $k => $v) {
				// Hide complex values
				if(!is_string($v) && !is_numeric($v)) {
					unset($values[$k]);
					continue;
				}
				
				// Hide any failed key lookups
				if(substr($k,0,1) == '_' && is_null($v)) {
					unset($values[$k]);
					continue;
				}
				
				// Hide meta data
				if(preg_match('/__context$/', $k)) {
					unset($values[$k]);
					continue;
				}
			}
			
			$results[] = $values;
		}
		
		return $results;
	}
	
	private function _exportDataAsJson(Model_WorkspaceWidget $widget, $view) {
		$export_data = array(
			'widget' => array(
				'label' => $widget->label,
				'type' => 'worklist',
				'version' => 'Cerb ' . APP_VERSION,
				'page' => $view->renderPage,
				'count' => 0,
				'results' => array(),
			),
		);
		
		$results = $this->_exportDataLoadAsContexts($widget, $view);
		
		$export_data['widget']['count'] = count($results);
		$export_data['widget']['results'] = $results;
			
		return DevblocksPlatform::strFormatJson(json_encode($export_data));
	}
	
	private function _exportDataAsCSV(Model_WorkspaceWidget $widget, $view) {
		$results = $this->_exportDataLoadAsContexts($widget, $view);
		
		$fp = fopen("php://temp", 'r+');

		if(!empty($results)) {
			$first_result = current($results);
			$headings = array();
			
			foreach(array_keys($first_result) as $k)
				$headings[] = $k;
			
			fputcsv($fp, $headings);
		}
		
		foreach($results as $result) {
			fputcsv($fp, $result);
		}
		
		rewind($fp);

		$output = "";
		
		while(!feof($fp)) {
			$output .= fgets($fp);
		}
		
		fclose($fp);
		
		return $output;
	}
};

class WorkspaceWidget_CustomHTML extends Extension_WorkspaceWidget {
	function render(Model_WorkspaceWidget $widget) {
		$this->_renderHtml($widget);
	}
	
	private function _renderHtml(Model_WorkspaceWidget $widget) {
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(empty($active_worker))
			return;
		
		@$content = $widget->params['content'];
		
		$labels = array();
		$values = array();
		
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $active_worker, $worker_labels, $worker_values, null, true, true);
		CerberusContexts::merge('current_worker_', null, $worker_labels, $worker_values, $labels, $values);
		
		$dict = new DevblocksDictionaryDelegate($values);
		
		echo $tpl_builder->build($content, $dict);
	}
	
	// Config
	
	function renderConfig(Model_WorkspaceWidget $widget) {
		if(empty($widget))
			return;
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		// Widget
		
		$tpl->assign('widget', $widget);
		
		// Placeholders
		
		$labels = array();
		$values = array();
		
		if(false != ($active_worker = CerberusApplication::getActiveWorker())) {
			$active_worker->getPlaceholderLabelsValues($labels, $values);
			$tpl->assign('labels', $labels);
		}
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/custom_html/config.tpl');
	}
	
	function saveConfig(Model_WorkspaceWidget $widget) {
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', array());
		
		DAO_WorkspaceWidget::update($widget->id, array(
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		));

		// Clear caches
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(sprintf("widget%d_datasource", $widget->id));
	}
};

class WorkspaceWidget_PieChart extends Extension_WorkspaceWidget implements ICerbWorkspaceWidget_ExportData {
	private function _loadData(Model_WorkspaceWidget &$widget) {
		// Per series datasources
		@$datasource_extid = $widget->params['datasource'];

		if(empty($datasource_extid)) {
			return false;
		}
		
		if(null == ($datasource_ext = Extension_WorkspaceWidgetDatasource::get($datasource_extid)))
			return false;
		
		$data = $datasource_ext->getData($widget, $widget->params);

		// Convert raw data
		if(isset($data['data'])) {
			foreach($data['data'] as $wedge) {
				$label = @$wedge['metric_label'] ?: '';
				$value = @$wedge['metric_value'] ?: 0;
				
				if(empty($value))
					continue;
				
				$data['wedge_labels'][] = $label;
				$data['wedge_values'][] = $value;
			}
			
			unset($data['data']);
		}
		
		if(!empty($data))
			$widget->params = $data;
		
		$wedge_colors = array(
			'#57970A',
			'#007CBD',
			'#7047BA',
			'#8B0F98',
			'#CF2C1D',
			'#E97514',
			'#FFA100',
			'#3E6D07',
			'#345C05',
			'#005988',
			'#004B73',
			'#503386',
			'#442B71',
			'#640A6D',
			'#55085C',
			'#951F14',
			'#7E1A11',
			'#A8540E',
			'#8E470B',
			'#B87400',
			'#9C6200',
			'#CCCCCC',
		);
		$widget->params['wedge_colors'] = $wedge_colors;
		
		return true;
	}
	
	function render(Model_WorkspaceWidget $widget) {
		$tpl = DevblocksPlatform::getTemplateService();

		if(false == $this->_loadData($widget)) {
			echo "This pie chart doesn't have a data source. Configure it and select one.";
			return;
		}

		$tpl->assign('widget', $widget);
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/pie_chart/pie_chart.tpl');
	}
	
	// Config
	
	function renderConfig(Model_WorkspaceWidget $widget) {
		if(empty($widget))
			return;
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		// Widget
		
		$tpl->assign('widget', $widget);
		
		// Limit to widget
		
		$datasource_mfts = Extension_WorkspaceWidgetDatasource::getAll(false, $this->manifest->id);
		$tpl->assign('datasource_mfts', $datasource_mfts);
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/pie_chart/config.tpl');
	}
	
	function saveConfig(Model_WorkspaceWidget $widget) {
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', array());
		
		DAO_WorkspaceWidget::update($widget->id, array(
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		));

		// Clear caches
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(sprintf("widget%d_datasource", $widget->id));
	}
	
	// Export
	
	function exportData(Model_WorkspaceWidget $widget, $format=null) {
		if(false == $this->_loadData($widget)) {
			return;
		}
		
		switch(strtolower($format)) {
			case 'csv':
				return $this->_exportDataAsCsv($widget);
				break;
				
			default:
			case 'json':
				return $this->_exportDataAsJson($widget);
				break;
		}
		
		return false;
	}
	
	private function _exportDataAsCsv(Model_WorkspaceWidget $widget) {
		if(!isset($widget->params['wedge_labels']))
			return false;
		
		if(!is_array($widget->params['wedge_labels']))
			return false;
		
		$results = array();
		
		$results[] = array(
			'Label',
			'Count',
		);
		
		foreach(array_keys($widget->params['wedge_labels']) as $idx) {
			@$wedge_label = $widget->params['wedge_labels'][$idx];
			@$wedge_value = $widget->params['wedge_values'][$idx];

			$results[] = array(
				$wedge_label,
				$wedge_value,
			);
		}
		
		$fp = fopen("php://temp", 'r+');
		
		foreach($results as $result) {
			fputcsv($fp, $result);
		}
		
		rewind($fp);

		$output = "";
		
		while(!feof($fp)) {
			$output .= fgets($fp);
		}
		
		fclose($fp);
		
		return $output;
	}
	
	private function _exportDataAsJson(Model_WorkspaceWidget $widget) {
		if(!isset($widget->params['wedge_labels']))
			return false;
		
		if(!is_array($widget->params['wedge_labels']))
			return false;
		
		
		$results = array(
			'widget' => array(
				'label' => $widget->label,
				'type' => 'pie',
				'version' => 'Cerb ' . APP_VERSION,
				'counts' => array(),
			),
		);

		foreach(array_keys($widget->params['wedge_labels']) as $idx) {
			@$wedge_label = $widget->params['wedge_labels'][$idx];
			@$wedge_value = $widget->params['wedge_values'][$idx];
			@$wedge_color = $widget->params['wedge_colors'][$idx];

			// Reuse the last color
			if(empty($wedge_color))
				$wedge_color = end($widget->params['wedge_colors']);
			
			$results['widget']['counts'][] = array(
				'label' => $wedge_label,
				'count' => $wedge_value,
				'color' => $wedge_color,
			);
		}
		
		return DevblocksPlatform::strFormatJson(json_encode($results));
	}
};

class WorkspaceWidget_Scatterplot extends Extension_WorkspaceWidget implements ICerbWorkspaceWidget_ExportData {
	private function _loadData(Model_WorkspaceWidget &$widget) {
		@$series = $widget->params['series'];
		
		if(empty($series)) {
			return false;
		}
		
		// Multiple datasources
		if(is_array($series))
		foreach($series as $series_idx => $series_params) {
			@$datasource_extid = $series_params['datasource'];

			if(empty($datasource_extid))
				continue;
			
			if(null == ($datasource_ext = Extension_WorkspaceWidgetDatasource::get($datasource_extid)))
				continue;
			
			$params_prefix = sprintf("[series][%d]", $series_idx);
			
			$data = $datasource_ext->getData($widget, $series_params, $params_prefix);

			if(!empty($data))
				$widget->params['series'][$series_idx] = $data;
		}
		
		return true;
	}
	
	function render(Model_WorkspaceWidget $widget) {
		$tpl = DevblocksPlatform::getTemplateService();

		if(false == ($this->_loadData($widget))) {
			echo "This scatterplot doesn't have any data sources. Configure it and select one.";
			return;
		}
		
		$tpl->assign('widget', $widget);

		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/scatterplot/scatterplot.tpl');
	}
	
	// Config
	
	function renderConfig(Model_WorkspaceWidget $widget) {
		if(empty($widget))
			return;
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		// Widget
		
		$tpl->assign('widget', $widget);
		
		// Limit to widget
		
		$datasource_mfts = Extension_WorkspaceWidgetDatasource::getAll(false, $this->manifest->id);
		$tpl->assign('datasource_mfts', $datasource_mfts);
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/scatterplot/config.tpl');
	}
	
	function saveConfig(Model_WorkspaceWidget $widget) {
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', array());
		
		// [TODO] The extension should be able to filter the properties here
		
		foreach($params['series'] as $idx => $series) {
			// Convert the serialized model to proper JSON before saving
		
			if(isset($series['worklist_model_json'])) {
				$worklist_model = json_decode($series['worklist_model_json'], true);
				unset($series['worklist_model_json']);
				
				if(empty($worklist_model) && isset($series['context'])) {
					if(false != ($context_ext = Extension_DevblocksContext::get($series['context']))) {
						if(false != (@$worklist_model = json_decode(C4_AbstractViewLoader::serializeViewToAbstractJson($context_ext->getChooserView(), $context_ext->id), true))) {
							$worklist_model['context'] = $context_ext->id;
						}
					}
				}
				
				$series['worklist_model'] = $worklist_model;
				$params['series'][$idx] = $series;
			}
		}
		
		DAO_WorkspaceWidget::update($widget->id, array(
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		));

		// Clear caches
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(sprintf("widget%d_datasource", $widget->id));
	}
	
	// Export
	
	function exportData(Model_WorkspaceWidget $widget, $format=null) {
		if(false == ($this->_loadData($widget))) {
			return;
		}
		
		switch(strtolower($format)) {
			case 'csv':
				return $this->_exportDataAsCsv($widget);
				break;
				
			default:
			case 'json':
				return $this->_exportDataAsJson($widget);
				break;
		}
		
		return false;
	}
	
	private function _exportDataAsCsv(Model_WorkspaceWidget $widget) {
		$series = $widget->params['series'];
		
		$results = array();
		
		$results[] = array(
			'Series #',
			'Series Label',
			'Data X Label',
			'Data X Value',
			'Data Y Label',
			'Data Y Value',
		);
		
		if(is_array($series))
		foreach($series as $series_idx => $series_params) {
			if(!isset($series_params['data']) || empty($series_params['data']))
				continue;
		
			$data = $series_params['data'];
			
			if(is_array($data))
			foreach($data as $k => $v) {
				$results[] = array(
					'series' => $series_idx,
					'series_label' => $series_params['label'],
					'x_label' => $v['x_label'],
					'x' => $v['x'],
					'y_label' => $v['y_label'],
					'y' => $v['y'],
				);
			}
		}
		
		$fp = fopen("php://temp", 'r+');
		
		foreach($results as $result) {
			fputcsv($fp, $result);
		}
		
		rewind($fp);

		$output = "";
		
		while(!feof($fp)) {
			$output .= fgets($fp);
		}
		
		fclose($fp);
		
		return $output;
	}
	
	private function _exportDataAsJson(Model_WorkspaceWidget $widget) {
		$series = $widget->params['series'];
		
		$results = array(
			'widget' => array(
				'label' => $widget->label,
				'type' => 'scatterplot',
				'version' => 'Cerb ' . APP_VERSION,
				'series' => array(),
			),
		);
		
		if(is_array($series))
		foreach($series as $series_idx => $series_params) {
			if(!isset($series_params['data']) || empty($series_params['data']))
				continue;
		
			$data = $series_params['data'];
			
			$results['widget']['series'][$series_idx] = array(
				'id' => $series_idx,
				'label' => $series_params['label'],
				'data' => array(),
			);
			
			if(is_array($data))
			foreach($data as $k => $v) {
				$row = array(
					'x' => $v['x'],
					'x_label' => $v['x_label'],
					'y' => $v['y'],
					'y_label' => $v['y_label'],
				);
				
				$results['widget']['series'][$series_idx]['data'][] = $row;
			}
		}
		
		return DevblocksPlatform::strFormatJson(json_encode($results));
	}
};