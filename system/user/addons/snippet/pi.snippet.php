<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Snippet {
	var $return_data = "";
    public function __construct()
    {
		$total      = ee()->TMPL->fetch_param('total',500);
		$word       = ee()->TMPL->fetch_param('word',true);
		$ellipsis   = ee()->TMPL->fetch_param('ellipsis','...');

		if(!is_numeric($total)){
			$total = 500;
        }

        $str = strip_tags(ee()->TMPL->tagdata);

        if(strlen($str) <= $total){
    		$this->return_data = $str;
        }else{
        	if($word === true){
        		$this->return_data = $this->truncate_str($str,$total).$ellipsis;
    		}else{
    			$this->return_data = substr($str,0,$total).$ellipsis;
    		}
        }
    }
   function truncate_str($str, $maxlen)
	{
	  $newstr = substr($str, 0, $maxlen);
	  $newstr = substr($newstr, 0, strrpos($newstr," "));
	  return $newstr;
	}
// ----------------------------------------
//  Plugin Usage
// ----------------------------------------
// This function describes how the plugin is used.
//  Make sure and use output buffering
static function usage()
{
ob_start();
?>
Wrap a block of test in the tag pair. The plugin will strip the tags and return a preview of the block with the
total number of characters as set by the 'total' parameter. If the original text was longer than the total ellipsis will
be added.

{exp:snippet total="100" word="true" ellipsis="..."}

The block of text that you want to snippet.

{/exp:snippet}

Parameters:

* {total} = default 500

* {word} = Tells the plugin if you want it to end the snippet on the last full word. Set to "true" by default.

* {ellipsis} = Set to '...' by default. You can pass any text that you want appended to the end of the snippet.

<?php
$buffer = ob_get_contents();

ob_end_clean();
return $buffer;
}
/* END */
}
