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
 
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once DOKU_PLUGIN.'syntax.php';
/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_imagereference_imgcaption extends DokuWiki_Syntax_Plugin {
     
    
    var $_figure_name_array = array("");
    var $_figure_map = array();
    
    
    function getInfo(){
        return array( 
            'author' => 'Martin Heinemann',
            'email'  => 'info@martinheinemann.net',
            'date'   => '2012-08-21',
            'name'   => 'imagereference',
            'desc'   => 'Create image references like latex is doing with figures',
            'url'    => 'http://wiki.splitbrain.org/wiki:plugins',
        );
    }
 
   
   function getType(){ return 'protected';}
    function getAllowedTypes() { return array('container','substition','protected','disabled','formatting','paragraphs'); }
    function getPType(){ return 'block';}

    // must return a number lower than returned by native 'code' mode (200)
    function getSort(){ return 196; }

    // override default accepts() method to allow nesting 
    // - ie, to get the plugin accepts its own entry syntax
    function accepts($mode) {
        if ($mode == substr(get_class($this), 7)) return true;

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
        $this->Lexer->addEntryPattern('<imgcaption\s[^\r\n\|]*?>(?=.*?</imgcaption.*?>)',$mode,'plugin_imagereference_imgcaption');
        $this->Lexer->addEntryPattern('<imgcaption\s[^\r\n\|]*?\|(?=[^\r\n]*>.*?</imgcaption.*>)',$mode,'plugin_imagereference_imgcaption');
    }

    function postConnect() {
        $this->Lexer->addExitPattern('</imgcaption>', 'plugin_imagereference_imgcaption');
    }


    function handle($match, $state, $pos, &$handler){

        switch ($state) {
            // entering <imgcaption> tag
            case DOKU_LEXER_ENTER : {
                $refLabel = trim(substr($match, 11, -1));
                // parse the input for reference-name and orientation
                $parsedInput = $this->_parseParam($refLabel);  // image1,left

                //array_push($this->_figure_name_array, $parsedInput[0]);

                //$this->_figure_map[$parsedInput[0]] = "";

              return array('caption_open', $parsedInput);  // image anchor label
             }
            // the content <imgcaption left>content
            case DOKU_LEXER_UNMATCHED : {
                //$parsed = $this->splitImgcaptionTagFromContent($match);
                //$this->_figure_map[end($this->_figure_name_array)] = $this->_imgend($parsed[0]);

              return array('data', '');
            }
            // the closing end tag
            case DOKU_LEXER_EXIT :
                return array('caption_close', $this->_figure_map[end($this->_figure_name_array)]);
            // don't know what this is for
            case DOKU_LEXER_MATCHED :
                return array('data', "----".$match."------");
        }
        
        return array();
    }
 
    function render($mode, &$renderer, $indata) {

        list($case, $data) = $indata;
        if ($mode == 'metadata') {
            // build up the image index for this page
            // to prepare the information for xhtml rendering
            switch ($case) {
                case 'caption_open' : {
                    // these are the main information about an imgcaption tag
                    // data is the parsedInput array from handle method
                    // just store the imagref name in the metadata
                    $imagerefname = $data[0];
                    $metadata = p_get_metadata($ID, 'imgreflist', METADATA_RENDER_USING_CACHE);

                    if (is_null($metadata) || !is_array($metadata)) {
                        $metadata = array("");
                    }
                    array_push($metadata, $imagerefname);
                    p_set_metadata($ID, $metadata, false, true);
                }
            }
        }



        if($mode == 'xhtml'){
            switch ($case) {
               case 'caption_open' :  $renderer->doc .= $this->_imgstart($data); break;
               case 'caption_close' :  {
               // -------------------------------------------------------
               list($name, $number, $caption) = $data;
               $layout = "<div class=\"undercaption\">".$this->getLang('fig').$number.": 
                    <a name=\"".$name."\">".$caption."</a><a href=\" \"><span></span></a>
                    </div></div>";
               $renderer->doc .= $layout; break;
               }
                // -------------------------------------------------------    
                // data is mostly empty!!!
            case 'data' : $renderer->doc .= $data; break; 
            }
            // store the image refences as metadata to expose them to the
            // imgref renderer
            $tmp = $renderer->meta['imagreference'];
            if (!is_null($tmp) && is_array($tmp)) {
                $renderer->meta['imagereferences'] = array_merge($tmp, $this->_figure_name_array);
                
            } else {
                $renderer->meta['imagereferences'] = $this->_figure_name_array;
            }
            return true;
        }
        if($mode == 'latex') {
            // -----------------------------------------
            switch ($case) {
               /* case 'imgref' :  {
                   	$renderer->doc .= "\\ref{".$data."}"; break;
               } */ 
               case 'caption_open' :  {
                   	// --------------------------------------
                   	$orientation = "\\centering";
                   	switch($data[1]) {
                   		case 'left'  : $orientation = "\\left";break;
                   		case 'right' : $orientation = "\\right";break;
                   	}
                   	$renderer->doc .= "\\begin{figure}[H!]{".$orientation; break;
                   	// --------------------------------------
               }
               case 'caption_close' : {
                   	// -------------------------------------------------------
                   	list($name, $number, $caption) = $data;
                   	$layout = "\\caption{".$caption."}\\label{".$name."}\\end{figure}";
                   	$renderer->doc .= $layout; break;
               }

    		   case 'data' :  $renderer->doc .= trim($data); break;
            }
            
            return true;
            // -----------------------------------------
        }
        
        
        return false;
    }
    
    
    
    function _parseParam($str) {
        if ( $str == null  || count ( $str ) < 1 ) {
            return array();
        }
        $styles = array();

        // get the img ref name. Its the first word
        $parsed = explode(" ", $str, 2);
        $imgref = $parsed[0];


        $tokens = preg_split('/\s+/', $parsed[1], 9);                      // limit is defensive
          foreach ($tokens as $token) {
              // restrict token (class names) characters to prevent any malicious data
              if (preg_match('/[^A-Za-z0-9_-]/',$token)) continue;
                $styles['class'] = (isset($styles['class']) ? $styles['class'].' ' : '').$token;
          }
        // return imageref name , style
        // e.G.    image1,left
        return array($imgref, $styles['class']);
    }
    
    
    function _imgstart($str) {
        // ============================================ //
        if (!strlen($str)) return array();
        
    	$layout = "<div class=\"imgcaption";
    	//$layout = "<div><div class=\"imgcaption";
    	if ($str[1] != "")
    		$layout = $layout.$str[1];
    	$layout = $layout."\">";
    	
        return $layout;
        // ============================================ //
    }
    
    
    /**
     * 
     *
     * @param String $str the image caption
     * @return array(imagename, image number, image caption)
     */
    function _imgend($str) {
        $figureName = end($this->_figure_name_array);
        // get the position of the figure in the array
        $refNumber = array_search($figureName, $this->_figure_name_array);

        return array($figureName, $refNumber, $str);

        $layout = "<div class=\"undercaption\">".$this->getLang('fig').$refNumber.": 
        <a name=\"".end($this->_figure_name_array)."\">".$str."</a></div>";

        //$layout = "<div id=\"undercaption\">Fig. ".$refNumber.": 
        //<a name=\"".end($this->_figure_name_array)."\">".$str."</a></div></div></div>";

        return $layout;
    }
    /**
     * divides the image caption and the content between the tags
     *
     */
    
    function splitImgcaptionTagFromContent($str) {
        if (!strlen($str)) return "";
        // parse for '>' 
        $parsed = explode(">", $str, 2);
        
        return $parsed;
    }
    
   
}
 
//Setup VIM: ex: et ts=4 enc=utf-8 :
?>
