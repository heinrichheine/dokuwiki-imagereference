<?php
    /**
     * Plugin imagereference
     *
     * Syntax: <imgref linkname> - creates a figure link to an image
     *         <imgcaption linkname <orientation> | Image caption> Image/Table</imgcaption>
     *
     * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
     * @author     Martin Heinemann <info@martinheinemann.net>
     * @author     Gerrit Uitslag <klapinklapin@gmail.com>
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

    /**
     * @return string Syntax type
     */
    function getType() {
        return 'substition';
    }
    /**
     * @return string Paragraph type
     */
    function getPType() {
        return 'normal';
    }
    /**
     * @return int Sort order
     */
    function getSort() {
        return 197;
    }

    /**
     * Connect lookup pattern to lexer.
     *
     * @param string $mode Parser mode
     */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('<imgref.*?>', $mode, 'plugin_imagereference_imgref');
    }
    /**
     * Handle matches of the imgref syntax
     *
     * @param string $match The match of the syntax
     * @param int    $state The state of the handler
     * @param int    $pos The position in the document
     * @param Doku_Handler    $handler The handler
     * @return array Data for the renderer
     */
    function handle($match, $state, $pos, &$handler) {
        $ref = trim(substr($match, 8, -1));
        if($ref) {
            return array('imgrefname' => $ref);
        }
        return false;
    }
    /**
     * Render xhtml output or metadata
     *
     * @param string         $mode      Renderer mode (supported modes: xhtml and metadata)
     * @param Doku_Renderer  $renderer  The renderer
     * @param array          $data      The data from the handler function
     * @return bool If rendering was successful.
     */
    function render($mode, &$renderer, $data) {
        global $ID;
        if($data === false) return false;

        switch($mode) {
            case 'xhtml' :
                /** @var Doku_Renderer_xhtml $renderer */

                //determine referencenumber
                $imgrefs   = p_get_metadata($ID, 'imagereferences');
                $refNumber = array_search($data['imgrefname'], $imgrefs);

                if(!$refNumber) {
                    $refNumber = "##";
                }

                $renderer->doc .= '<a href="#'.cleanID($data['imgrefname']).'">'.$this->getLang('figure').' '.$refNumber.'</a>';
                return true;

            case 'latex' :
                $renderer->doc .= "\\ref{".$data['imgrefname']."}";
                return true;
        }
        return false;
    }
}
//Setup VIM: ex: et ts=4 enc=utf-8 :