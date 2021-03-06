<?php
/**
 * Session Management Singleton
 *
 * @static
 * @ingroup services
 */
class _DevblocksSessionManager {
	var $visit = null;
	private $_handler_class = null;
	
	/**
	 * @private
	 */
	private function _DevblocksSessionManager() {}
	
	/**
	 * Returns an instance of the session manager
	 *
	 * @static
	 * @return _DevblocksSessionManager
	 */
	static function getInstance() {
		static $instance = null;
		if(null == $instance) {
			$url_writer = DevblocksPlatform::getUrlService();
			
			$prefix = (APP_DB_PREFIX != '') ? APP_DB_PREFIX.'_' : ''; // [TODO] Cleanup
			
			@session_destroy();
			
			$handler_class = DevblocksPlatform::getHandlerSession();
			
			session_set_save_handler(
				array($handler_class, 'open'),
				array($handler_class, 'close'),
				array($handler_class, 'read'),
				array($handler_class, 'write'),
				array($handler_class, 'destroy'),
				array($handler_class, 'gc')
			);

			$session_lifespan = DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::SESSION_LIFESPAN, CerberusSettingsDefaults::SESSION_LIFESPAN);

			session_name(APP_SESSION_NAME);
			session_set_cookie_params($session_lifespan, '/', NULL, $url_writer->isSSL(), true);
			
			if(php_sapi_name() != 'cli')
				session_start();
			
			$instance = new _DevblocksSessionManager();
			$instance->visit = isset($_SESSION['db_visit']) ? $_SESSION['db_visit'] : NULL; /* @var $visit DevblocksVisit */
			$instance->_handler_class = $handler_class;
		}
		
		return $instance;
	}
	
	/**
	 * Returns the current session or NULL if no session exists.
	 *
	 * @return DevblocksVisit
	 */
	function getVisit() {
		return $this->visit;
	}
	
	/**
	 * @param DevblocksVisit $visit
	 */
	function setVisit(DevblocksVisit $visit = null) {
		$this->visit = $visit;
		$_SESSION['db_visit'] = $this->visit;
	}
	
	function getAll() {
		return call_user_func(array($this->_handler_class, 'getAll'));
	}
	
	/**
	 * Kills the specified or current session.
	 *
	 */
	function clear($key=null) {
		if(is_null($key)) {
			$this->visit = null;
			setcookie('Devblocks', null, 0, '/', null);
			session_unset();
			session_destroy();
		} else {
			call_user_func(array($this->_handler_class, 'destroy'), $key);
		}
	}
	
	function clearAll() {
		self::clear();
		call_user_func(array($this->_handler_class, 'destroyAll'));
	}
};