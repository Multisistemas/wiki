<?php
/**
 * Table editing plugin. Allows to manipulate with table columns order.
 *
 * @author Bohumir Zamecnik <bohumir@zamecnik.org>
 */
 
if (!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
require_once (DOKU_PLUGIN . 'action.php');
 
class action_plugin_tableedit extends DokuWiki_Action_Plugin {
 
    /**
     * Return some info
     */
    function getInfo() {
        return array (
            'author' => 'Bohumir Zamecnik',
            'email' => 'bohumir@zamecnik.org',
            'date' => '2008-11-23',
            'name' => 'Table editing plugin',
            'desc' => 'Allows to manipulate with table columns order',
            'url' => 'http://zamecnik.org/',
        );
    }
 
    /**
     * Register the eventhandlers
     */
    function register(&$controller) {
        $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE',  $this, '_hookjs');
        $controller->register_hook('TOOLBAR_DEFINE', 'AFTER', $this, 'insert_button', array ());
    }
 
    /**
     * Hook js script into page headers.
     */
    function _hookjs(&$event, $param) {
        $event->data["script"][] = array (
          "type" => "text/javascript",
          "charset" => "utf-8",
          "_data" => "",
          "src" => DOKU_BASE."lib/plugins/tableedit/tableedit.js"
        );
    }

 
    /**
     * Inserts the toolbar button
     */
    function insert_button(& $event, $param) {
        $event->data[] = array (
            'type' => 'tableedit',
            'title' => $this->getLang('qb_tableedit'),
            'icon' => '../../plugins/tableedit/tableedit.png',
            'prompt' => $this->getLang('new_columns_order'),
        );
    }
}
