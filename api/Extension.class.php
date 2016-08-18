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
/*
 * IMPORTANT LICENSING NOTE from your friends at Cerb
 *
 * Sure, it would be really easy to just cheat and edit this file to use
 * Cerb without paying for a license.  We trust you anyway.
 *
 * It takes a significant amount of time and money to develop, maintain,
 * and support high-quality enterprise software with a dedicated team.
 * For Cerb's entire history we've avoided taking money from outside
 * investors, and instead we've relied on actual sales from satisfied
 * customers to keep the project running.
 *
 * We've never believed in hiding our source code out of paranoia over not
 * getting paid.  We want you to have the full source code and be able to
 * make the tweaks your organization requires to get more done -- despite
 * having less of everything than you might need (time, people, money,
 * energy).  We shouldn't be your bottleneck.
 *
 * As a legitimate license owner, your feedback will help steer the project.
 * We'll also prioritize your issues, and work closely with you to make sure
 * your teams' needs are being met.
 *
 * - Jeff Standen and Dan Hildebrandt
 *	 Founders at Webgroup Media LLC; Developers of Cerb
 */

abstract class Extension_AppPreBodyRenderer extends DevblocksExtension
{
    public function render()
    {
    }
}

abstract class Extension_AppPostBodyRenderer extends DevblocksExtension
{
    public function render()
    {
    }
}

abstract class CerberusPageExtension extends DevblocksExtension
{
    public function isVisible()
    {
        return true;
    }

    public function render()
    {
    }
}

abstract class Extension_PluginSetup extends DevblocksExtension
{
    const POINT = 'cerberusweb.plugin.setup';

    public static function getByPlugin($plugin_id, $as_instances = true)
    {
        $results = [];

        // Include disabled extensions
        $all_extensions = DevblocksPlatform::getExtensionRegistry(true, true, true);
        foreach ($all_extensions as $k => $ext) { /* @var $ext DevblocksExtensionManifest */
            if ($ext->plugin_id == $plugin_id && $ext->point == self::POINT) {
                $results[$k] = ($as_instances) ? $ext->createInstance() : $ext;
            }
        }

        return $results;
    }

    abstract public function render();

    abstract public function save(&$errors);
}

abstract class Extension_PageSection extends DevblocksExtension
{
    const POINT = 'cerberusweb.ui.page.section';

    /**
     * @return DevblocksExtensionManifest[]|Extension_PageSection[]
     */
    public static function getExtensions($as_instances = true, $page_id = null)
    {
        if (empty($page_id)) {
            return DevblocksPlatform::getExtensions(self::POINT, $as_instances);
        }

        $results = [];

        $exts = DevblocksPlatform::getExtensions(self::POINT, false);
        foreach ($exts as $ext_id => $ext) {
            if (0 == strcasecmp($page_id, $ext->params['page_id'])) {
                $results[$ext_id] = $as_instances ? $ext->createInstance() : $ext;
            }
        }

        return $results;
    }

    /**
     * @param string $uri
     *
     * @return DevblocksExtensionManifest|Extension_PageSection
     */
    public static function getExtensionByPageUri($page_id, $uri, $as_instance = true)
    {
        $manifests = self::getExtensions(false, $page_id);

        foreach ($manifests as $mft) { /* @var $mft DevblocksExtensionManifest */
            if (0 == strcasecmp($uri, $mft->params['uri'])) {
                return $as_instance ? $mft->createInstance() : $mft;
            }
        }
    }

    abstract public function render();
}

abstract class Extension_PageMenu extends DevblocksExtension
{
    const POINT = 'cerberusweb.ui.page.menu';

    /**
     * @return DevblocksExtensionManifest[]|Extension_PageMenu[]
     */
    public static function getExtensions($as_instances = true, $page_id = null)
    {
        if (empty($page_id)) {
            return DevblocksPlatform::getExtensions(self::POINT, $as_instances);
        }

        $results = [];

        $exts = DevblocksPlatform::getExtensions(self::POINT, false);
        foreach ($exts as $ext_id => $ext) {
            if (0 == strcasecmp($page_id, $ext->params['page_id'])) {
                $results[$ext_id] = $as_instances ? $ext->createInstance() : $ext;
            }
        }

        // Sorting
        if ($as_instances) {
            DevblocksPlatform::sortObjects($results, 'manifest->name');
        } else {
            DevblocksPlatform::sortObjects($results, 'name');
        }

        return $results;
    }

    abstract public function render();
}

abstract class Extension_PageMenuItem extends DevblocksExtension
{
    const POINT = 'cerberusweb.ui.page.menu.item';

    /**
     * @return DevblocksExtensionManifest[]|Extension_PageMenuItem[]
     */
    public static function getExtensions($as_instances = true, $page_id = null, $menu_id = null)
    {
        if (empty($page_id) && empty($menu_id)) {
            return DevblocksPlatform::getExtensions(self::POINT, $as_instances);
        }

        $results = [];

        $exts = DevblocksPlatform::getExtensions(self::POINT, false);
        foreach ($exts as $ext_id => $ext) {
            if (empty($page_id) || 0 == strcasecmp($page_id, $ext->params['page_id'])) {
                if (empty($menu_id) || 0 == strcasecmp($menu_id, $ext->params['menu_id'])) {
                    $results[$ext_id] = $as_instances ? $ext->createInstance() : $ext;
                }
            }
        }

        // Sorting
        if ($as_instances) {
            DevblocksPlatform::sortObjects($results, 'manifest->name');
        } else {
            DevblocksPlatform::sortObjects($results, 'name');
        }

        return $results;
    }

    abstract public function render();
}

abstract class Extension_PreferenceTab extends DevblocksExtension
{
    const POINT = 'cerberusweb.preferences.tab';

    public function showTab()
    {
    }

    public function saveTab()
    {
    }
}

abstract class Extension_SendMailToolbarItem extends DevblocksExtension
{
    public function render()
    {
    }
}

abstract class Extension_MessageToolbarItem extends DevblocksExtension
{
    public function render(Model_Message $message)
    {
    }
}

abstract class Extension_ReplyToolbarItem extends DevblocksExtension
{
    public function render(Model_Message $message)
    {
    }
}

abstract class Extension_ExplorerToolbar extends DevblocksExtension
{
    public function render(Model_ExplorerSet $item)
    {
    }
}

abstract class Extension_MailTransport extends DevblocksExtension
{
    const POINT = 'cerberusweb.mail.transport';

    public static $_registry = [];

    /**
     * @return DevblocksExtensionManifest[]|Extension_MailTransport[]
     */
    public static function getAll($as_instances = true)
    {
        $exts = DevblocksPlatform::getExtensions(self::POINT, $as_instances);

        // Sorting
        if ($as_instances) {
            DevblocksPlatform::sortObjects($exts, 'manifest->name');
        } else {
            DevblocksPlatform::sortObjects($exts, 'name');
        }

        return $exts;
    }

    public static function get($extension_id)
    {
        if (isset(self::$_registry[$extension_id])) {
            return self::$_registry[$extension_id];
        }

        if (null != ($extension = DevblocksPlatform::getExtension($extension_id, true))
            && $extension instanceof self) {
            self::$_registry[$extension->id] = $extension;

            return $extension;
        }
    }

    abstract public function renderConfig(Model_MailTransport $model);

    abstract public function testConfig(array $params, &$error = null);

    abstract public function send(Swift_Message $message, Model_MailTransport $model);

    abstract public function getLastError();
}

abstract class Extension_ContextProfileTab extends DevblocksExtension
{
    const POINT = 'cerberusweb.ui.context.profile.tab';

    /**
     * @return DevblocksExtensionManifest[]|Extension_ContextProfileTab[]
     */
    public static function getExtensions($as_instances = true, $context = null)
    {
        if (empty($context)) {
            return DevblocksPlatform::getExtensions(self::POINT, $as_instances);
        }

        $results = [];

        $exts = DevblocksPlatform::getExtensions(self::POINT, false);

        foreach ($exts as $ext_id => $ext) {
            if (isset($ext->params['contexts'][0])) {
                foreach (array_keys($ext->params['contexts'][0]) as $ctx_pattern) {
                    $ctx_pattern = DevblocksPlatform::strToRegExp($ctx_pattern);

                    if (preg_match($ctx_pattern, $context)) {
                        $results[$ext_id] = $as_instances ? $ext->createInstance() : $ext;
                    }
                }
            }
        }

        // Sorting
        if ($as_instances) {
            DevblocksPlatform::sortObjects($results, 'manifest->name');
        } else {
            DevblocksPlatform::sortObjects($results, 'name');
        }

        return $results;
    }

    public function showTab($context, $context_id)
    {
    }
}

abstract class Extension_ContextProfileScript extends DevblocksExtension
{
    const POINT = 'cerberusweb.ui.context.profile.script';

    /**
     * @return DevblocksExtensionManifest[]|Extension_ContextProfileScript[]
     */
    public static function getExtensions($as_instances = true, $context = null)
    {
        if (empty($context)) {
            return DevblocksPlatform::getExtensions(self::POINT, $as_instances);
        }

        $results = [];

        $exts = DevblocksPlatform::getExtensions(self::POINT, false);

        foreach ($exts as $ext_id => $ext) {
            if (isset($ext->params['contexts'][0])) {
                foreach (array_keys($ext->params['contexts'][0]) as $ctx_pattern) {
                    $ctx_pattern = DevblocksPlatform::strToRegExp($ctx_pattern);

                    if (preg_match($ctx_pattern, $context)) {
                        $results[$ext_id] = $as_instances ? $ext->createInstance() : $ext;
                    }
                }
            }
        }

        // Sorting
        if ($as_instances) {
            DevblocksPlatform::sortObjects($results, 'manifest->name');
        } else {
            DevblocksPlatform::sortObjects($results, 'name');
        }

        return $results;
    }

    public function renderScript($context, $context_id)
    {
    }
}

abstract class Extension_CalendarDatasource extends DevblocksExtension
{
    const POINT = 'cerberusweb.calendar.datasource';

    public static $_registry = [];

    /**
     * @return DevblocksExtensionManifest[]|Extension_WorkspacePage[]
     */
    public static function getAll($as_instances = true)
    {
        $exts = DevblocksPlatform::getExtensions(self::POINT, $as_instances);

        // Sorting
        if ($as_instances) {
            DevblocksPlatform::sortObjects($exts, 'manifest->name');
        } else {
            DevblocksPlatform::sortObjects($exts, 'name');
        }

        return $exts;
    }

    public static function get($extension_id)
    {
        if (isset(self::$_registry[$extension_id])) {
            return self::$_registry[$extension_id];
        }

        if (null != ($extension = DevblocksPlatform::getExtension($extension_id, true))
            && $extension instanceof self) {
            self::$_registry[$extension->id] = $extension;

            return $extension;
        }
    }

    abstract public function renderConfig(Model_Calendar $calendar, $params, $series_prefix);

    abstract public function getData(Model_Calendar $calendar, array $params, $params_prefix, $date_range_from, $date_range_to);
}

abstract class Extension_WorkspacePage extends DevblocksExtension
{
    const POINT = 'cerberusweb.ui.workspace.page';

    public static $_registry = [];

    /**
     * @return DevblocksExtensionManifest[]|Extension_WorkspacePage[]
     */
    public static function getAll($as_instances = true)
    {
        $exts = DevblocksPlatform::getExtensions(self::POINT, $as_instances);

        // Sorting
        if ($as_instances) {
            DevblocksPlatform::sortObjects($exts, 'manifest->name');
        } else {
            DevblocksPlatform::sortObjects($exts, 'name');
        }

        return $exts;
    }

    public static function get($extension_id)
    {
        if (isset(self::$_registry[$extension_id])) {
            return self::$_registry[$extension_id];
        }

        if (null != ($extension = DevblocksPlatform::getExtension($extension_id, true))
            && $extension instanceof self) {
            self::$_registry[$extension->id] = $extension;

            return $extension;
        }
    }

    public function exportPageConfigJson(Model_WorkspacePage $page)
    {
        $json_array = [
            'page' => [
                'name'         => $page->name,
                'extension_id' => $page->extension_id,
            ],
        ];

        return json_encode($json_array);
    }

    public function importPageConfigJson($import_json, Model_WorkspacePage $page)
    {
        if (!is_array($import_json) || !isset($import_json['page'])) {
            return false;
        }

        return true;
    }

    abstract public function renderPage(Model_WorkspacePage $page);
}

abstract class Extension_WorkspaceTab extends DevblocksExtension
{
    const POINT = 'cerberusweb.ui.workspace.tab';

    public static $_registry = [];

    /**
     * @return DevblocksExtensionManifest[]|Extension_WorkspaceTab[]
     */
    public static function getAll($as_instances = true)
    {
        $exts = DevblocksPlatform::getExtensions(self::POINT, $as_instances);

        // Sorting
        if ($as_instances) {
            DevblocksPlatform::sortObjects($exts, 'manifest->name');
        } else {
            DevblocksPlatform::sortObjects($exts, 'name');
        }

        return $exts;
    }

    public static function get($extension_id)
    {
        if (isset(self::$_registry[$extension_id])) {
            return self::$_registry[$extension_id];
        }

        if (null != ($extension = DevblocksPlatform::getExtension($extension_id, true))
            && $extension instanceof self) {
            self::$_registry[$extension->id] = $extension;

            return $extension;
        }
    }

    abstract public function renderTab(Model_WorkspacePage $page, Model_WorkspaceTab $tab);

    public function exportTabConfigJson(Model_WorkspacePage $page, Model_WorkspaceTab $tab)
    {
    }

    public function importTabConfigJson($import_json, Model_WorkspaceTab $tab)
    {
    }

    public function renderTabConfig(Model_WorkspacePage $page, Model_WorkspaceTab $tab)
    {
    }

    public function saveTabConfig(Model_WorkspacePage $page, Model_WorkspaceTab $tab)
    {
    }
}

abstract class Extension_WorkspaceWidgetDatasource extends DevblocksExtension
{
    public static $_registry = [];

    public static function getAll($as_instances = false, $only_for_widget = null)
    {
        $extensions = DevblocksPlatform::getExtensions('cerberusweb.ui.workspace.widget.datasource', false);

        if (!empty($only_for_widget)) {
            $results = [];

            foreach ($extensions as $id => $ext) {
                if (in_array($only_for_widget, array_keys($ext->params['widgets'][0]))) {
                    $results[$id] = ($as_instances) ? $ext->createInstance() : $ext;
                }
            }

            $extensions = $results;
            unset($results);
        }

        if ($as_instances) {
            DevblocksPlatform::sortObjects($extensions, 'manifest->name');
        } else {
            DevblocksPlatform::sortObjects($extensions, 'name');
        }

        return $extensions;
    }

    public static function get($extension_id)
    {
        if (isset(self::$_registry[$extension_id])) {
            return self::$_registry[$extension_id];
        }

        if (null != ($extension = DevblocksPlatform::getExtension($extension_id, true))
            && $extension instanceof self) {
            self::$_registry[$extension->id] = $extension;

            return $extension;
        }
    }

    abstract public function renderConfig(Model_WorkspaceWidget $widget, $params = [], $params_prefix = null);

    abstract public function getData(Model_WorkspaceWidget $widget, array $params = [], $params_prefix = null);
}

interface ICerbWorkspaceWidget_ExportData
{
    public function exportData(Model_WorkspaceWidget $widget, $format = null);
}

abstract class Extension_WorkspaceWidget extends DevblocksExtension
{
    public static $_registry = [];

    public static function getAll($as_instances = false)
    {
        $extensions = DevblocksPlatform::getExtensions('cerberusweb.ui.workspace.widget', $as_instances);

        if ($as_instances) {
            DevblocksPlatform::sortObjects($extensions, 'manifest->name');
        } else {
            DevblocksPlatform::sortObjects($extensions, 'name');
        }

        return $extensions;
    }

    public static function get($extension_id)
    {
        if (isset(self::$_registry[$extension_id])) {
            return self::$_registry[$extension_id];
        }

        if (null != ($extension = DevblocksPlatform::getExtension($extension_id, true))
                && $extension instanceof self) {
            self::$_registry[$extension->id] = $extension;

            return $extension;
        }
    }

    public static function renderWidgetFromCache($widget, $autoload = true, $nocache = false)
    {
        // Polymorph
        if ($widget instanceof Model_WorkspaceWidget) {
            // Do nothing, it's what we want.
        } elseif (is_numeric($widget)) {
            $widget = DAO_WorkspaceWidget::get($widget);
        } else {
            $widget = null;
        }

        $cache = DevblocksPlatform::getCacheService();
        $is_cached = false;

        if ($widget && $widget instanceof Model_WorkspaceWidget) {
            $cache_key = sprintf('widget%d_render', $widget->id);

            // Fetch and cache
            if ($nocache || empty($widget->cache_ttl) || null === ($widget_contents = $cache->load($cache_key))) {
                if ($autoload) {
                    $tpl = DevblocksPlatform::getTemplateService();
                    $tpl->assign('widget', $widget);

                    if (false !== ($widget_contents = $tpl->fetch('devblocks:cerberusweb.core::internal/workspaces/widgets/render.tpl'))) {
                        $cache->save($widget_contents, $cache_key, null, $widget->cache_ttl);
                    }
                }
            } else {
                $is_cached = true;
            }

            if (isset($widget_contents)) {
                echo $widget_contents;
            }
        }

        return $is_cached;
    }

    abstract public function render(Model_WorkspaceWidget $widget);

    abstract public function renderConfig(Model_WorkspaceWidget $widget);

    abstract public function saveConfig(Model_WorkspaceWidget $widget);

    public static function getViewFromParams($widget, $params, $view_id)
    {
        if (!isset($params['worklist_model'])) {
            return false;
        }

        $view_model = $params['worklist_model'];

        if (false != ($view = C4_AbstractViewLoader::unserializeViewFromAbstractJson($view_model, $view_id))) {
            // Check for quick search
            @$mode = $params['search_mode'];
            @$q = $params['quick_search'];

            if ($mode == 'quick_search' && $q) {
                $view->addParamsWithQuickSearch($q, true);
            }

            $view->persist();

            return $view;
        }

        return false;
    }
}

abstract class Extension_LoginAuthenticator extends DevblocksExtension
{
    public static function getAll($as_instances = false)
    {
        $extensions = DevblocksPlatform::getExtensions('cerberusweb.login', $as_instances);

        // [TODO] Alphabetize

        return $extensions;
    }

    public static function get($extension_id, $as_instance = false)
    {
        $extensions = self::getAll(false);

        if (!isset($extensions[$extension_id])) {
            return;
        }

        $ext = $extensions[$extension_id];

        if ($as_instance) {
            return $ext->createInstance();
        } else {
            return $ext;
        }
    }

    public static function getByUri($uri, $as_instance = false)
    {
        $extensions = self::getAll(false);

        foreach ($extensions as $manifest) { /* @var $manifest DevblocksExtensionManifest */
            if ($manifest->params['uri'] == $uri) {
                return $as_instance ? $manifest->createInstance() : $manifest;
            }
        }
    }

    /**
     * draws HTML form of controls needed for login information.
     */
    public function render()
    {
    }

    public function renderWorkerPrefs($worker)
    {
    }

    public function saveWorkerPrefs($worker)
    {
    }

    public function resetCredentials($worker)
    {
    }

    /**
     * pull auth info out of $_POST, check it, return user_id or false.
     *
     * @return bool whether login succeeded
     */
    public function authenticate()
    {
        return false;
    }

    /**
     * release any resources tied up by the authenticate process, if necessary.
     */
    public function signoff()
    {
    }
}

abstract class CerberusCronPageExtension extends DevblocksExtension
{
    const PARAM_ENABLED = 'enabled';
    const PARAM_LOCKED = 'locked';
    const PARAM_DURATION = 'duration';
    const PARAM_TERM = 'term';
    const PARAM_LASTRUN = 'lastrun';

    /**
     * runs scheduled task.
     */
    abstract public function run();

    public function _run()
    {
        $duration = $this->getParam(self::PARAM_DURATION, 5);
        $term = $this->getParam(self::PARAM_TERM, 'm');
        $lastrun = $this->getParam(self::PARAM_LASTRUN, time());

        // [TODO] By setting the locks directly on these extensions, we're invalidating them during the same /cron
        //	and causing redundant retrievals of the params from the DB
        $this->setParam(self::PARAM_LOCKED, time());

        $this->run();

        $secs = self::getIntervalAsSeconds($duration, $term);
        $ran_at = time();

        if (!empty($secs)) {
            $gap = time() - $lastrun; // how long since we last ran
            $extra = $gap % $secs; // we waited too long to run by this many secs
            $ran_at = time() - $extra; // go back in time and lie
        }

        $this->setParam(self::PARAM_LASTRUN, $ran_at);
        $this->setParam(self::PARAM_LOCKED, 0);
    }

    /**
     * @param bool $is_ignoring_wait Ignore the wait time when deciding to run
     *
     * @return bool
     */
    public function isReadyToRun($is_ignoring_wait = false)
    {
        $locked = $this->getParam(self::PARAM_LOCKED, 0);
        $enabled = $this->getParam(self::PARAM_ENABLED, false);
        $duration = $this->getParam(self::PARAM_DURATION, 5);
        $term = $this->getParam(self::PARAM_TERM, 'm');
        $lastrun = $this->getParam(self::PARAM_LASTRUN, 0);

        // If we've been locked too long then unlock
        if ($locked && $locked < (time() - 10 * 60)) {
            $locked = 0;
        }

        // Make sure enough time has elapsed.
        $checkpoint = ($is_ignoring_wait)
            ? (0) // if we're ignoring wait times, be ready now
            : ($lastrun + self::getIntervalAsSeconds($duration, $term)) // otherwise test
;

        // Ready?
        return (!$locked && $enabled && time() >= $checkpoint) ? true : false;
    }

    public static function getIntervalAsSeconds($duration, $term)
    {
        $seconds = 0;

        if ($term == 'd') {
            $seconds = $duration * 24 * 60 * 60; // x hours * mins * secs
        } elseif ($term == 'h') {
            $seconds = $duration * 60 * 60; // x * mins * secs
        } else {
            $seconds = $duration * 60; // x * secs
        }

        return $seconds;
    }

    public function configure($instance)
    {
    }

    public function saveConfigurationAction()
    {
    }
}

abstract class Extension_UsermeetTool extends DevblocksExtension implements DevblocksHttpRequestHandler
{
    private $portal = '';

    /*
     * Site Key
     * Site Name
     * Site URL
     */

    /**
     * @param DevblocksHttpRequest
     *
     * @return DevblocksHttpResponse
     */
    public function handleRequest(DevblocksHttpRequest $request)
    {
        $path = $request->path;

        @$a = DevblocksPlatform::importGPC($_REQUEST['a'], 'string');

        if (empty($a)) {
            @$action = array_shift($path).'Action';
        } else {
            @$action = $a.'Action';
        }

        switch ($action) {
            case null:
                // [TODO] Index/page render
                break;
//
            default:
                // Default action, call arg as a method suffixed with Action
                if (method_exists($this, $action)) {
                    call_user_func([&$this, $action]); // [TODO] Pass HttpRequest as arg?
                }
                break;
        }
    }

    public function writeResponse(DevblocksHttpResponse $response)
    {
    }

    /**
     * @param Model_CommunityTool $instance
     */
    public function configure(Model_CommunityTool $instance)
    {
    }

    public function saveConfiguration(Model_CommunityTool $instance)
    {
    }
}
