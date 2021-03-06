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

class Page_Search extends CerberusPageExtension {
	function isVisible() {
		// The current session must be a logged-in worker to use this page.
		if(null == ($worker = CerberusApplication::getActiveWorker()))
			return false;
		
		return true;
	}
	
	function handleSectionActionAction() {
		// GET has precedence over POST
		@$section_uri = DevblocksPlatform::importGPC(isset($_GET['section']) ? $_GET['section'] : $_REQUEST['section'],'string','');
		@$action = DevblocksPlatform::importGPC(isset($_GET['action']) ? $_GET['action'] : $_REQUEST['action'],'string','');

		$inst = Extension_PageSection::getExtensionByPageUri($this->manifest->id, $section_uri, true);
		
		if($inst instanceof Extension_PageSection && method_exists($inst, $action.'Action')) {
			call_user_func(array($inst, $action.'Action'));
		}
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$response = DevblocksPlatform::getHttpResponse();
		$active_worker = CerberusApplication::getActiveWorker();
		
		// Allow quick search queries to be sent in the URL
		@$query = DevblocksPlatform::importGPC($_REQUEST['q'], 'string', '');
		
		$stack = $response->path;
		@array_shift($stack); // search
		@$context_extid = array_shift($stack); // context
		
		if(empty($context_extid))
			return;
		
		if(null == ($context_ext = Extension_DevblocksContext::getByAlias($context_extid, true))) { /* @var $context_ext Extension_DevblocksContext */
			if(null == ($context_ext = Extension_DevblocksContext::get($context_extid)))
				return;
		}
		
		if(!isset($context_ext->manifest->params['options'][0]['workspace']))
			return;
		
		$tpl->assign('context_ext', $context_ext);
		
		if(false == ($view = $context_ext->getSearchView())) /* @var $view C4_AbstractView */
			return;
		
		// Quick search initialization
		
		if(!empty($query)) {
			$view->addParamsWithQuickSearch($query, true);
			$tpl->assign('quick_search_query', $query);
		}
		
		// Placeholders
		
		if($active_worker) {
			$labels = array();
			$values = array();
			$active_worker->getPlaceholderLabelsValues($labels, $values);
			
			$view->setPlaceholderLabels($labels);
			$view->setPlaceholderValues($values);
		}
		
		// Template
		
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::search/index.tpl');
	}
	
	function openSearchPopupAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$query = DevblocksPlatform::importGPC($_REQUEST['q'],'string','');

		if(false == ($context_ext = Extension_DevblocksContext::get($context)))
			return;
		
		// Verify that this context is publicly searchable
		@$context_options = $context_ext->manifest->params['options'][0];
		
		if(!is_array($context_options) || !isset($context_options['workspace']))
			return;
		
		if(false == ($view = $context_ext->getSearchView()) || !($view instanceof IAbstractView_QuickSearch))
			return;
		
		if(isset($_REQUEST['q']))
			$view->addParamsWithQuickSearch($query, true);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('context_ext', $context_ext);
		$tpl->assign('popup_title', $context_ext->manifest->name . ' Search');
		$tpl->assign('quick_search_query', $query);
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::search/popup.tpl');
	}
	
	function ajaxQuickSearchAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$query = DevblocksPlatform::importGPC($_REQUEST['query'],'string','');

		header("Content-type: application/json");
		
		if(null == ($view = C4_AbstractViewLoader::getView($view_id))) { /* @var $view C4_AbstractView */
			echo json_encode(null);
			return;
		}
		
		$replace_params = true;
		
		// Allow parameters to be added incrementally with a leading '+' character
		if('+' == substr($query,0,1)) {
			$replace_params = false;
			$query = ltrim($query, '+ ');
		}
		
		$view->addParamsWithQuickSearch($query, $replace_params);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view', $view);
		
		// Add search meta output to the view marquee
		
		if(false != ($context_ext = Extension_DevblocksContext::getByViewClass(get_class($view), true))) {
			$params = $view->getParams();
			$marquees = array();
			
			$search_class = $context_ext->getSearchClass();
			
			array_walk_recursive($params, function($param) use (&$marquees, $search_class) {
				if(!($param instanceof DevblocksSearchCriteria))
					return;
				
				if($param->operator == DevblocksSearchCriteria::OPER_FULLTEXT && isset($param->value[0])) {
					$search_class::getWhereSQL($param);
					
					$key = 'search_' . sha1($param->value[0]);
					if(false != ($meta = DevblocksPlatform::getRegistryKey($key, DevblocksRegistryEntry::TYPE_JSON)) 
							&& is_array($meta)
							&& isset($meta['total'])
							&& isset($meta['results'])
							&& isset($meta['took_ms'])
							&& isset($meta['engine'])
							) {
								
							if($meta['results'] < $meta['total']) {
								$marquees[] = sprintf("Found %s match%s for <b>%s</b>. Using %s. Try being more specific. [%s: %d ms]",
									number_format($meta['total']),
									(1 == $meta['results']) ? '' : 'es',
									DevblocksPlatform::strEscapeHtml($meta['query']),
									number_format($meta['results']),
									$meta['engine'],
									$meta['took_ms']
								);
							} else {
								$marquees[] = sprintf("Found %s match%s for <b>%s</b> [%s: %d ms]",
									number_format($meta['results']),
									(1 == $meta['results']) ? '' : 'es',
									DevblocksPlatform::strEscapeHtml($meta['query']),
									$meta['engine'],
									$meta['took_ms']
								);
							}
					}
				}
			});
				
			if(!empty($marquees)) {
				C4_AbstractView::setMarquee($view->id, implode('<br>', $marquees));
			}
		}
		
		$html = $tpl->fetch('devblocks:cerberusweb.core::internal/views/customize_view_criteria.tpl');
		
		echo json_encode(array(
			'status' => true,
			'html' => $html,
		));
		return;
	}
	
};