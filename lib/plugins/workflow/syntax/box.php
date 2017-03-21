<?php
/**
 * DokuWiki Plugin workflow (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Ryan Boder <ryan.boder@suretycam.com>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

class syntax_plugin_workflow_box extends DokuWiki_Syntax_Plugin {
  
    function getType(){ return 'formatting';}
    function getAllowedTypes() { return array('container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs'); }
    function getPType(){ return 'block';}
    function getSort(){ return 197; }
    
    public function connectTo($mode) {
      $this->Lexer->addEntryPattern('<box.*?>(?=.*?</box>)', $mode, 'plugin_workflow_box');
    }
    
    public function postConnect() {
      $this->Lexer->addExitPattern('</box>', 'plugin_workflow_box');
    }

    public function handle($match, $state, $pos, &$handler){
      $data = array($state, $match);
      return $data;
    }
    
    public function render($mode, &$renderer, $indata) {
      if($mode != 'xhtml') return false;
      
      if (empty($indata)) return false;
      list($state, $data) = $indata;

      switch ($state) {
      case DOKU_LEXER_ENTER:
	$renderer->doc .= '<div class="box">';
	break;
      case DOKU_LEXER_UNMATCHED:
	$renderer->doc .= $data;
	break;
      case DOKU_LEXER_EXIT:
	$renderer->doc .= '</div><br/>';
	break;
      }
      
      return true;
    }
    
}
