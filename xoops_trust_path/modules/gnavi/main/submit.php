<?php
// ------------------------------------------------------------------------- //
//                      GNAVI - XOOPS area guide +                           //
//                        <http://xoops.iko-ze.net/>                         //
//                 based on  myAlbum-P - XOOPS photo album                   //
//                        <http://www.peak.ne.jp/>                           //
// ------------------------------------------------------------------------- //

include dirname(dirname(__FILE__)).'/include/common_prepend.inc.php' ;
require_once dirname(dirname(__FILE__)).'/class/myuploader.php' ;
require_once dirname(dirname(__FILE__)).'/class/gnavi.textsanitizer.php' ;
require_once dirname(dirname(__FILE__)).'/class/gtickets.php' ;
$myts = GNaviTextSanitizer::sGetInstance() ;
$cattree = new XoopsTree( $table_cat , 'cid' , 'pid' ) ;
$exif_init = array_pad(array(), 3, array());
$exif_init_s = serialize($exif_init);

// check folders
gnavi_check_folders();

// check Categories exist
$result = $xoopsDB->query( "SELECT count(cid) as count FROM $table_cat" ) ;
list( $count ) = $xoopsDB->fetchRow( $result ) ;
if( $count < 1 ) {
	redirect_header( XOOPS_URL."/modules/$mydirname/" , 2 , _MD_GNAV_MSG_MUSTADDCATFIRST ) ;
	exit ;
}

// GPS Preview
if (! empty($_POST['gps_preview'])) {
	$_POST['preview'] = $_POST['gps_preview'];
	$_POST['pchange'] = '0';
}

// check lid exists
if( !empty( $_POST['submit'] ) || !empty( $_POST['preview'] ) || !empty( $_POST['conf_delete'] )) {
	$lid   = empty( $_POST['lid'] ) ? 0 : intval( $_POST['lid'] ) ;
}else{
	$lid   = empty( $_GET['lid'] ) ? 0 : intval( @$_GET['lid'] ) ;
}

if($lid > 0){
	$whr_status = $isadmin ? '' : 'AND status>0' ;
	$result = $xoopsDB->query( "SELECT count(lid) AS count FROM $table_photos WHERE lid=$lid $whr_status" ) ;
	list( $count ) = $xoopsDB->fetchRow( $result ) ;
	$mode = $count > 0 ? G_UPDATE : G_INSERT ;
}else{
	$mode = G_INSERT ;
}

// check parmition
if($mode==G_INSERT){

	$submitter = $my_uid ;
	if( ! ( $global_perms & GNAV_GPERM_INSERTABLE ) ) {
		redirect_header( XOOPS_URL."/user.php" , 2 , _MD_GNAV_MSG_MUSTREGFIRST ) ;
		exit ;
	}

}else{
	//check lid owner
	$result = $xoopsDB->query( "SELECT submitter FROM $table_photos WHERE lid=$lid" ) ;
	list( $submitter ) = $xoopsDB->fetchRow( $result ) ;

	if( $global_perms & GNAV_GPERM_EDITABLE ) {
		if( $my_uid != $submitter && ! $isadmin ) {
			redirect_header( $mod_url.'/' , 3 , _NOPERM ) ;
			exit ;
		}
	} else {
		redirect_header( $mod_url.'/' , 3 , _NOPERM ) ;
		exit ;
	}
}

// Do Delete
if( ! empty( $_POST['do_delete'] ) ) {

	if( ! ( $global_perms & GNAV_GPERM_DELETABLE ) ) {
		redirect_header( $mod_url.'/' , 3 , _NOPERM ) ;
		exit ;
	}

	// Ticket Check
	if ( ! $xoopsGTicket->check() ) {
		redirect_header(XOOPS_URL.'/',3,$xoopsGTicket->getErrors());
	}

	// get and check lid is valid
	if( $lid < 1 ) die( "Invalid photo id." ) ;

	$whr = "lid=$lid" ;
	if( ! $isadmin ) $whr .= " AND submitter=$my_uid" ;

	gnavi_delete_photos( $whr ) ;

	redirect_header( $mod_url.'/' , 3 , _MD_GNAV_SMT_DELETINGITEM ) ;
	exit ;
}


// POST variables
$p_lat  = empty( $_GET['lat']  ) ? 0 : floatval ( @$_GET['lat']  ) ;
$p_lng  = empty( $_GET['lng']  ) ? 0 : floatval ( @$_GET['lng']  ) ;
$p_zoom = empty( $_GET['z'] ) ? 0 : intval( @$_GET['z'] ) ;
$p_mtype = !in_array(@$_GET['mt'],$gnavi_maptypes) ? "" : $_GET['mt'] ;
$p_cid  = empty( $_GET['cid']  ) ? 0 : intval( @$_GET['cid']  ) ;

$p_set_latlng = empty( $_POST['set_latlng'] ) ? 1 : 0 ;
$preview_name = empty( $_POST['preview_name'] ) ? '' : @$_POST['preview_name'] ;
$preview_name1 = empty( $_POST['preview_name1'] ) ? '' : @$_POST['preview_name1'] ;
$preview_name2 = empty( $_POST['preview_name2'] ) ? '' : @$_POST['preview_name2'] ;
$del_photo = empty( $_POST['del_photo'] ) ? 0 : intval( @$_POST['del_photo'] ) ;
$del_photo1 = empty( $_POST['del_photo1'] ) ? 0 : intval( @$_POST['del_photo1'] ) ;
$del_photo2 = empty( $_POST['del_photo2'] ) ? 0 : intval( @$_POST['del_photo2'] ) ;
$p_valid  = empty( $_POST['valid'] ) ? 0 : intval( $_POST['valid'] );
$p_status = empty( $_POST['old_status'] ) ? 0 : intval( $_POST['old_status'] );
$pchange = false;

if( ! empty( $_POST['submit'] ) || ! empty( $_POST['preview'] )) {

	$title = $myts->stripSlashesGPC( $_POST["title"] ) ;
	$cid = empty( $_POST['cid'] ) ? 0 : intval( $_POST['cid'] ) ;
	$cid1 = empty( $_POST['cid1'] ) ? 0 : intval( $_POST['cid1'] ) ;
	$cid2 = empty( $_POST['cid2'] ) ? 0 : intval( $_POST['cid2'] ) ;
	$cid3 = empty( $_POST['cid3'] ) ? 0 : intval( $_POST['cid3'] ) ;
	$cid4 = empty( $_POST['cid4'] ) ? 0 : intval( $_POST['cid4'] ) ;
	$icd = empty( $_POST['icd'] ) ? 0 : intval( @$_POST['icd'] ) ;

	$desc_text = $myts->stripSlashesGPC( $_POST["desc_text"] ) ;
	$body_html = empty( $_POST['body_html'] ) || !($global_perms & GNAV_GPERM_WYSIWYG) ? 0 : intval( $_POST['body_html'] ) ;
	$arrow_html = $body_html ? 1 : 0 ;
	$arrow_br =  $body_html ? 0 : 1 ;

	$caption  = $myts->stripSlashesGPC( $_POST["caption" ] ) ;
	$caption1 = $myts->stripSlashesGPC( $_POST["caption1"] ) ;
	$caption2 = $myts->stripSlashesGPC( $_POST["caption2"] ) ;

	$url = $myts->stripSlashesGPC( $_POST["url"] ) ;
	$url= preg_match('/^(https?|ftp)(:\/\/[-_.!~*\'()a-zA-Z0-9;\/?:\@&=+\$,%#]+)$/', $url) ? $url :"";
	$rss = $myts->stripSlashesGPC( @$_POST["rss"] ) ;
	$rss= preg_match('/^(https?|ftp)(:\/\/[-_.!~*\'()a-zA-Z0-9;\/?:\@&=+\$,%#]+)$/', $rss) ? $rss :"";

	$tel = $myts->stripSlashesGPC( $_POST["tel"] ) ;
	$fax = $myts->stripSlashesGPC( $_POST["fax"] ) ;
	$zip = $myts->stripSlashesGPC( $_POST["zip"] ) ;
	$address = $myts->stripSlashesGPC( $_POST["address"] ) ;


	$lat = floatval($myts->stripSlashesGPC( @$_POST["lat"] )) ;
	$lng = floatval($myts->stripSlashesGPC( @$_POST["lng"] )) ;
	$zoom = intval($myts->stripSlashesGPC( @$_POST["z"] )) ;
	$mtype = !in_array($myts->stripSlashesGPC( @$_POST["mt"] ),$gnavi_maptypes) ? "" : $myts->stripSlashesGPC( @$_POST["mt"] ) ;	
	$pchange = (bool)@$_POST['pchange'];

	$addinfo = gnavi_addinfo_reg($myts->stripSlashesGPC( @$_POST["addinfo"] ));

	// ken add postername
	$poster_name = empty( $_POST['poster_name'] ) ? '' : $myts->stripSlashesGPC( @$_POST['poster_name'] );
	if( trim( $poster_name ) == "" ) {
		$poster_name = _GNAV_CAPTION_GUESTNAME;
		$submitter=0;
	}
	if (!gnavi_check_name_from_uid($submitter,$poster_name)){
		//if postername difference from uid then force guest witer 
		$submitter=0;
	}
	
	if (isset($_SESSION['GNAVI_PREVIEW_EXIF'])) {
		$exif = unserialize(base64_decode($_SESSION['GNAVI_PREVIEW_EXIF']));
	} else {
		$exif = $exif_init;
	}
}


// Do Modify
if( ! empty( $_POST['submit'] ) ) {
	
	unset($_SESSION['GNAVI_PREVIEW_EXIF']);
	
	// Ticket Check
	if ( ! $xoopsGTicket->check() ) {
		redirect_header(XOOPS_URL.'/',3,$xoopsGTicket->getErrors());
	}

	if(!$gnavi_usegooglemap || !$p_set_latlng){
		$lat = 0 ;
		$lng = 0 ;
		$zoom = 0 ;
		$mtype = '' ;
	}

	// Check if cid is valid
	if( $cid <= 0 ) {
		redirect_header( 'index.php?page=submit'.($lid ? '&lid='.$lid : '' ) , 2 , 'Category is not specified.' ) ;
		exit ;
	}

	//file uploads-------------------------------------------------------------------------------------------------

	if($mode==G_INSERT){

		$valid = ( $global_perms & GNAV_GPERM_SUPERINSERT ) ? 1 : 0 ;
		$p_ext=$p_ext1=$p_ext2='';

	}else{

		// status change
		if( $isadmin ) {
			$valid = empty( $_POST['valid'] ) ? 0 : intval( $_POST['valid'] ) ;
			if( $valid == 0 ){
				 $valid = 0 ;
			}else{
				if( empty( $_POST['old_status'] ) ) {
					$valid = 1 ;
				} else {
					$valid = 2 ;
				}
			}
		} else {
			$valid = 2 ;
		}

		$prs = $xoopsDB->query( "SELECT l.ext,l.ext1,l.ext2,e.exif FROM $table_photos l LEFT JOIN $table_exif e ON l.lid=e.lid WHERE l.lid=$lid") ;
		list($p_ext,$p_ext1,$p_ext2,$p_exif) = $xoopsDB->fetchRow( $prs ) ;
		$p_exif = @ unserialize($p_exif);
		if (! $p_exif) {
			$p_exif = $exif_init;
		}
		
		if($preview_name  && $preview_name ==$lid.".".$p_ext   ) {
			$preview_name='' ;
			$exif[0] = $p_exif[0];
		}
		if($preview_name1 && $preview_name1==$lid."_1.".$p_ext1) {
			$preview_name1='' ;
			$exif[1] = $p_exif[1];
		}
		if($preview_name2 && $preview_name2==$lid."_2.".$p_ext2) {
			$preview_name2='' ;
			$exif[2] = $p_exif[2];
		}

	}
	
	$exif = array_pad($exif, 3, array());
	
	$errmsg='';
	
	list($tmp_name ,$ext ,$errmsg,$exif[0]) = gnavi_submit_uploader(@$_POST["xoops_upload_file"][0] ,$del_photo ,$preview_name , 1, $errmsg, $exif[0]);
	list($tmp_name1,$ext1,$errmsg,$exif[1]) = gnavi_submit_uploader(@$_POST["xoops_upload_file"][1] ,$del_photo1,$preview_name1, 2, $errmsg, $exif[1]);
	list($tmp_name2,$ext2,$errmsg,$exif[2]) = gnavi_submit_uploader(@$_POST["xoops_upload_file"][2] ,$del_photo2,$preview_name2, 3, $errmsg, $exif[2]);

	if($mode == G_INSERT && $ext=='' && !$gnavi_allownoimage) {
		redirect_header( 'index.php?page=submit'.($lid ? '&lid='.$lid : '' ) , 2 , _MD_GNAV_MSG_NOIMAGESPECIFIED ) ;
		exit ;
	}
	if($errmsg) {
		if($tmp_name )@unlink($photos_dir/$tmp_name) ;
		if($tmp_name1)@unlink($photos_dir/$tmp_name1) ;
		if($tmp_name2)@unlink($photos_dir/$tmp_name2) ;
		redirect_header( 'index.php?page=submit'.($lid ? '&lid='.$lid : '' ) , 4 , $errmsg ) ;
		exit ;
	}

	if (@$gnavi_configs['gnavi_use_gps'] && $p_set_latlng && !$pchange) {
		foreach ($exif as $_check) {
			if (!empty($_check['GPS']) && isset($_check['GPS']['Lat'])) {
				$lat = $_check['GPS']['Lat'];
				$lng = $_check['GPS']['Lon'];
				break;
			}
		}
	}
	
	$lid = gnavi_update_item($mode,$lid,
						$title,$cid,$cid1,$cid2,$cid3,$cid4,
                       	$url,$tel,$fax,$zip,$address,$rss,$lat,$lng,$zoom,$mtype,$icd,
                       	$submitter,$poster_name,$valid,$exif ) ;

	//delete old files
	if( $p_ext){
		if($del_photo==1 || $ext){
				@unlink( "$photos_dir/$lid.$p_ext");
				@unlink( "$thumbs_dir/$lid.$p_ext");
				$p_ext='';
		}
	}
	if( $p_ext1){
		if($del_photo1==1 || $ext1){
				@unlink( $photos_dir."/".$lid."_1.".$p_ext1);
				$p_ext1='';
		}
	}
	if( $p_ext2){
		if($del_photo2==1 || $ext2){
				@unlink( $photos_dir."/".$lid."_2.".$p_ext2);
				$p_ext2='';
		}
	}

	if($ext){
		gnavi_modify_photo( "$photos_dir/$tmp_name" , "$photos_dir/$lid.$ext" ) ;
		if(in_array( strtolower( $ext ) , $gnavi_normal_exts )) {
			if( ! gnavi_create_thumb( "$photos_dir/$lid.$ext" , $lid , $ext ) ) {
				$xoopsDB->query( "DELETE FROM $table_photos WHERE lid=$lid" ) ;
				redirect_header( 'index.php?page=submit'.($lid ? '&lid='.$lid : '' ) , 2 , _MD_GNAV_MSG_FILEREADERROR ) ;
				exit ;
			}
		}
	}else{
		$ext=$p_ext;
	}
	if($ext1){
		gnavi_modify_photo( "$photos_dir/$tmp_name1" , $photos_dir."/".$lid."_1.".$ext1 ) ;
		if(in_array( strtolower( $ext1 ) , $gnavi_normal_exts )) {
			if( ! gnavi_create_thumb( $photos_dir."/".$lid."_1.".$ext1 , $lid."_1" , $ext1 ) ) {
				$xoopsDB->query( "DELETE FROM $table_photos WHERE lid=$lid" ) ;
				redirect_header( 'index.php?page=submit'.($lid ? '&lid='.$lid : '' ) , 2 , _MD_GNAV_MSG_FILEREADERROR ) ;
				exit ;
			}
		}
	}else{
		$ext1=$p_ext1;
	}
	if($ext2){
		gnavi_modify_photo( "$photos_dir/$tmp_name2" , $photos_dir."/".$lid."_2.".$ext2 ) ;
		if(in_array( strtolower( $ext2 ) , $gnavi_normal_exts )) {
			if( ! gnavi_create_thumb( $photos_dir."/".$lid."_2.".$ext2 , $lid."_2" , $ext2 ) ) {
				$xoopsDB->query( "DELETE FROM $table_photos WHERE lid=$lid" ) ;
				redirect_header( 'index.php?page=submit'.($lid ? '&lid='.$lid : '' ) , 2 , _MD_GNAV_MSG_FILEREADERROR ) ;
				exit ;
			}
		}
	}else{
		$ext2=$p_ext2;
	}

	//get size

	$resx=0;
	$resx1=0;
	$resx2=0;
	$resy=0;
	$resy1=0;
	$resy2=0;

	if($ext && in_array( strtolower( $ext ) , $gnavi_normal_exts )){
		$dim = GetImageSize( "$photos_dir/$lid.$ext" ) ;
		if( $dim ) {$resx=$dim[0];$resy=$dim[1];}
	}
	if($ext1 && in_array( strtolower( $ext1 ) , $gnavi_normal_exts )){
		$dim = GetImageSize( $photos_dir."/".$lid."_1.".$ext1 ) ;
		if( $dim ) {$resx1=$dim[0];$resy1=$dim[1];}
	}
	if($ext2 && in_array( strtolower( $ext2 ) , $gnavi_normal_exts )){
		$dim = GetImageSize( $photos_dir."/".$lid."_2.".$ext2 ) ;
		if( $dim ) {$resx2=$dim[0];$resy2=$dim[1];}
	}


	gnavi_update_desc($mode,$lid,$cid,$title,$submitter,$valid,
							$ext,$ext1,$ext2,$resx,$resy,$resx1,$resy1,$resx2,$resy2,
							$caption,$caption1,$caption2,
                            $desc_text,$arrow_html,$addinfo, $exif);

	$moreMsg = '' ;
	if (!$valid) {
		$redirect_uri = "index.php?cid=$cid" ;
		$moreMsg = '<br />' . _MD_GNAV_MSG_WAITAPPROVED;
	} else {
		$redirect_uri = "index.php?lid=$lid" ;
	}

	if( $mode == G_INSERT){
		gnavi_clear_tmp_files( $photos_dir ) ;
		redirect_header( $redirect_uri , 2 , _MD_GNAV_MSG_RECEIVED . $moreMsg ) ;
	}else{
		redirect_header( $redirect_uri , 2 , _MD_GNAV_MSG_DBUPDATED . $moreMsg ) ;
	}

	exit ;

}

// Confirm Delete
if( ! empty( $_POST['conf_delete'] ) ) {

	unset($_SESSION['GNAVI_PREVIEW_EXIF']);

	if( ! ( $global_perms & GNAV_GPERM_DELETABLE ) ) {
		redirect_header( $mod_url.'/' , 3 , _NOPERM ) ;
		exit ;
	}

	include( XOOPS_ROOT_PATH."/include/cp_functions.php" ) ;
	include(XOOPS_ROOT_PATH."/header.php");

	echo "<h2>"._MD_GNAV_SMT_DELETE."</h2><hr />";

	$xoops_module_header = $xoopsTpl->get_template_vars( "xoops_module_header" ) ."\n" ."<link rel='stylesheet' type='text/css' href='css/gnavi.css'/>";
	$xoopsTpl->assign('xoops_module_header',$xoops_module_header);

	$result = $xoopsDB->query( "SELECT l.lid, l.cid,l.cid1,l.cid2,l.cid3,l.cid4, l.title,l.caption,l.caption1,l.caption2, l.poster_name,l.icd,l.url,l.tel,l.fax,l.zip,l.address,l.rss,l.lat,l.lng,l.zoom,l.mtype, l.ext,l.ext1,l.ext2, l.res_x, l.res_y,l.res_x1, l.res_y1,l.res_x2, l.res_y2, l.status, l.date, l.hits, l.rating, l.votes, l.comments, l.submitter, t.description,t.arrowhtml,t.addinfo FROM $table_photos l LEFT JOIN $table_text t ON l.lid=t.lid WHERE l.lid=$lid" ) ;
	$photo = $xoopsDB->fetchArray( $result ) ;
	// Display
	$photo = gnavi_get_array_for_photo_assign( $photo ) ;
	$photo = gnavi_photo_assign($photo);
	$tpl = new XoopsTpl() ;
	$tpl->assign( $gnavi_assign_globals ) ;
	$tpl->assign( 'photo' , $photo ) ;

	$msg = "<form action='index.php?page=submit&amp;lid=$lid' method='post'>
			".$xoopsGTicket->getTicketHtml( __LINE__ )."
			<table><tr><td><div style='font-size:15px;font-weight:bold;'>"._MD_GNAV_SMT_ASKDELETE."</div></td><td align='left'><input type='submit' name='do_delete' value='"._YES."' />&nbsp;<input type='submit' name='cancel_delete' value="._NO." /></td></tr></table>
		</form>" ;


	echo $msg."<hr />";
	$tpl->display( "db:{$mydirname}_itemheader.html" ) ;
	echo "<hr />".$msg;

	gnavi_footer() ;
	include( XOOPS_ROOT_PATH . "/footer.php" ) ;
	exit ;
}



// Editing Display
include(XOOPS_ROOT_PATH."/header.php");
include_once( "../../class/xoopsformloader.php" ) ;
include_once( "../../include/xoopscodes.php" ) ;

echo "<h2>".($mode == G_INSERT ? _MD_GNAV_SMT_UPLOAD : _MD_GNAV_SMT_EDIT )."</h2><hr />";



// Preview
if(!empty( $_POST['preview'] ) || $mode==G_UPDATE) {

	if(!empty( $_POST['preview'] ) ) {

		if($mode==G_INSERT){
			$date=time();
			$hits=0;
			$status=1;
			if(!$gnavi_usegooglemap || !$p_set_latlng){
				$lat = 0 ;
				$lng = 0 ;
				$zoom = 0 ;
				$mtype = '' ;
			}
		}else{
			$result = $xoopsDB->query( "SELECT status,date,hits FROM $table_photos WHERE lid=$lid" ) ;
			list($status,$date,$hits) = $xoopsDB->fetchRow( $result ) ;
			$date = empty( $_POST['store_timestamp'] ) ? $date : time() ;
		}

		// Display Preview
		$photo = array(
			'cid' => $cid,
			'cid1' => $cid1,
			'cid2' => $cid2,
			'cid3' => $cid3,
			'cid4' => $cid4,
			'icd' => $icd,
			'submitter' => $submitter ,
			'title' => $myts->makeTboxData4Show( $title ) ,
			'caption' => $myts->makeTboxData4Show( $caption ) ,
			'caption1' => $myts->makeTboxData4Show( $caption1 ) ,
			'caption2' => $myts->makeTboxData4Show( $caption2 ) ,
			'description' => $myts->displayTarea( $desc_text , $arrow_html , 1 , 1 , 1 , $arrow_br  , 1) ,
			'addinfo' => gnavi_addinfo_array($addinfo,$myts) ,
			'submitter_name' => $myts->makeTboxData4Show( $poster_name ) ,
			'poster_name' =>  $myts->makeTboxData4Show( $poster_name ) ,
			'url' => $myts->makeTboxData4Show( $url ) ,
			'tel' => $myts->makeTboxData4Show( $tel ) ,
			'fax' => $myts->makeTboxData4Show( $fax ) ,
			'zip' => $myts->makeTboxData4Show( $zip ) ,
			'address' => $myts->makeTboxData4Show( $address ) , 
			'rss' => $myts->makeTboxData4Show( $rss ) , 
			'lat' =>  $lat ,
			'lng' =>  $lng ,
			'zoom' =>  $zoom,
			'mtype' =>  $mtype,
			'datetime' => formatTimestamp( $date , 'm' ) ,
			'hits' => $hits,
			'status' => $status,
			'is_newphoto' => ( $date > time() - 86400 * $gnavi_newdays && $status == 1 ) , 
			'is_updatedphoto' => ( $date > time() - 86400 * $gnavi_newdays && $status == 2 ) , 
			'is_popularphoto' => ( $hits >= $gnavi_popular )
		) ;

		$orgfile_name=$orgfile_name1=$orgfile_name2="";

		if($mode!=G_INSERT){
			$prs = $xoopsDB->query( "SELECT ext,ext1,ext2 FROM $table_photos WHERE lid=$lid") ;
			list($p_ext,$p_ext1,$p_ext2) = $xoopsDB->fetchRow( $prs ) ;
			if($p_ext ) $orgfile_name =$lid.".".$p_ext ;
			if($p_ext1) $orgfile_name1=$lid."_1.".$p_ext1 ;
			if($p_ext2) $orgfile_name2=$lid."_2.".$p_ext2 ;
		}

		//adump($_SESSION['GNAVI_PREVIEW_EXIF']);
		list($preview_name, $exif[0]) = gnavi_submit_uploader_pre(@$_POST['xoops_upload_file'][0],$preview_name ,$del_photo ,$orgfile_name ,$exif[0]);
		list($preview_name1,$exif[1]) = gnavi_submit_uploader_pre(@$_POST['xoops_upload_file'][1],$preview_name1,$del_photo1,$orgfile_name1,$exif[1]);
		list($preview_name2,$exif[2]) = gnavi_submit_uploader_pre(@$_POST['xoops_upload_file'][2],$preview_name2,$del_photo2,$orgfile_name2,$exif[2]);

		$_SESSION['GNAVI_PREVIEW_EXIF'] = base64_encode(serialize($exif));
		//adump($_SESSION['GNAVI_PREVIEW_EXIF']);
		if (@$gnavi_configs['gnavi_use_gps'] && $p_set_latlng && !$pchange) {
			foreach ($exif as $_check) {
				if (!empty($_check['GPS']) && isset($_check['GPS']['Lat'])) {
					$lat = $_check['GPS']['Lat'];
					$lng = $_check['GPS']['Lon'];
					break;
				}
			}
		}
		
		$photo = gnavi_get_img_attribs_for_preview($photo,$preview_name,$preview_name1,$preview_name2,$exif);

	}else{
		
		// Get the record
		$result = $xoopsDB->query( "SELECT l.lid, l.cid,l.cid1,l.cid2,l.cid3,l.cid4, l.title,l.caption,l.caption1,l.caption2, l.poster_name,l.icd,l.url,l.tel,l.fax,l.zip,l.address,l.rss,l.lat,l.lng,l.zoom,l.mtype, l.ext,l.ext1,l.ext2, l.res_x, l.res_y,l.res_x1, l.res_y1,l.res_x2, l.res_y2, l.status, l.date, l.hits, l.rating, l.votes, l.comments, l.submitter, e.exif, t.description,t.arrowhtml,t.addinfo FROM $table_photos l LEFT JOIN $table_text t ON l.lid=t.lid LEFT JOIN $table_exif e ON l.lid=e.lid WHERE l.lid=$lid" ) ;
		$photo = $xoopsDB->fetchArray( $result ) ;
		$photo = gnavi_get_array_for_photo_assign( $photo ) ;

		if($photo['ext' ]){
			$preview_name = $lid.".".$photo['ext'];
		}
		if($photo['ext1']){
				$preview_name1 = $lid."_1.".$photo['ext1'];
		}
		if($photo['ext2']){
			$preview_name2 = $lid."_2.".$photo['ext2'];
		}

	}

	$photo = gnavi_photo_assign($photo);
	$photo['mycat'] = gnavi_get_mycat($photo['cid'],$photo['cid1'],$photo['cid2'],$photo['cid3'],$photo['cid4']);
	$tpl = new XoopsTpl() ;
	$tpl->assign( $gnavi_assign_globals ) ;
	$tpl->assign( 'photo' , $photo ) ;
	$tpl->display( "db:{$mydirname}_itemheader.html" ) ;

	$imgsrc_photo  = $photo['imgsrc_photo'] ;
	$imgsrc_photo1 = $photo['imgsrc_photo1'] ;
	$imgsrc_photo2 = $photo['imgsrc_photo2'] ;

}


//make forms data
if(!empty( $_POST['preview'] )){

	$photo = array(
		'lid'	=> $lid ,
		'title'	=> $title ,
		'cid'	=> $cid ,
		'cid1'	=> $cid1,
		'cid2'	=> $cid2,
		'cid3' 	=> $cid3,
		'cid4' 	=> $cid4,
		'icd' 	=> $icd,
		'ext' 	=> $photo['ext' ] ,
		'ext1' 	=> $photo['ext1'] ,
		'ext2' 	=> $photo['ext2'] ,
		'caption' 	=> $caption ,
		'caption1' 	=> $caption1 ,
		'caption2' 	=> $caption2 ,
		'url' 	=> $url ,
		'tel' 	=> $tel ,
		'fax' 	=> $fax ,
		'zip' 	=> $zip ,
		'address' 	=> $address ,
		'rss' 	=> $rss ,
		'lat' 	=>$lat ,
		'lng' 	=>$lng ,
		'zoom' 	=>$zoom,
		'mtype' 	=>$mtype,
		'submitter' => $submitter ,
		'poster_name' 	=> $poster_name,
		'description' 	=> $desc_text ,
		'arrowhtml' 	=> ( !($global_perms & GNAV_GPERM_WYSIWYG) ? 0 : $body_html) ,
		'addinfo' 	=> $addinfo ,
		'status' 	=> $p_status ,
		'valid' 	=> $p_valid ,
		'imgsrc_photo' 	=> $imgsrc_photo ,
		'imgsrc_photo1' 	=> $imgsrc_photo1 ,
		'imgsrc_photo2' 	=> $imgsrc_photo2 ,
	) ;

}else{
	
	unset($_SESSION['GNAVI_PREVIEW_EXIF']);
	
	if($mode==G_INSERT){

		$photo = array(
			'lid'	=> 0 ,
			'title'	=> '' ,
			'cid'	=> $p_cid ,
			'cid1'	=> 0,
			'cid2'	=> 0,
			'cid3' 	=> 0,
			'cid4' 	=> 0,
			'icd' 	=> 0,
			'ext' 	=> '' ,
			'ext1' 	=> '' ,
			'ext2' 	=> '' ,
			'caption' 	=> '' ,
			'caption1' 	=> '' ,
			'caption2' 	=> '' ,
			'url' 	=> '' ,
			'tel' 	=> '' ,
			'fax' 	=> '' ,
			'zip' 	=> '' ,
			'address' 	=> '' ,
			'rss' 	=> '' ,
			'lat' 	=> 0 ,
			'lng' 	=> 0 ,
			'zoom' 	=> 0 ,
			'mtype' 	=> '' ,
			'submitter' => $submitter ,
			'poster_name' 	=> ( $my_uid > 0 ? gnavi_get_name_from_uid( $my_uid ) : '' ),
			'description' 	=> '' ,
			'addinfo' 	=> '' ,
			'status' 	=> 0 ,
			'valid' 	=> 0 ,
			'exif'      => $exif_init
		) ;

	}else{

		$result = $xoopsDB->query( "SELECT l.lid,l.title,l.cid,l.cid1,l.cid2,l.cid3,l.cid4,l.ext,l.ext1,l.ext2,l.caption,l.caption1,l.caption2,l.url,l.tel,l.fax,l.zip,l.address,l.rss,l.lat,l.lng,l.zoom,l.mtype,l.icd,l.submitter,l.poster_name,l.status, e.exif, t.description,t.arrowhtml,t.addinfo FROM $table_photos l LEFT JOIN $table_text t ON l.lid=t.lid LEFT JOIN $table_exif e ON l.lid=e.lid WHERE l.lid=$lid" ) ;
		$photo = $xoopsDB->fetchArray( $result ) ;
		$photo['imgsrc_photo'] 	= $imgsrc_photo ;
		$photo['imgsrc_photo1'] = $imgsrc_photo1 ;
		$photo['imgsrc_photo2'] = $imgsrc_photo2 ;
		$photo['valid'] = $photo['status'] ;
		$photo['exif'] = @ unserialize($photo['exif']);
		if (! $photo['exif']) {
			$photo['exif'] = $exif_init;
		}
		$_SESSION['GNAVI_PREVIEW_EXIF'] = base64_encode(serialize($photo['exif']));

		//map hidden
		if ($photo['lat']==0 && $photo['lng']==0) $p_set_latlng = 0 ;
		$p_cid = $photo['cid'] ;

	}

	//set map default
	if ($photo['lat']==0 && $photo['lng']==0){

		if ($p_cid) {
			$result = $xoopsDB->query( "SELECT lat,lng,zoom,mtype FROM $table_cat WHERE cid=$p_cid" ) ;
			list($lat,$lng,$zoom,$mtype) = $xoopsDB->fetchRow( $result ) ;
		}else{
			$lat=0;
			$lng=0;
			$zoom=0;
			$mtype='';
		}

		$photo['lat']  = $p_lat  ? $p_lat  : ($lat!=0  ? $lat  : $gnavi_defaultlat);
		$photo['lng']  = $p_lng  ? $p_lng  : ($lng!=0  ? $lng  : $gnavi_defaultlng);
		$photo['zoom'] = $p_zoom ? $p_zoom : ($zoom!=0 ? $zoom : $gnavi_defaultzoom);
		$photo['mtype'] = $p_mtype ? $p_mtype : ($mtype ? $mtype : $gnavi_defaultmtype);

	}
}


// Show the form
OpenTable() ;
if (defined('HYP_K_TAI_RENDER') && HYP_K_TAI_RENDER > 0) {
	$form = new XoopsSimpleForm( ($mode == G_INSERT ? _MD_GNAV_SMT_UPLOAD : _MD_GNAV_SMT_EDIT ) , "uploadphoto", "index.php?page=submit" ) ;
} else {
	$form = new XoopsThemeForm( ($mode == G_INSERT ? _MD_GNAV_SMT_UPLOAD : _MD_GNAV_SMT_EDIT ) , "uploadphoto", "index.php?page=submit" ) ;
}
$form->setExtra("enctype='multipart/form-data'");
$xoops_module_header = $xoopsTpl->get_template_vars( "xoops_module_header" ) ."\n" ."<link rel='stylesheet' type='text/css' href='css/gnavi.css'/>";

//each setting of insert or update; 

if($mode == G_INSERT){
	$canuse_editor = $global_perms & GNAV_GPERM_WYSIWYG ;
}else{
	$canuse_editor = $global_perms & GNAV_GPERM_WYSIWYG && ( $my_uid == $photo['submitter'] || $photo['arrowhtml'] ) ? 1 : 0 ;
	if(!$photo['arrowhtml']){
		if( $gnavi_body_editor == 'common_fckeditor' && $canuse_editor ||
			$gnavi_body_editor == 'common_spaw' && file_exists( XOOPS_ROOT_PATH.'/common/spaw/spaw_control.class.php' ) && $canuse_editor  ||
			$gnavi_body_editor == 'pure_html' && $canuse_editor ){

			//if use editor with already inputed dhtml,change data for html

			$photo['description'] = $myts->displayTarea( $photo['description'] , 0 , 1 , 1 , 1 , 1 , 1) ;

		}
	}

	$status_hidden = new XoopsFormHidden( "old_status" , $photo['status'] ) ;
	$valid_or_not = $photo['valid'] ? 1 : 0 ;
	$valid_box = new XoopsFormCheckBox( _MD_GNAV_SMT_VALIDPHOTO , "valid" , array( $valid_or_not ) ) ;
	$valid_box->addOption( '1' , '&nbsp;' ) ;
	$storets_box = new XoopsFormCheckBox(_MD_GNAV_SMT_UPDATEDATE, "store_timestamp" , array( 0 ) ) ;
	$storets_box->addOption( '1' , '&nbsp;' ) ;

	if( $global_perms & GNAV_GPERM_DELETABLE ) {
		$del_tray = new XoopsFormElementTray(_MD_GNAV_SMT_DELETE) ;
		$delete_button = new XoopsFormButton( "" , "conf_delete" , _DELETE , "submit" ) ;
		$del_tray->addElement( $delete_button ) ;
	}
}

//labels
$pixels_text = "$gnavi_width x $gnavi_height" ;
if( $gnavi_canresize ) $pixels_text .=_MD_GNAV_ITM_AUTORESIZE ;
$pixels_label = new XoopsFormLabel( _MD_GNAV_ITM_ABOUTFILE ,sprintf( _MD_GNAV_ITM_ABOUTFILEDESC,$pixels_text,strval(intval(intval($gnavi_fsize)/1048576*10)/10))) ;
$cation_label = new XoopsFormLabel( _MD_GNAV_ITM_CAUTION , _MD_GNAV_ITM_ABOUTUPLOADS ) ;


$title_text = new XoopsFormText( _MD_GNAV_ITM_TITLE, "title" , 50 , 255 , $myts->makeTboxData4Edit( $photo['title'] ) ) ;
$caption_text  = new XoopsFormText(_MD_GNAV_ITM_CAPTION1, "caption" , 50 , 255 , $myts->makeTboxData4Edit( $photo['caption'] ) ) ;
$caption1_text = new XoopsFormText(_MD_GNAV_ITM_CAPTION2, "caption1" , 50 , 255 , $myts->makeTboxData4Edit( $photo['caption1'] ) ) ;
$caption2_text = new XoopsFormText(_MD_GNAV_ITM_CAPTION3, "caption2" , 50 , 255 , $myts->makeTboxData4Edit( $photo['caption2'] ) ) ;

//----------------------------editor-------------------------------------------
$desc_tarea = $hidden_body_html = new stdClass;
// lazy registering & call pre build delegate
if (defined('XOOPS_CUBE_LEGACY')) {
	$delegate = new XCube_Delegate();
	$delegate->register(ucfirst($mydirname).'.Submit.BuildEditorForm');
	$delegate->call(new XCube_Ref($desc_tarea), new XCube_Ref($hidden_body_html), $photo['description'], $canuse_editor);
}

if (! ($desc_tarea instanceof XoopsFormElement) || ! ($hidden_body_html instanceof XoopsFormElement) ) {
	if( $gnavi_body_editor == 'common_fckeditor' && $canuse_editor ) {
	
		// FCKeditor in common/fckeditor/
		$xoops_module_header .= '
			<script type="text/javascript" src="'.XOOPS_URL.'/common/fckeditor/fckeditor.js"></script>
			<script type="text/javascript"><!--
				function fckeditor_exec() {
					var oFCKeditor = new FCKeditor( "desc_text" , "100%" , "500" , "Default" );
					
					oFCKeditor.BasePath = "'.XOOPS_URL.'/common/fckeditor/";
					
					oFCKeditor.ReplaceTextarea();
				}
			// --></script>
		' ;
		$wysiwyg_body = '<textarea id="desc_text" name="desc_text">'.htmlspecialchars( $photo['description'] ,ENT_QUOTES).'</textarea><script>fckeditor_exec();</script>' ;
		$desc_tarea =  new XoopsFormLabel( _MD_GNAV_ITM_DESC , $wysiwyg_body ) ;
		$hidden_body_html = new XoopsFormHidden("body_html","1") ;
	
	} else if( $gnavi_body_editor == 'common_spaw' && file_exists( XOOPS_ROOT_PATH.'/common/spaw/spaw_control.class.php' ) && $canuse_editor ) {
	
		// older spaw in common/spaw/
		include XOOPS_ROOT_PATH.'/common/spaw/spaw_control.class.php' ;
		ob_start() ;
		$sw = new SPAW_Wysiwyg( "desc_text" ,  $photo['description']  ) ;
		$sw->show() ;
		$wysiwyg_body = ob_get_contents() ;
		ob_end_clean() ;
		$desc_tarea =  new XoopsFormLabel( _MD_GNAV_ITM_DESC , $wysiwyg_body ) ;
		$hidden_body_html = new XoopsFormHidden("body_html","1") ;
	
	}else if ($gnavi_body_editor == 'pure_html' && $canuse_editor ){
		$desc_tarea = new XoopsFormTextArea(_MD_GNAV_ITM_DESC, "desc_text" , $myts->makeTareaData4Edit( $photo['description'] ) , 20 , 60 ) ;
		$hidden_body_html = new XoopsFormHidden("body_html","1") ;
	} else {
		$desc_tarea = new XoopsFormDhtmlTextArea(_MD_GNAV_ITM_DESC, "desc_text" , $myts->makeTareaData4Edit( $photo['description'] ) , 20 , 60 ) ;
		if (method_exists($desc_tarea, 'setEditor')) {
			// XoopsFormDhtmlTextArea::setEditor
			// https://github.com/xoopscube/legacy/pull/68 (xoopscube)
			// https://github.com/XoopsX/legacy/pull/44 (XoopsX)
			$desc_tarea->setEditor($canuse_editor? 'html' : 'bbcode');
			$hidden_body_html = new XoopsFormHidden("body_html", $canuse_editor? '1' : '0') ;
		} else {
			$hidden_body_html = new XoopsFormHidden("body_html","0") ;
		}
	}
}

//---------------------------------------------------------------------------------
$add_info_text = new XoopsFormTextArea(_MD_GNAV_ITM_ADDINFO, "addinfo" , $myts->makeTareaData4Edit( $photo['addinfo'] ) , 6 , 50 ) ;
$add_info_desc = new XoopsFormLabel( "" , _MD_GNAV_ITM_ADDINFODESC ) ;

if( gnavi_get_anony_perms() & GNAV_GPERM_INSERTABLE) {
	$poster_name_text = new XoopsFormText(_MD_GNAV_ITM_POSTERNAME, "poster_name" , 30 , 60 , $myts->makeTboxData4Edit( $photo['poster_name'] ) ) ;
}else{
	$poster_name_text = new XoopsFormHidden("poster_name",$myts->makeTboxData4Edit( $photo['poster_name'] )) ;
}

//category
$cat_select = new XoopsFormSelect( _MD_GNAV_ITM_CATMAIN , "cid" , $photo['cid'] ) ;
if($mode == G_INSERT)$cat_select->addOption( '' , '----' ) ;
$cat_select1 = new XoopsFormSelect( _MD_GNAV_ITM_CAT1 , "cid1" , $photo['cid1'] ) ;
$cat_select1->addOption( '' , '----' ) ;
$cat_select2 = new XoopsFormSelect( _MD_GNAV_ITM_CAT2 , "cid2" , $photo['cid2'] ) ;
$cat_select2->addOption( '' , '----' ) ;
$cat_select3 = new XoopsFormSelect( _MD_GNAV_ITM_CAT3 , "cid3" , $photo['cid3'] ) ;
$cat_select3->addOption( '' , '----' ) ;
$cat_select4 = new XoopsFormSelect( _MD_GNAV_ITM_CAT4 , "cid4" , $photo['cid4'] ) ;
$cat_select4->addOption( '' , '----' ) ;
$tree = $cattree->getChildTreeArray( 0 , "weight,title" ) ;
foreach( $tree as $leaf ) {
	$leaf['prefix'] = substr( $leaf['prefix'] , 0 , -1 ) ;
	$leaf['prefix'] = str_replace( "." , "--" , $leaf['prefix'] ) ;
	$cat_select->addOption( $leaf['cid'] , $leaf['prefix'] . $leaf['title'] ) ;
	$cat_select1->addOption( $leaf['cid'] , $leaf['prefix'] . $leaf['title'] ) ;
	$cat_select2->addOption( $leaf['cid'] , $leaf['prefix'] . $leaf['title'] ) ;
	$cat_select3->addOption( $leaf['cid'] , $leaf['prefix'] . $leaf['title'] ) ;
	$cat_select4->addOption( $leaf['cid'] , $leaf['prefix'] . $leaf['title'] ) ;
}

//fileform
if($photo['ext']){
	$photoview = new XoopsFormLabel(_MD_GNAV_ITM_FILE1, "<img src='".$photo['imgsrc_photo']."' width='150' />" ) ;
	$file_form = new XoopsFormFile(_MD_GNAV_ITM_EDIT_FILE1, "photofile" , $gnavi_fsize ) ;
	$del_box = new XoopsFormCheckBox( "&nbsp;" , "del_photo" , array( 0 ) ) ;
	$del_box->addOption( '1' ,_MD_GNAV_ITM_DEL_FILE1) ;
	$del_hidden = new XoopsFormHidden( "del_photo",$del_photo) ;
}else{
	$file_form = new XoopsFormFile(_MD_GNAV_ITM_FILE1, "photofile" , $gnavi_fsize ) ;
	if(!$gnavi_allownoimage){
		$form->setRequired( $file_form ) ;
	}
}
$file_form->setExtra( "size='70'" ) ;
if($photo['ext1']){
	$photoview1 = new XoopsFormLabel(_MD_GNAV_ITM_FILE2, "<img src='".$photo['imgsrc_photo1']."' width='150' />" ) ;
	$file_form1 = new XoopsFormFile(_MD_GNAV_ITM_EDIT_FILE2, "photofile1" , $gnavi_fsize ) ;
	$del_box1 = new XoopsFormCheckBox( "&nbsp;" , "del_photo1" , array( 0 ) ) ;
	$del_box1->addOption( '1' ,_MD_GNAV_ITM_DEL_FILE2) ;
}else{
	$file_form1 = new XoopsFormFile(_MD_GNAV_ITM_FILE2, "photofile1" , $gnavi_fsize ) ;
	$del_hidden1 = new XoopsFormHidden( "del_photo1",$del_photo1) ;
}
$file_form1->setExtra( "size='70'" ) ;
if($photo['ext2']){
	$photoview2 = new XoopsFormLabel(_MD_GNAV_ITM_FILE3, "<img src='".$photo['imgsrc_photo2']."' width='150' />" ) ;
	$file_form2 = new XoopsFormFile(_MD_GNAV_ITM_EDIT_FILE3, "photofile2" , $gnavi_fsize ) ;
	$del_box2 = new XoopsFormCheckBox( "&nbsp;" , "del_photo2" , array( 0 ) ) ;
	$del_box2->addOption( '1' ,_MD_GNAV_ITM_DEL_FILE3) ;
}else{
	$file_form2 = new XoopsFormFile(_MD_GNAV_ITM_FILE3, "photofile2" , $gnavi_fsize ) ;
	$del_hidden2 = new XoopsFormHidden( "del_photo2",$del_photo2) ;
}
$file_form2->setExtra( "size='70'" ) ;

//other
$op_hidden = new XoopsFormHidden( "op" , "submit" ) ;
$counter_hidden = new XoopsFormHidden( "fieldCounter" , 1 ) ;
$preview_hidden = new XoopsFormHidden( "preview_name" , htmlspecialchars( $preview_name ) , ENT_QUOTES ) ;
$preview1_hidden = new XoopsFormHidden( "preview_name1" , htmlspecialchars( $preview_name1 ) , ENT_QUOTES ) ;
$preview2_hidden = new XoopsFormHidden( "preview_name2" , htmlspecialchars( $preview_name2 ) , ENT_QUOTES ) ;

$submit_button = new XoopsFormButton( "" , "submit" , _SUBMIT , "submit" ) ;
$preview_button = new XoopsFormButton( "" , "preview" , _PREVIEW , "submit" ) ;
$gps_preview_button = new XoopsFormButton( "" , "gps_preview" , _PREVIEW , "submit" ) ;
$reset_button = new XoopsFormButton( "" , "reset" , ($mode == G_INSERT ? _MD_GNAV_SMT_CLEAR : _CANCEL ) , "reset" ) ;
$submit_tray = new XoopsFormElementTray( '' ) ;
$submit_tray->addElement( $preview_button ) ;
$submit_tray->addElement( $submit_button ) ;
$submit_tray->addElement( $reset_button ) ;
if (@ $gnavi_configs['gnavi_use_gps']) {
	$preview_tray = new XoopsFormElementTray( _MD_GNAV_GPS_PREVIEW ) ;
	$preview_tray->addElement( $gps_preview_button ) ;
	$preview_tray->addElement( new XoopsFormLabel( '' , _MD_GNAV_GPS_PREVIEW_DESC ) ) ;
} else {
	$preview_tray = null;
}
$lid_hidden = new XoopsFormHidden( "lid",$photo['lid']) ;


//moreinfo
$url_text = new XoopsFormText(_MD_GNAV_ITM_URL, "url" , 50 , 255 , $myts->makeTboxData4Edit( $photo['url'] ) ) ;
$tel_text = new XoopsFormText(_MD_GNAV_ITM_TEL, "tel" , 20 , 20 , $myts->makeTboxData4Edit( $photo['tel'] ) ) ;
$fax_text = new XoopsFormText(_MD_GNAV_ITM_FAX, "fax" , 20 , 20 , $myts->makeTboxData4Edit( $photo['fax'] ) ) ;
$zip_text = new XoopsFormText(_MD_GNAV_ITM_ZIP, "zip" , 10 , 8 , $myts->makeTboxData4Edit( $photo['zip'] ) ) ;
$rss_text = new XoopsFormText(_MD_GNAV_ITM_RSS, "rss" , 50 , 255 , $myts->makeTboxData4Edit( $photo['rss'] ) ) ;

if($language=='japanese' || $language=='ja_utf8' ){
	if(file_exists(XOOPS_ROOT_PATH.$gnavi_ajaxzip_place."ajaxzip2.js")){
$xoops_module_header .="
<script>var window.gnaviNoconflictArrayFrom = Array.from;// for StreetView mouse events</script>
<script src='js/prototype.js' charset='UTF-8'></script>
<script>Array.from = window.gnaviNoconflictArrayFrom;delete  window.gnaviNoconflictArrayFrom;</script>
<script src='".XOOPS_URL.$gnavi_ajaxzip_place."ajaxzip2.js' charset='UTF-8'></script>
<script type='text/javascript'>
//<![CDATA[
	AjaxZip2.JSONDATA = '".XOOPS_URL.$gnavi_ajaxzip_place."data';
//]]>
</script>
";
	$zip_text->setExtra("onKeyUp=\"AjaxZip2.zip2addr(this,'address','address');\"");
	}
}

$address_tray = new XoopsFormElementTray(_MD_GNAV_ITM_ADDRESS,'' );
$address_text = new XoopsFormText( "" , "address" , 50 , 255 , $myts->makeTboxData4Edit( $photo['address'] ) ) ;
$address_tray->addElement($address_text);
if($gnavi_usegooglemap){
	$geo_button = new XoopsFormButton( "" , "geo" ,_MD_GNAV_MAP_SEARCH, "button" ) ;
	$geo_button->setExtra("onClick=\"showAddress(document.getElementById('address').value);\"");
	$address_tray->addElement($geo_button);
}

//Google Maps
if($gnavi_usegooglemap){
$xoops_module_header .="<script src='".$gnavi_googlemap_url."/maps/api/js?v=".$gnavi_gmapapi_ver."&amp;key=".$gnavi_googlemapapi_key."&amp;sensor=false' type='text/javascript' charset='utf-8'></script>
<script src='js/jpegmeta.js' type='text/javascript' charset='utf-8'></script>
<script src='js/map.js' type='text/javascript' charset='utf-8'></script>
<script type='text/javascript'>
//<![CDATA[
	$gnavi_lang_java
	google.maps.event.addDomListener(window, 'load', function(){ InputGMap(); });
//]]>
</script>";

if($p_set_latlng){
	$set_latlng_state = '' ;
}else{
	$set_latlng_state = ' checked ' ;
}

$gmap = new XoopsFormLabel(_MD_GNAV_MAP, "
<div style='margin-bottom:2px;'><input type='checkbox' name='set_latlng' id='set_latlng' value='1' onclick='ChangeMapArea(this)' $set_latlng_state/>&nbsp;"._MD_GNAV_MAP_UNINPUT."</div>
<div id='maparea'>
<div id='map' style='width:100%;height:400px;'></div>
<div id='gn_latlng'>"._MD_GNAV_MAP_LAT.":&nbsp;<span id='slat'>".$myts->makeTboxData4Edit($photo['lat'])."</span>&nbsp;&nbsp;&nbsp;"._MD_GNAV_MAP_LNG.":&nbsp;<span id='slng'>".$myts->makeTboxData4Edit($photo['lng'])."</span>&nbsp;&nbsp;&nbsp;"._MD_GNAV_MAP_ZOOM.":&nbsp;<span id='sz'>".$myts->makeTboxData4Edit($photo['zoom'])."</span></div>
<input type='hidden' name='lat' id='lat' value='".$myts->makeTboxData4Edit($photo['lat'])."' />
<input type='hidden' name='lng' id='lng' value='".$myts->makeTboxData4Edit($photo['lng'])."' />
<input type='hidden' name='z' id='z' value='".$myts->makeTboxData4Edit($photo['zoom'])."' />
<input type='hidden' name='mt' id='mt' value='".$myts->makeTboxData4Edit($photo['mtype'])."' />
<input type='hidden' name='pchange' id='pchange' value='".($pchange?'1':'0')."' />
</div>" ) ;
if($gnavi_icon_by_lid){
	$icon_select = new XoopsFormSelect(_MD_GNAV_MAP_ICON, 'icd', $photo['icd'], 1, false);
	$sql = "SELECT icd, title FROM $table_icon ";
	$result = $xoopsDB->query($sql);
	$icons_array = array();
	$icons_array[0] = '---';
	while ($myrow = $xoopsDB->fetchArray($result)) {
		$icons_array[$myrow['icd']] = $myrow['title'];
	}
	$icon_select->addOptionArray($icons_array);
}
}


//----------form-Start--------------------------
$form->addElement( $title_text ) ;
$form->setRequired( $title_text ) ;
$form->addElement( $cat_select ) ;
if($mode == G_INSERT)$form->setRequired( $cat_select ) ;
$form->addElement( $cat_select1 ) ;
$form->addElement( $cat_select2 ) ;
$form->addElement( $cat_select3 ) ;
$form->addElement( $cat_select4 ) ;
$form->addElement( $desc_tarea ) ;
$form->addElement( $hidden_body_html ) ;

$form->insertBreak(_MD_GNAV_SMT_TITLE_FILE);

if($photo['ext']){
	$form->addElement( $photoview ) ;
	if($gnavi_allownoimage){
		$form->addElement( $del_box ) ;
	}
}else{
	$form->addElement( $del_hidden ) ;
}
$form->addElement( $file_form ) ;
$form->addElement( $caption_text ) ;
if($photo['ext1']){
	$form->addElement( $photoview1 ) ;
	$form->addElement( $del_box1 ) ;
}else{
	$form->addElement( $del_hidden1 ) ;
}
$form->addElement( $file_form1 ) ;
$form->addElement( $caption1_text ) ;
if($photo['ext2']){
	$form->addElement( $photoview2 ) ;
	$form->addElement( $del_box2 ) ;
}else{
	$form->addElement( $del_hidden2 ) ;
}
$form->addElement( $file_form2 ) ;
$form->addElement( $caption2_text ) ;
$form->addElement( $pixels_label ) ;
if ($preview_tray) $form->addElement( $preview_tray ) ;

$form->insertBreak(_MD_GNAV_SMT_TITLE_INFO);

$form->addElement( $url_text ) ;
$form->addElement( $tel_text ) ;
$form->addElement( $fax_text ) ;
$form->addElement( $zip_text ) ;
$form->addElement( $address_tray ) ;
if($gnavi_use_rss){
	$form->addElement( $rss_text ) ;
}

if($gnavi_usegooglemap){
	$form->addElement( $gmap ) ;
	if($gnavi_icon_by_lid)$form -> addElement( $icon_select );
}

if($gnavi_addinfo){
	$form->addElement( $add_info_text ) ;
	$form->addElement( $add_info_desc ) ;
}

$form->insertBreak(_MD_GNAV_SMT_TITLE_UPDT);


$form->addElement( $poster_name_text ) ;
$form->addElement( $preview_hidden ) ;
$form->addElement( $preview1_hidden ) ;
$form->addElement( $preview2_hidden ) ;
$form->addElement( $counter_hidden ) ;
$form->addElement( $op_hidden ) ;
$form->addElement( $lid_hidden ) ;

if($mode == G_UPDATE && $isadmin ) {
	$form->addElement( $valid_box ) ;
	$form->addElement( $storets_box ) ;
	$form->addElement( $status_hidden ) ;
}
$form->addElement( $submit_tray ) ;
$form->addElement( $cation_label ) ;

if($mode == G_UPDATE && ($global_perms & GNAV_GPERM_DELETABLE)) {
	$form->insertBreak(_MD_GNAV_SMT_TITLE_DELT);
	$form->addElement( $del_tray ) ;
}

$xoopsTpl->assign('xoops_module_header',$xoops_module_header);
//----------form-end--------------------------

// Ticket
$GLOBALS['xoopsGTicket']->addTicketXoopsFormElement( $form , __LINE__ ) ;

$form->display() ;
CloseTable() ;
gnavi_footer() ;

include( XOOPS_ROOT_PATH . "/footer.php" ) ;

