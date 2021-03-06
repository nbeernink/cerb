<?php
class DAO_ContextBulkUpdate extends Cerb_ORMHelper {
	const ID = 'id';
	const BATCH_KEY = 'batch_key';
	const CONTEXT = 'context';
	const CONTEXT_IDS = 'context_ids';
	const NUM_RECORDS = 'num_records';
	const WORKER_ID = 'worker_id';
	const VIEW_ID = 'view_id';
	const CREATED_AT = 'created_at';
	const STATUS_ID = 'status_id';
	const ACTIONS_JSON = 'actions_json';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "INSERT INTO context_bulk_update () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function createFromView(C4_AbstractView $view, array $do, $batch_size=null, $dao_class=null, $search_class=null) {
		$context = null;
		
		if(empty($do))
			return false;
		
		if(empty($batch_size) || !is_numeric($batch_size))
			$batch_size = 100;
		
		// Generate batch jobs
		$view_class = get_class($view);
		
		// Autoload classes
		if(false != ($context_ext = Extension_DevblocksContext::getByViewClass($view_class, true))) { /* @var $context_ext Extension_DevblocksContext */
			$context = $context_ext->id;
		
			if(is_null($dao_class))
				$dao_class = $context_ext->getDaoClass();
			
			if(is_null($search_class))
				$search_class = $context_ext->getSearchClass();
		}
		
		if(!$dao_class || !class_exists($dao_class))
			return false;
		
		if(!$search_class || !class_exists($search_class) || !class_implements('DevblocksSearchFields'))
			return false;
		
		if(false == ($pkey = $search_class::getPrimaryKey()) || empty($pkey))
			return false;
		
		$actions_json = json_encode($do);
		$batch_key = uniqid();
		$current_worker = CerberusApplication::getActiveWorker();
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$params = $view->getParams();
		
		if(false == ($query_parts = $dao_class::getSearchQueryComponents(array(), $params)))
			return false;
		
		$db->ExecuteSlave('set @rank=0');
		$db->ExecuteSlave('set group_concat_max_len = 1024000');
		
		$sql = sprintf('CREATE TEMPORARY TABLE _bulk SELECT %s AS id, @rank:=@rank+1 AS rank ', $pkey).
			$query_parts['join'].
			$query_parts['where']
			;
		$db->ExecuteSlave($sql);
		
		$sql = sprintf('INSERT INTO context_bulk_update (batch_key, context, context_ids, num_records, worker_id, view_id, created_at, status_id, actions_json) '.
			'SELECT %s as batch_key, %s as context, GROUP_CONCAT(id) AS context_ids, COUNT(id) as num_records, %d as worker_id, %s as view_id, %d as created_at, 0 as status_id, %s as actions_json '.
			'FROM _bulk '.
			'GROUP BY FLOOR(rank/%d)',
			$db->qstr($batch_key),
			$db->qstr($context),
			($current_worker ? $current_worker->id : 0),
			$db->qstr($view->id),
			time(),
			$db->qstr($actions_json),
			$batch_size
		);
		$db->ExecuteSlave($sql);
		
		$db->ExecuteSlave('DROP TABLE _bulk');
		
		return $batch_key;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = array($ids);
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;
				
			// Send events
			if($check_deltas) {
				//CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_, $batch_ids);
			}
			
			// Make changes
			parent::_update($batch_ids, 'context_bulk_update', $fields);
			
			// Send events
			if($check_deltas) {
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::getEventService();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.context_bulk_update.update',
						array(
							'fields' => $fields,
						)
					)
				);
				
				// Log the context update
				//DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_, $batch_ids);
			}
		}
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('context_bulk_update', $fields, $where);
	}
	
	/**
	 * 
	 * @param string $cursor
	 * @return Model_ContextBulkUpdate|boolean
	 */
	static function getNextByCursor($cursor) {
		$where = sprintf("status_id = 0 AND batch_key = %s", Cerb_ORMHelper::qstr($cursor));
		
		$results = self::getWhere(
			$where,
			DAO_ContextBulkUpdate::ID,
			true,
			1
		);
		
		if(is_array($results) && !empty($results))
			return array_shift($results);
		
		return false;
	}
	
	/**
	 * 
	 * @param string $cursor
	 * @return integer
	 */
	static function getTotalByCursor($cursor) {
		$db = DevblocksPlatform::getDatabaseService();
		return $db->GetOneSlave(sprintf("SELECT SUM(num_records) FROM context_bulk_update WHERE batch_key = %s", $db->qstr($cursor)));
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_ContextBulkUpdate[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, batch_key, context, context_ids, num_records, worker_id, view_id, created_at, status_id, actions_json ".
			"FROM context_bulk_update ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		
		if($options & Cerb_ORMHelper::OPT_GET_MASTER_ONLY) {
			$rs = $db->ExecuteMaster($sql, _DevblocksDatabaseManager::OPT_NO_READ_AFTER_WRITE);
		} else {
			$rs = $db->ExecuteSlave($sql);
		}
		
		return self::_getObjectsFromResult($rs);
	}
	
	/**
	 *
	 * @param bool $nocache
	 * @return Model_ContextBulkUpdate[]
	 */
	static function getAll($nocache=false) {
		//$cache = DevblocksPlatform::getCacheService();
		//if($nocache || null === ($objects = $cache->load(self::_CACHE_ALL))) {
			$objects = self::getWhere(null, DAO_ContextBulkUpdate::NAME, true, null, Cerb_ORMHelper::OPT_GET_MASTER_ONLY);
			
			//if(!is_array($objects))
			//	return false;
				
			//$cache->save($objects, self::_CACHE_ALL);
		//}
		
		return $objects;
	}

	/**
	 * @param integer $id
	 * @return Model_ContextBulkUpdate
	 */
	static function get($id) {
		if(empty($id))
			return null;
		
		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}
	
	/**
	 * 
	 * @param array $ids
	 * @return Model_ContextBulkUpdate[]
	 */
	static function getIds($ids) {
		if(!is_array($ids))
			$ids = array($ids);

		if(empty($ids))
			return array();

		if(!method_exists(get_called_class(), 'getWhere'))
			return array();

		$db = DevblocksPlatform::getDatabaseService();

		$ids = DevblocksPlatform::importVar($ids, 'array:integer');

		$models = array();

		$results = static::getWhere(sprintf("id IN (%s)",
			implode(',', $ids)
		));

		// Sort $models in the same order as $ids
		foreach($ids as $id) {
			if(isset($results[$id]))
				$models[$id] = $results[$id];
		}

		unset($results);

		return $models;
	}	
	
	/**
	 * @param resource $rs
	 * @return Model_ContextBulkUpdate[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_ContextBulkUpdate();
			$object->id = intval($row['id']);
			$object->batch_key = $row['batch_key'];
			$object->context = $row['context'];
			$object->context_ids = DevblocksPlatform::parseCsvString($row['context_ids']);
			$object->num_records = intval($row['num_records']);
			$object->worker_id = intval($row['worker_id']);
			$object->view_id = $row['view_id'];
			$object->created_at = intval($row['created_at']);
			$object->status_id = intval($row['status_id']);
			
			if(!empty($row['actions_json']) && false != ($actions = json_decode($row['actions_json'], true)))
				$object->actions = $actions;
			
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function random() {
		return self::_getRandom('context_bulk_update');
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM context_bulk_update WHERE id IN (%s)", $ids_list));
		
		// Fire event
		$eventMgr = DevblocksPlatform::getEventService();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => 'cerberusweb.contexts.context.bulk.update',
					'context_ids' => $ids
				)
			)
		);
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_ContextBulkUpdate::getFields();
		
		list($tables,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_ContextBulkUpdate', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"context_bulk_update.id as %s, ".
			"context_bulk_update.batch_key as %s, ".
			"context_bulk_update.context as %s, ".
			"context_bulk_update.context_ids as %s, ".
			"context_bulk_update.num_records as %s, ".
			"context_bulk_update.worker_id as %s, ".
			"context_bulk_update.view_id as %s, ".
			"context_bulk_update.created_at as %s, ".
			"context_bulk_update.status_id as %s, ".
			"context_bulk_update.actions_json as %s ",
				SearchFields_ContextBulkUpdate::ID,
				SearchFields_ContextBulkUpdate::BATCH_KEY,
				SearchFields_ContextBulkUpdate::CONTEXT,
				SearchFields_ContextBulkUpdate::CONTEXT_IDS,
				SearchFields_ContextBulkUpdate::NUM_RECORDS,
				SearchFields_ContextBulkUpdate::WORKER_ID,
				SearchFields_ContextBulkUpdate::VIEW_ID,
				SearchFields_ContextBulkUpdate::CREATED_AT,
				SearchFields_ContextBulkUpdate::STATUS_ID,
				SearchFields_ContextBulkUpdate::ACTIONS_JSON
			);
			
		$join_sql = "FROM context_bulk_update ".
			(isset($tables['context_link']) ? sprintf("INNER JOIN context_link ON (context_link.to_context = %s AND context_link.to_context_id = context_bulk_update.id) ", Cerb_ORMHelper::qstr('cerberusweb.contexts.context.bulk.update')) : " ").
			'';
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_ContextBulkUpdate');
	
		// Virtuals
		
		$args = array(
			'join_sql' => &$join_sql,
			'where_sql' => &$where_sql,
			'tables' => &$tables,
			'has_multiple_values' => &$has_multiple_values
		);
	
		array_walk_recursive(
			$params,
			array('DAO_ContextBulkUpdate', '_translateVirtualParameters'),
			$args
		);
		
		return array(
			'primary_table' => 'context_bulk_update',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => $has_multiple_values,
			'sort' => $sort_sql,
		);
	}
	
	private static function _translateVirtualParameters($param, $key, &$args) {
		if(!is_a($param, 'DevblocksSearchCriteria'))
			return;
			
		$from_context = 'cerberusweb.contexts.context.bulk.update';
		$from_index = 'context_bulk_update.id';
		
		$param_key = $param->field;
		settype($param_key, 'string');
		
		switch($param_key) {
			case SearchFields_ContextBulkUpdate::VIRTUAL_CONTEXT_LINK:
				$args['has_multiple_values'] = true;
				self::_searchComponentsVirtualContextLinks($param, $from_context, $from_index, $args['join_sql'], $args['where_sql']);
				break;
		
			case SearchFields_ContextBulkUpdate::VIRTUAL_HAS_FIELDSET:
				self::_searchComponentsVirtualHasFieldset($param, $from_context, $from_index, $args['join_sql'], $args['where_sql']);
				break;
		}
	}
	
	/**
	 * Enter description here...
	 *
	 * @param array $columns
	 * @param DevblocksSearchCriteria[] $params
	 * @param integer $limit
	 * @param integer $page
	 * @param string $sortBy
	 * @param boolean $sortAsc
	 * @param boolean $withCounts
	 * @return array
	 */
	static function search($columns, $params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$db = DevblocksPlatform::getDatabaseService();
		
		// Build search queries
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		$select_sql = $query_parts['select'];
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		$has_multiple_values = $query_parts['has_multiple_values'];
		$sort_sql = $query_parts['sort'];
		
		$sql =
			$select_sql.
			$join_sql.
			$where_sql.
			($has_multiple_values ? 'GROUP BY context_bulk_update.id ' : '').
			$sort_sql;
			
		if($limit > 0) {
			if(false == ($rs = $db->SelectLimit($sql,$limit,$page*$limit)))
				return false;
		} else {
			if(false == ($rs = $db->ExecuteSlave($sql)))
				return false;
			$total = mysqli_num_rows($rs);
		}
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		$results = array();
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object_id = intval($row[SearchFields_ContextBulkUpdate::ID]);
			$results[$object_id] = $row;
		}

		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					($has_multiple_values ? "SELECT COUNT(DISTINCT context_bulk_update.id) " : "SELECT COUNT(context_bulk_update.id) ").
					$join_sql.
					$where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}
		
		mysqli_free_result($rs);
		
		return array($results,$total);
	}

};

class SearchFields_ContextBulkUpdate extends DevblocksSearchFields {
	const ID = 'c_id';
	const BATCH_KEY = 'c_batch_key';
	const CONTEXT = 'c_context';
	const CONTEXT_IDS = 'c_context_ids';
	const NUM_RECORDS = 'c_num_records';
	const WORKER_ID = 'c_worker_id';
	const VIEW_ID = 'c_view_id';
	const CREATED_AT = 'c_created_at';
	const STATUS_ID = 'c_status_id';
	const ACTIONS_JSON = 'c_actions_json';

	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_WATCHERS = '*_workers';
	
	const CONTEXT_LINK = 'cl_context_from';
	const CONTEXT_LINK_ID = 'cl_context_from_id';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'context_bulk_update.id';
	}
	
	static function getCustomFieldContextKeys() {
		// [TODO] Context
		return array(
			'' => new DevblocksSearchFieldContextKeys('context_bulk_update.id', self::ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			/*
			case self::VIRTUAL_WATCHERS:
				return self::_getWhereSQLFromWatchersField($param, '', self::getPrimaryKey());
				break;
			*/
			
			default:
				if('cf_' == substr($param->field, 0, 3)) {
					return self::_getWhereSQLFromCustomFields($param);
				} else {
					return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
				}
				break;
		}
	}
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		if(is_null(self::$_fields))
			self::$_fields = self::_getFields();
		
		return self::$_fields;
	}
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function _getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'context_bulk_update', 'id', $translate->_('common.id'), null, true),
			self::BATCH_KEY => new DevblocksSearchField(self::BATCH_KEY, 'context_bulk_update', 'batch_key', $translate->_('dao.context_bulk_update.batch_key'), null, true),
			self::CONTEXT => new DevblocksSearchField(self::CONTEXT, 'context_bulk_update', 'context', $translate->_('common.context'), null, true),
			self::CONTEXT_IDS => new DevblocksSearchField(self::CONTEXT_IDS, 'context_bulk_update', 'context_ids', $translate->_('dao.context_bulk_update.context_ids'), null, true),
			self::NUM_RECORDS => new DevblocksSearchField(self::NUM_RECORDS, 'context_bulk_update', 'num_records', $translate->_('dao.context_bulk_update.num_records'), null, true),
			self::WORKER_ID => new DevblocksSearchField(self::WORKER_ID, 'context_bulk_update', 'worker_id', $translate->_('common.worker'), null, true),
			self::VIEW_ID => new DevblocksSearchField(self::VIEW_ID, 'context_bulk_update', 'view_id', $translate->_('common.view'), null, true),
			self::CREATED_AT => new DevblocksSearchField(self::CREATED_AT, 'context_bulk_update', 'created_at', $translate->_('common.created'), null, true),
			self::STATUS_ID => new DevblocksSearchField(self::STATUS_ID, 'context_bulk_update', 'status_id', $translate->_('common.status'), null, true),
			self::ACTIONS_JSON => new DevblocksSearchField(self::ACTIONS_JSON, 'context_bulk_update', 'actions_json', $translate->_('common.actions'), null, true),

			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null, false),
			self::VIRTUAL_WATCHERS => new DevblocksSearchField(self::VIRTUAL_WATCHERS, '*', 'workers', $translate->_('common.watchers'), 'WS', false),
			
			self::CONTEXT_LINK => new DevblocksSearchField(self::CONTEXT_LINK, 'context_link', 'from_context', null, null, false),
			self::CONTEXT_LINK_ID => new DevblocksSearchField(self::CONTEXT_LINK_ID, 'context_link', 'from_context_id', null, null, false),
		);
		
		// Custom Fields
		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array_keys(self::getCustomFieldContextKeys()));
		
		if(!empty($custom_columns))
			$columns = array_merge($columns, $custom_columns);

		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Model_ContextBulkUpdate {
	public $id = 0;
	public $batch_key = null;
	public $context = null;
	public $context_ids = array();
	public $num_records = 0;
	public $worker_id = 0;
	public $view_id = null;
	public $created_at = 0;
	public $status_id = 0;
	public $actions = array();
	
	function markInProgress() {
		DAO_ContextBulkUpdate::update($this->id, array(
			DAO_ContextBulkUpdate::STATUS_ID => 1,
		));
	}
	
	function markCompleted() {
		DAO_ContextBulkUpdate::update($this->id, array(
			DAO_ContextBulkUpdate::STATUS_ID => 2,
		));
	}
};

