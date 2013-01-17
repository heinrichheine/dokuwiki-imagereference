<?php
/**
 * Plugin imagereference
 *
 * Syntax: <imgref linkname> - creates a figure link to an image
 *         <imgcaption linkname <orientation> | Image caption> Image/Table</imgcaption>
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Martin Heinemann <info@martinheinemann.net>
 */

if(!defined('DOKU_INC')) die();

if(!defined('DOKU_LF')) define('DOKU_LF', "\n");
if(!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC.'lib/plugins/');

require_once DOKU_PLUGIN.'syntax.php';
/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_imagereference_imgref extends DokuWiki_Syntax_Plugin {

    var $_figure_name_array = array("");
    var $_figure_map = array();

    function getType() {
        return 'protected';
    }

    function getAllowedTypes() {
        return array('container', 'substition', 'protected', 'disabled', 'formatting', 'paragraphs');
    }

    function getPType() {
        return 'normal';
    }

    // must return a number lower than returned by native 'code' mode (200)
    function getSort() {
        return 197;
    }

    // override default accepts() method to allow nesting 
    // - ie, to get the plugin accepts its own entry syntax
    function accepts($mode) {
        if($mode == substr(get_class($this), 7)) return true;

        return parent::accepts($mode);
    }

    /**
     * Connect lookup pattern to lexer.
     *
     * @param $aMode String The desired rendermode.
     * @return none
     * @public
     * @see render()
     */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('<imgref\s[^\r\n]*?>', $mode, 'plugin_imagereference_imgref');
    }

    function postConnect() {
    }

    function handle($match, $state, $pos, &$handler) {

        switch($state) {
            case DOKU_LEXER_SPECIAL :
                $ref = substr($match, 8, -1);
                return array('imgref', $ref);
        }

        return array();
    }

    function render($mode, &$renderer, $indata) {
        list($case, $data) = $indata;
        if($mode == 'xhtml') {
            switch($case) {
                case 'imgref' :
                    $_figure_name_array = $renderer->meta['imagereferences'];
                    if(is_array($_figure_name_array)) {
                        $refNumber = array_search($data, $_figure_name_array);
                        if($refNumber == null || $refNumber == "")
                            $refNumber = "##";
                        $str = "<a href=\"#".cleanID($data)."\">".$this->getLang('figure')." ".$refNumber." </a>";
                        $renderer->doc .= $str;

                    } else {
                        $warning = cleanID($data)."<sup style=\"color:#FF0000;\">".$this->getLang('error_imgrefbeforeimgcaption')."</sup>";
                        $renderer->doc .= $warning;
                    }
                    break;
            }

            return true;
        }
        if($mode == 'latex') {
            switch($case) {
                case 'imgref' :
                    $renderer->doc .= "\\ref{".$data."}";
                    break;
            }

            return true;
        }
        return false;
    }

}

//Setup VIM: ex: et ts=4 enc=utf-8 :