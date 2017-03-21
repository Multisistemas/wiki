<?php
/**
 * DokuWiki Plugin workflow (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Ryan Boder <ryan.boder@suretycam.com>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

function startswith($haystack, $needle) {
  return $needle === "" || strpos($haystack, $needle) === 0;
}

function wf_render_link($ns, $page, $onclick, $text) {
  $exists = false;
  resolve_pageid('wf', $page, $exists);
  $exists = $exists ? '1' : '2';
  return "<a class=\"wikilink$exists\" title=\"$page\" href=\"doku.php?id=$page\" onclick=\"$onclick\">$text</a>";
}

function wf_render_criteria($criteria) {
  $data = '';
  foreach ($criteria as $name => $value) {
    $data .= $name . '=' . $value . ':';
  }
  return trim($data, ':');
}

class syntax_plugin_workflow_decision extends DokuWiki_Syntax_Plugin {

    public function getType() { return 'formatting'; }
    public function getPType() { return 'block'; }
    public function getSort() { return 196; }
    
    public function connectTo($mode) {
      $this->Lexer->addEntryPattern('<decision.*?>(?=.*?</decision>)',$mode,'plugin_workflow_decision');
    }
    
    public function postConnect() {
      $this->Lexer->addExitPattern('</decision>','plugin_workflow_decision');
    }

    public function handle($match, $state, $pos, &$handler){
      $data = array($state, $match);
      
      return $data;
    }
    
    public function render($mode, &$renderer, $indata) {
      if($mode != 'xhtml') return false;
      
      if (empty($indata)) return false;
      list($state, $data) = $indata;

      global $INFO;
      include DOKU_INC . 'lib/plugins/workflow/wfversion.php';
      $wfns = trim($this->getConf('WorkflowNamespace'));
      
      switch ($state) {
      case DOKU_LEXER_ENTER:
	// get the attributes
	$numattrs = preg_match_all('/([\w\d]+)="([\w\d, \/_-]+)"/', $data, $matches);
	$attrs = array();
	for ($i = 0; $i < $numattrs; $i++) {
	  $attrs[$matches[1][$i]] = $matches[2][$i];
	}
	$renderer->doc .= '<script>';
	$renderer->doc .= 'var wfnamespace = ' . json_encode($wfns) . ';';
	$renderer->doc .= 'var wfpageid = ' . json_encode(substr($INFO['id'], strlen($wfns) + 1)) . ';';
	$renderer->doc .= 'var wfstatedefs = ' . json_encode($attrs) . ';';
	$renderer->doc .= '</script>';
	$renderer->doc .= '<div class="wfsearch"><a href="#wfsearch" onclick="wfFocusSearch();" title="Workflow Decision"><div class="wfsearchx"></div></a></div>';
	$renderer->doc .= '<input type="text" id="wfsearch" placeholder="Type to search">';
	$renderer->doc .= ' <span style="color:#919191;">workflow version ' . $workflow_plugin_version . '</span><br/>';
	$renderer->doc .= '<table id="wftable">';
	break;
      case DOKU_LEXER_UNMATCHED:
	// foreach decision link
	$lines = explode("\n  *", $data);
	foreach ($lines as $rawline) {
	  // parse the line
	  $line = trim($rawline);
	  if ($line == '') continue;
	  $tokens = preg_split('/(\|| \+| -| \?)/', $line, NULL, PREG_SPLIT_DELIM_CAPTURE);

	  // get the page name and link text
	  $page = trim($tokens[0]);
	  $text = in_array('|', $tokens) ? trim($tokens[2]) : $page;
	  $options_index = in_array('|', $tokens) ? 3 : 1; // the index of the first option
	  
	  // process new workflow state and criteria for the link
	  $criteria = array();
	  $onclick = '';
	  for ($i = $options_index; $i+1 < count($tokens); $i += 2) {
	    list($name, $value) = array_map('trim', explode('=', $tokens[$i+1]));
	    if ($tokens[$i] == ' +' && $name != NULL && $value != NULL) {
	      $onclick .= 'wfRemember(\'' . $name . '\',\'' . $value . '\');';
	    } else if ($tokens[$i] == ' -' && $name != NULL) {
	      $onclick .= 'wfForget(\'' . $name . '\');';
	    } else if ($tokens[$i] == ' ?' && $name != NULL && $value != NULL) {
	      $criteria[$name] = $value;
	    }
	  }
	  
	  $renderer->doc .= "<tr data-criteria=\"" . wf_render_criteria($criteria) . "\"><td>" . wf_render_link($wfns, $page, $onclick, $text) . "</td></tr>";
	}
	
	break;
      case DOKU_LEXER_EXIT:
	$renderer->doc .= '</table>';
	break;
      default:
	$renderer->doc .= '!!!Renderer default case!!!';
      }
      
      return true;
    }
    
}
