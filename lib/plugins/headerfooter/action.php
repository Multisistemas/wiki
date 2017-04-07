<?php
/**
 * DokuWiki Plugin headerfooter (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Li Zheng <lzpublic@qq.com>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

class action_plugin_headerfooter extends DokuWiki_Action_Plugin {
    public function register(Doku_Event_Handler $controller) {

       $controller->register_hook('PARSER_WIKITEXT_PREPROCESS', 'AFTER', $this, 'handle_parser_wikitext_preprocess');
   
    }
    public function handle_parser_wikitext_preprocess(Doku_Event &$event, $param) {
        global $INFO;
        if ($INFO['id'] != '') return; // 发现每页会执行两次，当id为空时是真正的文本，否则是菜单。
        $inf = pageinfo();
        $inf['namespace'] = urlencode(str_replace(array(' ', '%', '&'), '_', $inf['namespace']));
        $ns = str_replace(':', '/', $inf['namespace']) . '/';
        $base = str_replace('\\', '/', DOKU_INC) . 'data/pages/' . $ns; // 得到文件绝对路径
        file_put_contents('tt.txt','abc');
        if (file_exists($base . '_header.txt')){ // 存在头文件
            $header = file_get_contents($base . '_header.txt');
            if ($this->getConf('separation') == 'paragraph'){ // 如果使用段落来分割
                $header = rtrim($header, " \r\n\\") . "\n\n";
            }
            $event->data = $header . $event->data;
        }
        if (file_exists($base . '_footer.txt')){ // 存在尾文件
            $footer = file_get_contents($base . '_footer.txt');
            if ($this->getConf('separation') == 'paragraph'){ // 如果使用段落来分割
                $footer = "\n\n" . ltrim($footer, " \r\n\\");
            }
            $event->data .= $footer;
        }
    }
}
