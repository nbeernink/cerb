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

class PageSection_SetupSessions extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		
		$visit->set(ChConfigurationPage::ID, 'sessions');

		// View
		
		$defaults = C4_AbstractViewModel::loadFromClass('View_DevblocksSession');
		$defaults->id = 'devblocks_sessions';
		
		$view = C4_AbstractViewLoader::getView($defaults->id, $defaults);
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/sessions/index.tpl');
	}
	
	function viewDeleteAction() {
		@$session_ids = DevblocksPlatform::importGPC($_REQUEST['row_id'],' array', array());
		
		$session = DevblocksPlatform::getSessionService();
		
		if(is_array($session_ids))
		foreach($session_ids as $session_id)
			$session->clear($session_id);
	}
};