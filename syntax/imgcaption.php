<?php
/**
 * Plugin imagereference
 *
 * Syntax: <imgref linkname> - creates a figure link to an image
 *         <imgcaption linkname <orientation> | Image caption> Image/Table</imgcaption>
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Martin Heinemann <martinheinemann@tudor.lu>
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
class syntax_plugin_imagereference_imgcaption extends DokuWiki_Syntax_Plugin {

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
        return 196;
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
        /*$this->Lexer->addEntryPattern('<imgcaption\s[^\r\n\|]*?>(?=.*?</imgcaption.*?>)', $mode, 'plugin_imagereference_imgcaption');*/
        $this->Lexer->addEntryPattern('<imgcaption.*?>(?=.*?</imgcaption>)', $mode, 'plugin_imagereference_imgcaption');
        /*$this->Lexer->addEntryPattern('<imgcaption\s[^\r\n\|]*?\|(?=[^\r\n]*>.*?</imgcaption.*>)', $mode, 'plugin_imagereference_imgcaption');*/
    }

    function postConnect() {
        $this->Lexer->addExitPattern('</imgcaption>', 'plugin_imagereference_imgcaption');
    }

    function handle($match, $state, $pos, &$handler) {

        switch($state) {
            case DOKU_LEXER_ENTER :
                $refParam    = trim(substr($match, 11, -1));
                list($param, $caption) = $this->_parseParam($refParam);

                array_push($this->_figure_name_array, $param['imgref']);

                // get the position of the figure in the array
                $refNumber = count($this->_figure_name_array)-1;

                $this->_figure_map[$param['imgref']] = array(
                    'imgref' => $param['imgref'],
                    'caption' => $caption,
                    'refnumber' => $refNumber
                );

                return array('caption_open', $param); // image anchor label

            case DOKU_LEXER_UNMATCHED :
                // drop unmatched text inside imgcaption tag
                return array('data', '');
                // when normal text it's usefull, then use next lines instead
                //$handler->_addCall('cdata', array($match), $pos);
                //return false;

            case DOKU_LEXER_EXIT :
                return array('caption_close', $this->_figure_map[end($this->_figure_name_array)]);
        }

        return array();
    }

    function render($mode, &$renderer, $indata) {

        list($case, $data) = $indata;
        if($mode == 'xhtml') {
            switch($case) {
                case 'caption_open' :
                    $renderer->doc .= $this->_imgstart($data);
                    break;

                case 'caption_close' :
                    $renderer->doc .= $this->_imgend($data);
                    break;

                // $data is empty string
                case 'data' :
                    $renderer->doc .= $data;
                    break;
            }
            // store the image refences as metadata to expose them to the
            // imgref renderer
            $tmp = $renderer->meta['imagereferences'];
            if(!is_null($tmp) && is_array($tmp)) {
                $renderer->meta['imagereferences'] = array_merge($tmp, $this->_figure_name_array);
            } else {
                $renderer->meta['imagereferences'] = $this->_figure_name_array;
            }
            return true;
        }
        if($mode == 'latex') {
            switch($case) {
                case 'caption_open' :
                    $orientation = "\\centering";
                    switch($data['classes']) {
                        case 'left'  :
                            $orientation = "\\left";
                            break;
                        case 'right' :
                            $orientation = "\\right";
                            break;
                    }
                    $renderer->doc .= "\\begin{figure}[H!]{".$orientation;
                    break;

                case 'caption_close' :
                    $layout = "\\caption{".$data['caption']."}\\label{".$data['imgref']."}\\end{figure}";
                    $renderer->doc .= $layout;
                    break;

                case 'data' :
                    $renderer->doc .= trim($data);
                    break;
            }

            return true;
        }

        return false;
    }

    /**
     * Parse parameters part of <imgcaption imgref class1 class2|Caption>
     *
     * @param string $str space separated parameters e.g."imgref class1 class2"
     * @return array(string imgref, string classes)
     */
    function _parseParam($str) {
        if($str == null || count($str) < 1) {
            return array();
        }
        $classes = '';

        // get caption, second part
        $parsed = explode("|", $str, 2);
        $caption = $parsed[1];

        // get the img ref name. Its the first word
        $parsed = explode(" ", $parsed[0], 2);
        $imgref = $parsed[0];

        $tokens = preg_split('/\s+/', $parsed[1], 9); // limit is defensive
        foreach($tokens as $token) {
            // restrict token (class names) characters to prevent any malicious data
            if(preg_match('/[^A-Za-z0-9_-]/', $token)) continue;
            $token = trim($token);
            if($token == '') continue;
            $classes .= ' '.$token;
        }
        // return imageref name , style
        // e.G.    image1,left
        return array(
            array(
                'imgref'  => $imgref,
                'classes' => $classes
            ),
            $caption,
        );
    }

    /**
     * Create html of opening of caption wrapper
     *
     * @param array $data(imgref, classes)
     * @return string html start of caption wrapper
     */
    function _imgstart($data) {

        $layout = '<span class="imgcaption';
        if($data['classes'] != "") {
            $layout .= $data['classes'];
        }
        $layout .= '">';

        return $layout;
    }

    /**
     * Create html of closing of caption wrapper
     *
     * @param array($name, $number, $caption) caption data
     * @return string html caption wrapper
     */
    function _imgend($data) {
        return '<span class="undercaption">'
                    .$this->getLang('fig').' '.$data['refnumber'].':
                    <a name="'.cleanID($data['imgref']).'">'.hsc($data['caption']).'</a>
                    <a href=" "><span></span></a>
                </span></span>';
    }
}

//Setup VIM: ex: et ts=4 enc=utf-8 :