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

class PageSection_SetupSecurity extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		
		$visit->set(ChConfigurationPage::ID, 'security');
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/security/index.tpl');
	}
	
	function saveJsonAction() {
		try {
			$worker = CerberusApplication::getActiveWorker();
			
			if(!$worker || !$worker->is_superuser)
				throw new Exception("You are not a superuser.");
			
			@$authorized_ips = DevblocksPlatform::importGPC($_POST['authorized_ips'],'string','');
			DevblocksPlatform::setPluginSetting('cerberusweb.core',CerberusSettings::AUTHORIZED_IPS, $authorized_ips);
			
			@$session_lifespan = DevblocksPlatform::importGPC($_POST['session_lifespan'],'integer',0);
			DevblocksPlatform::setPluginSetting('cerberusweb.core',CerberusSettings::SESSION_LIFESPAN, $session_lifespan);
			
			echo json_encode(array('status'=>true));
			return;
			
		} catch(Exception $e) {
			echo json_encode(array('status'=>false,'error'=>$e->getMessage()));
			return;
		}
	}
};