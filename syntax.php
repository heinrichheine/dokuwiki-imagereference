<?php
/**
 * Plugin imagereference
 *
 * Syntax: <imgref linkname> - creates a figure link to an image
 *         <imgcaption linkname <orientation> | Image caption> Image/Table</imgcaption>
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Martin Heinemann <martin.heinemann@tudor.lu>
 */
 
if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');
 
/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_imagereference extends DokuWiki_Syntax_Plugin {
 	
	
	var $_figure_name_array = array("");
	var $_figure_map = array();
	
	
   /**
    * Get an associative array with plugin info.
    *
    * <p>
    * The returned array holds the following fields:
    * <dl>
    * <dt>author</dt><dd>Author of the plugin</dd>
    * <dt>email</dt><dd>Email address to contact the author</dd>
    * <dt>date</dt><dd>Last modified date of the plugin in
    * <tt>YYYY-MM-DD</tt> format</dd>
    * <dt>name</dt><dd>Name of the plugin</dd>
    * <dt>desc</dt><dd>Short description of the plugin (Text only)</dd>
    * <dt>url</dt><dd>Website with more information on the plugin
    * (eg. syntax description)</dd>
    * </dl>
    * @param none
    * @return Array Information about this plugin class.
    * @public
    * @static
    */
    function getInfo(){
        return array( 
            'author' => 'Martin Heinemann',
            'email'  => 'martin.heinemann@tudor.lu',
            'date'   => '2008-05-30',
            'name'   => 'imagereference',
            'desc'   => 'Create image references like latex is doing with figures',
            'url'    => 'http://wiki.splitbrain.org/wiki:plugins',
        );
    }
 
   
   function getType(){ return 'protected';}
    function getAllowedTypes() { return array('container','substition','protected','disabled','formatting','paragraphs'); }
    function getPType(){ return 'normal';}

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
      $this->Lexer->addSpecialPattern('<imgref\s[^\r\n]*?>',$mode, 'plugin_imagereference');
	  $this->Lexer->addEntryPattern('<imgcaption\s[^\r\n\|]*?>(?=.*?</imgcaption.*?>)',$mode,'plugin_imagereference');
	  $this->Lexer->addEntryPattern('<imgcaption\s[^\r\n\|]*?\|(?=[^\r\n]*>.*?</imgcaption.*>)',$mode,'plugin_imagereference');
    }
 
    function postConnect() {
      $this->Lexer->addExitPattern('</imgcaption>', 'plugin_imagereference');
    }
 	
 
    function handle($match, $state, $pos, &$handler){
    	
        switch ($state) {
           case DOKU_LEXER_ENTER : {
           	$refLabel = trim(substr($match, 11, -1));
           	$parsedInput = $this->_parseParam($refLabel);
           	
           	//$data = $this->_imgstart($parsedInput);
           	// store the figure name from imgcaption
           	array_push($this->_figure_name_array, $parsedInput[0]);
           	
           	$this->_figure_map[$parsedInput[0]] = "";
           	
        	return array('caption_open', $parsedInput);  // image anchor label
           }
          case DOKU_LEXER_UNMATCHED : {
    		$parsed = $this->_parseContent($match);      	
          	$this->_figure_map[end($this->_figure_name_array)] = $this->_imgend($parsed[0]);
          	
        	return array('data', '');
          }
           
          case DOKU_LEXER_EXIT :
        	return array('caption_close', $this->_figure_map[end($this->_figure_name_array)]);
          case DOKU_LEXER_MATCHED :
        	return array('data', "----".$match."------");
          case DOKU_LEXER_SPECIAL : {
                $ref = substr($match, 8, -1);
                return array('imgref', $ref);
          }
        }
        
        return array();
    }
 
    function render($mode, &$renderer, $indata) {
	
        list($case, $data) = $indata;
        if($mode == 'xhtml'){
            switch ($case) {
               case 'imgref' :  {
	               	$refNumber = array_search($data, $this->_figure_name_array);
	               	if ($refNumber == null || $refNumber == "")
	               		$refNumber = "##";
	               	$str = "<a href=\"#".$data."\">".$this->getLang('figure').$refNumber." </a>";
	               	$renderer->doc .= $str; break;
//	               	 $renderer->_xmlEntities($str);break;
               }
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
            
            return true;
        }
        if($mode == 'latex') {
        	// -----------------------------------------
        	switch ($case) {
               case 'imgref' :  {
	               	/* --------------------------------------- */
	               	$renderer->doc .= "\\ref{".$data."}"; break;
	               	/* --------------------------------------- */
               }
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
      //if (!strlen($str)) return array();

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
    	// ===================================================== //
    	$figureName = end($this->_figure_name_array);
    	// get the position of the figure in the array
		$refNumber = array_search($figureName, $this->_figure_name_array);
		
		return array($figureName, $refNumber, $str);
		
		$layout = "<div class=\"undercaption\">".$this->getLang('fig').$refNumber.": 
		<a name=\"".end($this->_figure_name_array)."\">".$str."</a></div>";
		
		//$layout = "<div id=\"undercaption\">Fig. ".$refNumber.": 
		//<a name=\"".end($this->_figure_name_array)."\">".$str."</a></div></div></div>";
		
		return $layout;
    	// ===================================================== 
    }
    /**
     * divides the image caption and the content between the tags
     *
     */
    
    function _parseContent($str) {
    	// ======================================================
    	if (!strlen($str)) return "";
    	// parse for '>' 
    	$parsed = explode(">", $str, 2);
    	
    	return $parsed;
    	// ======================================================
    }
    
   
}
 
//Setup VIM: ex: et ts=4 enc=utf-8 :
?>
