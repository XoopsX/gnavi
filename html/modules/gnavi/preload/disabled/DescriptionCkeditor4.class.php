<?php

$mydirname = basename(dirname(dirname(__FILE__)));

class gnavi_descriptionCkeditor4_base extends XCube_ActionFilter
{
	public function preBlockFilter() {
		$this->mRoot->mDelegateManager->add(ucfirst($this->mydirname).'.Submit.BuildEditorForm', array($this, 'makeTextarea'));
	}
	
	public function makeTextarea(&$desc_tarea, &$hidden_body_html , $value, $canuse_editor) {
		$ckUtilFile = XOOPS_ROOT_PATH . '/modules/ckeditor4/class/Ckeditor4Utiles.class.php';
		if (! file_exists($ckUtilFile)) return;
		
		require_once $ckUtilFile;
		
		$params['name'] = 'desc_text';
		$params['value'] = $value;
		$params['editor'] = $canuse_editor? 'html' : 'bbcode';
		
		$js = Ckeditor4_Utils::getJS($params);
		if ($js) {
		
			if (version_compare(LEGACY_BASE_VERSION, '2.2', '>=')) {
				// Add script into HEAD
				$root =& XCube_Root::getSingleton();
				$jQuery = $root->mContext->getAttribute('headerScript');
				$jQuery->addScript($js);
				$jQuery->addLibrary('/modules/ckeditor4/ckeditor/ckeditor.js');
				$addScript = '';
			} else {
				$xoopsURL = XOOPS_URL;
				$addScript = <<<EOD
<script type="text/javascript">
if (typeof jQuery != 'undefined') {
	jQuery(function($){
		$js
	});
}
</script>
<script type="text/javascript" src="{$xoopsURL}/modules/ckeditor4/ckeditor/ckeditor.js"></script>
EOD;
			}
			
			//
			// Build the object for output.
			//
			$html = '<textarea name="'.$params['name'].'" class="'.$params['class'].'" style="'.$params['style'].'" cols="'.$params['cols'].'" rows="'.$params['rows'].'" id="'.$params['id'].'">'.$params['value'].'</textarea>'.$addScript;
			
			$desc_tarea =  new XoopsFormLabel( _MD_GNAV_ITM_DESC , $html);
			$hidden_body_html = new XoopsFormHidden('body_html', $canuse_editor? '1' : '0');
		}
	}
}
eval('class '.$mydirname.'_descriptionCkeditor4 extends gnavi_descriptionCkeditor4_base {protected $mydirname=\''.$mydirname.'\';}');
