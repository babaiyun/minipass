<?php //Copyright (c) babaiyun.com[cxly_21@163.com] All rights reserved.

$_SERVER['BBY_DEBUG'] =1;

!isset($_SERVER['BBY_DEBUG']) && $_SERVER['BBY_DEBUG'] =0;

function_exists('ini_set') && ini_set('display_errors', $_SERVER['BBY_DEBUG'] ? '1' : '0');
error_reporting($_SERVER['BBY_DEBUG'] ? E_ALL : 0);

date_default_timezone_set('Asia/Shanghai');
$_SERVER['BBY_START_TIME'] =microtime(1);
$_SERVER['BBY_TIME'] =time();
$_SERVER['BBY_METHOD'] =strtoupper($_SERVER['REQUEST_METHOD']);

version_compare(PHP_VERSION, '5.3.0', '<') && set_magic_quotes_runtime(0);
$magic_quotes_gpc =get_magic_quotes_gpc();

define( 'BBY_CLI', !empty($_SERVER['SHELL']) || empty($_SERVER['REMOTE_ADDR']) );
define( 'BBY_WIN', strstr(PHP_OS, 'WIN') ? 1 : 0 );
define( 'BBY_PATH', __DIR__ .'/' );

header("Content-type: text/html; charset=utf-8");
session_start();

function _GET($k, $v=NULL, $t=1) { return isset($_GET[$k]) ? ($t?trim($_GET[$k]):$_GET[$k]) : $v; }
function _POST($k, $v=NULL, $t=1) { return isset($_POST[$k]) ? ($t?trim($_POST[$k]):$_POST[$k]) : $v; }
function _SESSION($k, $v=NULL) { return isset($_SESSION[$k]) ? $_SESSION[$k] : $v; }
function _SERVER($k, $v=NULL, $t=1) { return isset($_SERVER[$k]) ? ($t?trim($_SERVER[$k]):$_SERVER[$k]) : $v; }
function _G($k, $v=NULL) { return isset($GLOBALS[$k]) ? $GLOBALS[$k] : $v; }

function bby_timefmt($ts=0, $fmt=''){
	$ts==0 && $ts =$_SERVER['BBY_TIME'];
	!$fmt && $fmt='Y-m-d H:i:s';
	return date($fmt, $ts);
}
function bby_datefmt($ts=0, $fmt=''){
	!$fmt && $fmt='Y-m-d';
	return bby_timefmt($ts, $fmt);
}

function bby_export_php($var){
	return "<?php\nreturn " .var_export($var, TRUE) .";";
}

function bby_human_byte($n){
	if(!$n) return '-';
	if($n > 1073741824){
		return round($n / 1073741824, 2).' G';
	}else if($n > 1048576){
		return round($n / 1048576, 2).' M';
	}else if($n > 1024){
		return round($n / 1024, 2).' K';
	}
	return $n.' B';
}

function bby_location($url){
	header('Location: '. $url); exit;
}

function bby_addslashes($v){
	return !is_array($v) ? addslashes($v) : array_map('bby_addslashes', $v);
}
function bby_stripslashes($v){
	return !is_array($v) ? stripslashes($v) : array_map('bby_stripslashes', $v);
}

//htmlspecialchars(ENT_QUOTES)
function bby_htmlspchars($v){
	if( !is_array($v) ){
		return str_replace( array('&', '"', "'", '<', '>'), array('&amp;', '&quot;', '&apos;', '&lt;', '&gt;'), $v );
	}else{
		return array_map('bby_htmlspchars', $v);
	}
}
//htmlspecialchars_decode
function bby_htmlspchars_decode($v){
	if( !is_array($v) ){
		return str_replace( array('&amp;', '&quot;', '&apos;', '&lt;', '&gt;'), array('&', '"', "'", '<', '>'), $v );
	}else{
		return array_map('bby_htmlspchars_decode', $v);
	}
}
function bby_input($r, $safe=1){
	global $magic_quotes_gpc;
	if($safe){
		$r =bby_htmlspchars($r);
		!$magic_quotes_gpc && $r =bby_addslashes($r);
	}else{
		$magic_quotes_gpc && $r =bby_stripslashes($r);
	}
	return $r;
}
function bby_input_post($k, $v=NULL, $t=1, $safe=1){
	return bby_input( _POST($k, $v, $t), $safe );
}
function bby_input_get($k, $v=NULL, $t=1, $safe=1){
	return bby_input( _GET($k, $v, $t), $safe );
}
function bby_input_server($k, $v=NULL, $t=1, $safe=1){
	return bby_input( _SERVER($k, $v, $t), $safe );
}

function bby_json_encode($data){
	if(version_compare(PHP_VERSION, '5.4.0') >= 0) {
		return json_encode($data, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
	}
	switch($type = gettype($data)) {
		case 'NULL':
			return 'null';
		case 'boolean':
			return ($data ? 'true' : 'false');
		case 'integer':
		case 'double':
		case 'float':
			return $data;
		case 'string':
			$data = '"'.str_replace(array('\\', '"'), array('\\\\', '\\"'), $data).'"';
			$data = str_replace("\r", '\\r', $data);
			$data = str_replace("\n", '\\n', $data);
			$data = str_replace("\t", '\\t', $data);
			return $data;
		case 'object':
			$data = get_object_vars($data);
		case 'array':
			$output_index_count = 0;
			$output_indexed = array();
			$output_associative = array();
			foreach($data as $key => $value) {
				$output_indexed[] = rp_json_encode($value);
				$output_associative[] = '"'.$key.'":' . rp_json_encode($value);
				if ($output_index_count !== NULL && $output_index_count++ !== $key) {
					$output_index_count = NULL;
				}
			}
			if($output_index_count !== NULL) {
				return '[' . implode(",", $output_indexed) . ']';
			} else {
				return '{' . implode(",", $output_associative) . '}';
			}
		default:
			return ''; // Not supported
	}
}
function bby_json_decode($json, $arr=1){
	$json =trim($json, "\xEF\xBB\xBF");
	$json =trim($json, "\xFE\xFF");
	return json_decode($json, $arr);
}

//xxtea encrypt or decrypt
function bby_encrypt($str, $key){ return base64_encode( xxtea_encrypt($str, $key) ); }
function bby_decrypt($str, $key){ return xxtea_decrypt( base64_decode($str), $key ); }
function xxtea_long2str($v, $w) {
	$len = count($v);
	$n = ($len - 1) << 2;
	if ($w) {
		$m = $v[$len - 1];
		if (($m < $n - 3) || ($m > $n)) return FALSE;
		$n = $m;
	}
	$s = array();
	for ($i = 0; $i < $len; $i++) {
		$s[$i] = pack("V", $v[$i]);
	}
	if ($w) {
		return substr(join('', $s), 0, $n);
	}
	else {
		return join('', $s);
	}
}
function xxtea_str2long($s, $w) {
	$v = unpack("V*", $s. str_repeat("\0", (4 - strlen($s) % 4) & 3));
	$v = array_values($v);
	if ($w) {
		$v[count($v)] = strlen($s);
	}
	return $v;
}
function xxtea_int32($n) {
	while ($n >= 2147483648) $n -= 4294967296;
	while ($n <= -2147483649) $n += 4294967296;
	return (int)$n;
}
function xxtea_encrypt($str, $key) {
	if($str == '') return '';
	$v = xxtea_str2long($str, TRUE);
	$k = xxtea_str2long($key, FALSE);
	if (count($k) < 4) {
		for ($i = count($k); $i < 4; $i++) {
			$k[$i] = 0;
		}
	}
	$n = count($v) - 1;

	$z = $v[$n];
	$y = $v[0];
	$delta = 0x9E3779B9;
	$q = floor(6 + 52 / ($n + 1));
	$sum = 0;
	while (0 < $q--) {
		$sum = xxtea_int32($sum + $delta);
		$e = $sum >> 2 & 3;
		for ($p = 0; $p < $n; $p++) {
			$y = $v[$p + 1];
			$mx = xxtea_int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ xxtea_int32(($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
			$z = $v[$p] = xxtea_int32($v[$p] + $mx);
		}
		$y = $v[0];
		$mx = xxtea_int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ xxtea_int32(($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
		$z = $v[$n] = xxtea_int32($v[$n] + $mx);
	}
	return xxtea_long2str($v, FALSE);
}
function xxtea_decrypt($str, $key) {
	if($str == '') return '';
	$v = xxtea_str2long($str, FALSE);
	$k = xxtea_str2long($key, FALSE);
	if(count($k) < 4) {
		for ($i = count($k); $i < 4; $i++) {
			$k[$i] = 0;
		}
	}
	$n = count($v) - 1;

	$z = $v[$n];
	$y = $v[0];
	$delta = 0x9E3779B9;
	$q = floor(6 + 52 / ($n + 1));
	$sum = xxtea_int32($q * $delta);
	while ($sum != 0) {
		$e = $sum >> 2 & 3;
		for ($p = $n; $p > 0; $p--) {
			$z = $v[$p - 1];
			$mx = xxtea_int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ xxtea_int32(($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
			$y = $v[$p] = xxtea_int32($v[$p] - $mx);
		}
		$z = $v[$n];
		$mx = xxtea_int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ xxtea_int32(($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
		$y = $v[0] = xxtea_int32($v[0] - $mx);
		$sum = xxtea_int32($sum - $delta);
	}
	return xxtea_long2str($v, TRUE);
}

function bby_fopen_get($file, $try=3){
	while($try-- > 0){
		$fp = fopen($file, 'rb');
		if($fp){
			$size =filesize($file);
			if($size == 0) return '';
			$s =fread($fp, $size);
			fclose($fp);
			return $s;
		}else{
			sleep(1);
		}
	}
	return FALSE;
}
function bby_fopen_put($file, $s, $try=3){
	while($try-- > 0){
		$fp =fopen($file, 'wb');
		if( $fp && flock($fp, LOCK_EX) ){
			$n =fwrite($fp, $s);
			version_compare(PHP_VERSION, '5.3.2', '>=') && flock($fp, LOCK_UN);
			fclose($fp);
			clearstatcache();
			return $n;
		}else{
			sleep(1);
		}
	}
	return FALSE;
}

function arrlist_multisort(&$arr, $col, $asc=1){
	$r =array();
	!is_array($arr) && $arr =array();
	foreach($arr as $k=>$v) $r[$k] =$v[$col];
	$asc =$asc ? SORT_ASC : SORT_DESC;
	array_multisort($r, $asc, $arr);
}
function arrlist_slice(array $arr, $start, $len=0){
	if( isset($arr[0]) ) return array_slice($arr, $start, $len);
	$k_arr =array_keys($arr);
	$k_arr =array_slice($k_arr, $start, $len);
	$r =array();
	foreach($k_arr as $k) $r[$k] =$arr[$k];
	return $r;
}

function bby_msgbox($msg, $url='back', $sec=-1, $errcode=10001, $title='', $plus='', $confirm=FALSE){
	while( ob_get_level() > 0 ){
		ob_end_clean();
	}
	
	if ($msg == '404') {
		header("HTTP/1.1 404 Not Found");
		$msg = '抱歉，你所请求的页面不存在！';
	}
	
	if( FALSE === $confirm ){
		$ck_str ='点击跳转 &raquo;';
		if($url == 'back'){
			$ck_str ='&laquo; 点击返回';
			$url ='javascript:history.back(-1);';
		}
		$link ='<p style="padding:0;margin:0;font-size:14px;text-align:center"><br/><a href="' .$url .'">' .$ck_str .'</a></p>';
	}else{
		$yes_str =isset($confirm['yes_str']) ? $confirm['yes_str'] : '确认删除';
		$no_str =isset($confirm['no_str']) ? $confirm['no_str'] : '取消';
		$no_url =isset($confirm['no_url']) ? $confirm['no_url'] : '';
		empty($no_url) && $no_url ='javascript:history.back(-1);';
		$link ='<p style="padding:0;margin:0;font-size:14px;text-align:center"><br/><a href="' .$url .'">' .$yes_str .'</a><a style="margin-left:20px" href="' .$no_url .'">' .$no_str .'</a></p>';
	}
	
	$errcode && $msg ='<span style="color:#f36;">'.$msg.'</span>';
	empty($title) && $title ='BBY Msgbox';
	
	if($sec >= 0){
		$sec =intval( (float)$sec * 1000 );
		$plus .='<script type="text/javascript">var _BBY_JUMP=1; function bby_jump_url(){ if(_BBY_JUMP){window.location.href="'.$url.'"; _BBY_JUMP=0;} }; setTimeout("bby_jump_url()",'.$sec.')</script>';
	}
	
	echo '<!DOCTYPE HTML><html><head><meta charset="utf-8"/><meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" name="viewport"/><title>' .$title .'</title></head><body><div style="-moz-box-shadow:0 0 5px #EAEAEA;background:#FFFEF5;font-size:14px;border:3px solid #DCC7AB;color:#333;font:14px/1.5 Arial,Microsoft Yahei,Simsun;margin:50px auto;padding:30px;max-width:760px;">' .$msg .$link .'</div>' .$plus .'</body></html>';
	exit;
}
function bby_errbox($msg, $url='back', $errcode=10001, $plus=''){
	$title =bby_env_get('mb_err_title', 'Error Msgbox');
	bby_msgbox($msg, $url, -1, $errcode=10001, $title, $plus, FALSE);
}
function bby_succbox($msg, $url, $sec=2, $plus=''){
	$title =bby_env_get('mb_succ_title', 'Success Msgbox');
	bby_msgbox($msg, $url, $sec, 0, $title, $plus, FALSE);
}
function bby_confirmbox($msg, $url, $confirm=TRUE, $plus=''){
	$title =bby_env_get('mb_confirm_title', 'Confirm Msgbox');
	bby_msgbox($msg, $url, -1, 0, $title, $plus, $confirm);
}
function bby_diebox($msg, $plus=''){
	$title =bby_env_get('mb_die_title', 'Fatalerr Msgbox');
	bby_msgbox($msg, 'back', -1, 10004, $title, $plus, FALSE);
}

function bby_ip(){
    $ip =isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    if( !filter_var($ip, FILTER_VALIDATE_IP) ){
        $ip ='';
    }
    return $ip;
}
$_SERVER['BBY_IP'] =bby_ip();

$_bbyhooks =array();
function bby_hook_add($tag, $func){
    global $_bbyhooks;
	!isset($_bbyhooks[$tag]) && $_bbyhooks[$tag] =array();
    if( !in_array($func, $_bbyhooks[$tag]) ){
        $_bbyhooks[$tag][] = $func;
	}
}
function bby_hook_do($tag, $ret=NULL){
    global $_bbyhooks;
    $args =array_slice( func_get_args(), 1 );
    if( isset($_bbyhooks[$tag]) ){
		foreach($_bbyhooks[$tag] as $func){
			$ret =call_user_func_array($func, $args);
        }
    }
	return $ret;
}

$_bbyenvs =array();
function bby_env_set($k, $v){
	global $_bbyenvs;
	$_bbyenvs[$k] =$v;
}
function bby_env_get($k, $v=''){
	global $_bbyenvs;
	return isset($_bbyenvs[$k]) ? $_bbyenvs[$k] : $v;
}

function bby_log($s, $file='error'){
	if( $_SERVER['BBY_DEBUG'] == 0 && strpos($file, 'error') === FALSE ){
		return;
	}
	
	$time =$_SERVER['BBY_TIME'];
	$ip =$_SERVER['BBY_IP'];
	$day =date('Ym', $time);
	$mtime =date('Y-m-d H:i:s');
	$url =_SERVER('REQUEST_URI');
	$user =_SESSION('user','_user_');
	
	$path =BBY_PATH .'_data/'.$day;
	!is_dir($path) && mkdir($path, 0777, true);
	
	$s =str_replace( array("\r\n", "\n", "\t"), ' ', $s );
	$s = "<?php exit;?>\t$mtime\t$ip\t$url\t$user\t$s\r\n";
	
	@error_log($s, 3, $path."/$file.php");
}

function bby_http_get($url){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$result =curl_exec($ch);
	curl_close($ch);
	return $result;
}
function bby_http_post($url, $data){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url); 
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $result =curl_exec($ch);
    if(curl_errno($ch)){
       return 'errno: '.curl_error($ch);
    }
    curl_close($ch);
    return $result;
}
//BBY template view from Composer

function cp_skin_errbox($s){
	echo '<p class="msgbox msgbox-error">'. $s .'</p>';
}
function cp_skin_succbox($s){
	echo '<p class="msgbox msgbox-success">'. $s .'</p>';
}
function cp_skin_warnbox($s){
	echo '<p class="msgbox msgbox-warn">'. $s .'</p>';
}

//tpl header
function cp_skin_header(){
?>
<!DOCTYPE html>
<html class="no-js" lang="en"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo bby_env_get('site_name');?></title>
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<meta name="description" content="Minipass powerby Babaiyun.com">
<style>
article,aside,details,figcaption,figure,footer,header,hgroup,nav,section{display:block;}audio,canvas,video{display:inline-block;*display:inline;*zoom:1;}audio:not([controls]){display:none;}[hidden]{display:none;}html{font-size:100%;overflow-y:scroll;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;}body{margin:0;font-size:13px;line-height:1.231;}body,button,input,select,textarea{font-family:sans-serif;color:#222;}a{color:#00e;}a:visited{color:#551a8b;}a:hover{color:#06e;}a:focus{outline:thin dotted;}a:hover,a:active{outline:0;}abbr[title]{border-bottom:1px dotted;}b,strong{font-weight:bold;}blockquote{margin:1em 40px;}dfn{font-style:italic;}hr{display:block;height:1px;border:0;border-top:1px solid #ccc;margin:1em 0;padding:0;}ins{background:#ff9;color:#000;text-decoration:none;}mark{background:#ff0;color:#000;font-style:italic;font-weight:bold;}pre,code,kbd,samp{font-family:monospace,monospace;_font-family:'courier new',monospace;font-size:1em;}pre{white-space:pre;white-space:pre-wrap;word-wrap:break-word;}q{quotes:none;}q:before,q:after{content:"";content:none;}small{font-size:85%;}sub,sup{font-size:75%;line-height:0;position:relative;vertical-align:baseline;}sup{top:-0.5em;}sub{bottom:-0.25em;}ul,ol{margin:1em 0;padding:0 0 0 40px;}dd{margin:0 0 0 40px;}nav ul,nav ol{list-style:none;list-style-image:none;margin:0;padding:0;}img{border:0;-ms-interpolation-mode:bicubic;vertical-align:middle;}svg:not(:root){overflow:hidden;}figure{margin:0;}form{margin:0;}fieldset{border:0;margin:0;padding:0;}label{cursor:pointer;}legend{border:0;*margin-left:-7px;padding:0;}button,input,select,textarea{font-size:100%;margin:0;vertical-align:baseline;*vertical-align:middle;}button,input{line-height:normal;*overflow:visible;}table button,table input{*overflow:auto;}button,input[type="button"],input[type="reset"],input[type="submit"]{cursor:pointer;-webkit-appearance:button;}input[type="checkbox"],input[type="radio"]{box-sizing:border-box;}input[type="search"]{-webkit-appearance:textfield;-moz-box-sizing:content-box;-webkit-box-sizing:content-box;box-sizing:content-box;}input[type="search"]::-webkit-search-decoration{-webkit-appearance:none;}button::-moz-focus-inner,input::-moz-focus-inner{border:0;padding:0;}textarea{overflow:auto;vertical-align:top;resize:vertical;}input:valid,textarea:valid{}input:invalid,textarea:invalid{background-color:#f0dddd;}table{border-collapse:collapse;border-spacing:0;}td{vertical-align:top;}
#main, header { margin: auto; max-width: 800px; margin-bottom: 30px; font-size: 16px; line-height: 150%;}
a, a:link, a:visited, a:hover { text-decoration: none; color: #44f;}
header { font-size: 23px; margin-top: 20px; margin-bottom: 40px;}
header a { margin: 0 8px 0 0; padding: 0 8px 0 0; display: inline-block; border-right: 1px solid #ccc;}
header a.last { border: 0;}
header a.active { color: #000;}
header .slogan { margin-top: 20px;}
.logo { text-align: center;}
footer { margin: auto; max-width: 600px; margin-bottom: 5px; text-align: center;}
h1, h2 { font-weight: normal; }
h1 { font-size: 30px; line-height: 130%; }
h1 .anchor, h2 .anchor, h3 .anchor, h4 .anchor { display: inline-block; margin-left: 5px; color: #ccc;}
input, textarea{
  font-size: 16px; padding: 5px 10px; border: 1px solid #ccc; -webkit-border-radius: 2px; -moz-border-radius: 2px; border-radius: 2px;
}
.buttons { text-align: center; margin: 0; padding: 0;}
.buttons .btn {
  font-size: 18px; display: inline-block; width: 160px;
  border: 1px solid #ccc; -webkit-border-radius: 4px; -moz-border-radius: 4px; border-radius: 4px;
  padding: 10px; margin: 10px; background: #eee;
}
.buttons .btn:hover { background: #ffe;}
blockquote {
  background: #ddf; border: 1px solid #ddf; -webkit-border-radius: 4px;
  -moz-border-radius: 4px; border-radius: 4px; margin: 0 20px; padding: 10px;
}
blockquote p { margin: 0;}

.toc, .toc ul { padding-left: 40px; margin: 0 0 10px 0; list-style: decimal outside none; color: #000;}
.toc ul { padding-left: 20px;}

.dark{ color:#333; }
.gray{ color:#777; }
code:not([class*="language-"]) {
  border: 1px solid #ccc; -webkit-border-radius: 3px; -moz-border-radius: 3px; border-radius: 3px;
  padding: 0px 4px; display: inline-block; line-height: 125%; margin: 0 2px;
}
pre.installer {
  overflow-x: scroll; white-space: pre; word-wrap: normal; border: 1px solid #ccc; padding: 5px; border-radius: 3px;
}

.msgbox{ border-radius: 4px; margin: 0 20px; padding: 10px; }
.msgbox-success { background: #ddf; }
.msgbox-error { background: #DA9386; }
.msgbox-warn { background: #FFECDD; }

.list{}
.list li:hover{ background-color:#F8F8F8; }

.ul-list{margin:0; padding:0 0 0 30px;}
.ul-list li{font-size:20px; color:#bbb;}
.ul-list li:hover{ background-color:#F8F8F8; }
.ht-item{padding:5px;}
.ht-tit{color:#333; font-size:20px; font-weight:normal; margin:10px 0;}

.prev-next { text-align: center; margin-top: 40px;}
.prev-next a{ margin-left:10px; margin-right:10px;}
.tagline { display: block; margin-bottom: 5px;}
.tagact{ color:#999; font-size:14px; margin:2px 5px; }
.tagact a{ color:#333; }
.tagact a:hover{ color:#369; }

table { width: 100%;}
tr { border-bottom: 1px solid #ddd;}
tr:last-child { border-bottom: none;}
th, td { text-align: left; padding: 0.5em;}

.hidden { display: none !important; visibility: hidden; }
.invisible { visibility: hidden; }
.clearfix:before, .clearfix:after { content: ""; display: table; }
.clearfix:after { clear: both; }
.clearfix { zoom: 1; }

@media (max-width: 800px) {
  body { margin-left: 15px; margin-right: 15px;}
}
@media print {
  * { background: transparent !important; color: black !important; text-shadow: none !important; filter:none !important; -ms-filter: none !important; }
  a, a:visited { text-decoration: underline; }
  a[href]:after { content: " (" attr(href) ")"; }
  abbr[title]:after { content: " (" attr(title) ")"; }
  .ir a:after, a[href^="javascript:"]:after, a[href^="#"]:after { content: ""; }
  pre, blockquote { border: 1px solid #999; page-break-inside: avoid; }
  thead { display: table-header-group; }
  tr, img { page-break-inside: avoid; }
  img { max-width: 100% !important; }
  @page { margin: 0.5cm; }
  p, h2, h3 { orphans: 3; widows: 3; }
  h2, h3 { page-break-after: avoid; }
}
</style>
<?php bby_hook_do('tpl_page_head');?>
</head><body>
<div id="container">
<?php bby_hook_do('tpl_page_top');?>
<div id="main">
<?php
}

//tpl footer
function cp_skin_footer(){
?>
</div>
<footer>
<?php bby_hook_do('tpl_page_foot');?>
</footer>
</div>
<?php bby_hook_do('tpl_page_ftplus');?>
</body></html>
<?php
}
//bby_minipass_data_start

$mpdata_str ="";

//bby_minipass_data_end

$mpdata =array();
$mpdata_file =__FILE__;

function sessdata_get($k){
	return _SESSION($k);
}

//admin
function mpdata_defpass(){
	return '21232f297a57a5a743894a0e4a801fc3';
}
function mpdata_init(){
	return array( 'id_index'=>0, 'user'=>'admin', 'pass'=>mpdata_defpass(), 'ptime'=>0, 'data'=>array() );
}

//数据解码初始化
function mpdata_decode_init(){
	global $mpdata_str, $mpdata, $islogin;
	if( empty($mpdata_str) ){
		$mpdata =mpdata_init();
		return;
	}
	if( !$islogin ){
		return;
	}
	
	$pass =sessdata_get('pass');
	$mpdata =bby_json_decode( bby_decrypt($mpdata_str, $pass), 1);
}

function minipass_check_sess(){
	global $mpdata, $islogin;
	if( !$islogin ){
		return;
	}
	
	if( !isset($mpdata['pass']) || $mpdata['pass']!=sessdata_get('pass') || $mpdata['user']!=sessdata_get('user') ){
		mpdata_logout();
	}
}

function minipass_savedata(){
	global $mpdata, $islogin, $mpdata_file;
	if(!$islogin) return;
	
	$mpdata['ptime'] =time();
	$pass =$mpdata['pass'];
	$str =bby_encrypt( bby_json_encode($mpdata), $pass );
	$str =str_replace('"', '&quot;', $str);
	
	$data =bby_fopen_get($mpdata_file);
	
	$backup_file =$mpdata_file .'_bak.php';
	$ret =bby_fopen_put($backup_file, $data);
	!$ret && bby_errbox('Minipass file backup error');
	
	$p =strpos($data, '//bby_minipass_data_start');
	$s ="\xEF\xBB\xBF". substr($data, 0, $p+25) ."\n\r";
	$s .='$'.'mpdata_str ="'. $str .'";';
	$p =strpos($data, '//bby_minipass_data_end');
	$s .="\n\r". substr($data, $p);
	unset($data);
	
	$ret =bby_fopen_put($mpdata_file, $s);
	!$ret && bby_errbox('Minipass data save error');
	
	@unlink($backup_file);
}

//获取数据项目
function mpdata_get($k){
	global $mpdata;
	$r =isset($mpdata[$k]) ? $mpdata[$k] : '';
	switch($k){
		case 'data': return !is_array($r) ? array() : $r; break;
		case 'id_index':
			$r =(int) $r;
			return ($r + 1);
		break;
		case 'ptime':
			$r =(int) $r;
			return !$r ? '' : date('Y-m-d H:i:s', $r);
		break;
	}
	return $r;
}

//登录数据解码验证
function mpdata_check_login($user, $pass){
	global $mpdata_str, $mpdata;
	$pass_md5 =md5($pass);
	
	if( $mpdata_str != '' ){
		$tmp_str =bby_decrypt($mpdata_str, $pass_md5);
		$mpdata =$tmp_str ? bby_json_decode($tmp_str, 1) : array();
	}
	
	if( !isset($mpdata['user']) || $mpdata['user']!=$user ){
		return FALSE;
	}
	if( !isset($mpdata['pass']) || $mpdata['pass']!=$pass_md5 ){
		return FALSE;
	}
	
	$_SESSION['user'] =$user;
	$_SESSION['pass'] =$pass_md5;
	$_SESSION['islogin'] ='TRUE';
	
	return TRUE;
}


$mp_act =_GET('act');
empty($mp_act) && $mp_act ='home';

$islogin =_SESSION('islogin')=='TRUE'?1:0;

mpdata_decode_init();
minipass_check_sess();

//注销登录
function mpdata_logout(){
	global $islogin;
	$islogin =0;
	
	if( isset($_SESSION['islogin']) ) unset($_SESSION['islogin']);
	if( isset($_SESSION['user']) ) unset($_SESSION['user']);
	if( isset($_SESSION['pass']) ) unset($_SESSION['pass']);
}
bby_env_set('site_name', 'Minipass');

bby_hook_add('tpl_page_top', 'mp_page_topnav');
bby_hook_add('tpl_page_foot', 'mp_page_foot');


//view:passbox
function mp_view_passbox(){
	global $mpdata;
	
	$page =(int) _GET('page'); $page<1 && $page =1; $pagesize =12;
	
	$start =($page - 1) * $pagesize;
	$limit =$start + $pagesize;
	
	$srch_par ='';
	$srch_wd =_GET('srch_wd');
	if($srch_wd){
		$srch_wd =urldecode($srch_wd);
		$srch_par ='&srch_wd=' .urlencode($srch_wd);
		
		$datalist =array();
		foreach($mpdata['data'] as $val){
			if( strpos($val['tit'], $srch_wd) !== FALSE ){
				$datalist[] =$val;
			}
		}
		
		$cnt =count($datalist);
		if($cnt) $datalist =arrlist_slice($datalist, $start, $pagesize);
		
	}else{
		$cnt =count($mpdata['data']);
		$datalist =arrlist_slice($mpdata['data'], $start, $pagesize);
	}
	
	$total =ceil($cnt / $pagesize);
	$prev =$next =0;
	$page>1 && $prev =$page - 1;
	$page<$total && $next =$page + 1;
	
	cp_skin_header();
?>
<script>
function pass_del(id){
	if( confirm("确定要删除吗？") ){
		location ="?act=passbox_del&id="+id;
	}
}
function pass_view(id){
	var dis, oE =document.getElementById("pass_view_"+id);
	if(!oE) return;
	dis =oE.style.display;
	oE.style.display =dis=="none" ? "" : "none";
}
</script>
<h1>我的保险箱 <small>(<?php echo $cnt;?>)</small> <small class="gray">[<a href="?act=passbox_add">新建</a>]</small></h1>
<div style="text-align:right"><form action="" method="GET">
<input type="hidden" name="act" value="passbox" />
<input type="text" name="srch_wd" value="<?php echo $srch_wd;?>" style="width:200px" /><input class="btn" type="submit" style="margin-left:10px" value="搜 索" />
</form></div>
<ul class="ul-list">
<?php foreach($datalist as $val){ ?>
<li class="ht-item"><h2 class="ht-tit">
<?php if($val['url']){ ?>
	<a href="<?php echo $val['url'];?>" target="_blank"><?php echo $val['tit'];?></a>
<?php }else{ ?>
	<?php echo $val['tit'];?>
<?php } ?></h2>
<div id="pass_view_<?php echo $val['id'];?>" style="display:none">
	<table><tbody>
	<tr><td style="width:60px" class="gray">用户名</td><td><?php echo $val['userid'];?></td></tr>
	<tr><td class="gray">密码</td><td><?php echo $val['pass'];?></td></tr>
	<?php if($val['bak']){ ?><tr><td class="gray">备注</td><td><?php echo $val['bak'];?></td></tr><?php } ?>
	</tbody></table>
</div>
<div class="tagline">
	<span class="tagact">[<a href="?act=passbox_edit&id=<?php echo $val['id'];?>">编辑</a>]</span>
	<span class="tagact">[<a href="javascript:;" onclick="pass_del(<?php echo $val['id'];?>)">删除</a>]</span>
	<span class="tagact">[<a href="javascript:;" onclick="pass_view(<?php echo $val['id'];?>)">查看详细</a>]</span>
</div>
</li>
<?php } ?>
</ul>
<p class="prev-next">
<?php if($prev){ ?><a href="?act=passbox&page=<?php echo $prev. $srch_par;?>">&larr; 上一页</a><?php } ?>
<?php if($total>1){ ?><span class="gray">[<?php echo $page;?> / <?php echo $total;?>]</span><?php } ?>
<?php if($next){ ?><a href="?act=passbox&page=<?php echo $next. $srch_par;?>">下一页 &rarr;</a><?php } ?>
</p>
<?php
	cp_skin_footer();
}

//保险箱项目表单
function mp_passbox_form(array $row, $is_edit=0){
	$tit =$is_edit ? '修改项目' : '新建项目';
	$fm_act =$is_edit ? '?act=passbox_edit' : '?act=passbox_add';
?>
<h1><?php echo $tit;?></h1>
<form action="<?php echo $fm_act;?>" method="POST" >
<input type="hidden" name="is_edit" value="<?php echo $is_edit;?>" />
<input type="hidden" name="id" value="<?php if( isset($row['id']) ) echo $row['id'];?>" />
<p>标题：<br/><input type="text" name="tit" value="<?php if( isset($row['tit']) ) echo $row['tit'];?>" style="width:100%" /></p>
<p>网页URL（可选）：<br/><input type="text" name="url" value="<?php if( isset($row['url']) ) echo $row['url'];?>" style="width:100%" /></p>
<p>用户ID：<br/><input type="text" name="userid" value="<?php if( isset($row['userid']) ) echo $row['userid'];?>" style="width:200px" autocomplete="off" /></p>
<p>密码：<br/><input type="text" name="pass" value="<?php if( isset($row['pass']) ) echo $row['pass'];?>" style="width:200px" autocomplete="off" /></p>
<p>备注（可选）：<br/><textarea name="bak" style="width:100%;height:100px;"><?php if( isset($row['bak']) ) echo $row['bak'];?></textarea></p>
<p class="buttons"><input class="btn" type="submit" value="保 存" /></p>
<p class="buttons gray">[<a href="javascript:history.back(-1)">取消</a>]</p>
</form>
<?php
}

function passbox_check_row($id){
	global $mpdata;
	$row =$id ? (isset($mpdata['data'][$id])? $mpdata['data'][$id]: NULL) : NULL;
	if(!$row){
		bby_errbox('保险箱项目不存在。');
	}
	return $row;
}

function mp_view_passbox_add(){
	if( $_SERVER['BBY_METHOD'] == 'POST' ){
		mp_passbox_save();
	}
	cp_skin_header();
	$row =array();
	mp_passbox_form($row, 0);
	cp_skin_footer();
}

function mp_view_passbox_edit(){
	if( $_SERVER['BBY_METHOD'] == 'POST' ){
		mp_passbox_save();
	}
	cp_skin_header();

	$id =(int) _GET('id');
	$row =passbox_check_row($id);
	
	mp_passbox_form($row, 1);
	cp_skin_footer();
}

function mp_view_passbox_del(){
	$id =(int) _GET('id');
	$row =passbox_check_row($id);
	
	global $mpdata;
	unset($mpdata['data'][$id]);
	
	minipass_savedata();
	bby_location('?act=passbox');
}

function mp_passbox_save(){
	$is_edit =(int) _POST('is_edit');
	$tit =bby_input_post('tit');
	$url =bby_input_post('url');
	$userid =bby_input_post('userid');
	$pass =bby_input_post('pass');
	$bak =bby_input_post('bak');
	
	if( empty($tit) || empty($userid) || empty($pass) ){
		bby_errbox('项目标题，用户ID，密码都属于必填项目。');
	}
	
	global $mpdata;
	if( $is_edit ){
		$id =(int) _POST('id');
		if( !isset($mpdata['data'][$id]) ) exit('Post IN_Err!');
		
		$mpdata['data'][$id]['tit'] =$tit;
		$mpdata['data'][$id]['url'] =$url;
		$mpdata['data'][$id]['userid'] =$userid;
		$mpdata['data'][$id]['pass'] =$pass;
		$mpdata['data'][$id]['bak'] =$bak;
		
	}else{
		$id_index =mpdata_get('id_index');
		$mpdata['id_index'] =$id_index;
		$mpdata['data'][$id_index] =array( 'id'=>$id_index, 'tit'=>$tit, 'url'=>$url, 'userid'=>$userid, 'pass'=>$pass, 'bak'=>$bak, 'ptime'=>time() );
	}
	
	minipass_savedata();
	bby_location('?act=passbox');
}

//view:myinfo
function mp_view_myinfo(){
	if( $_SERVER['BBY_METHOD'] == 'POST' ){
		$f_user =bby_input_post('user');
		$f_pass =bby_input_post('pass');
		$newuser =bby_input_post('newuser');
		$newpass =bby_input_post('newpass');
		$renewpass =bby_input_post('renewpass');
		
		if( empty($f_user) || empty($f_pass) ){
			bby_errbox('用户名和密码必须填写。');
		}
		if( $f_user != sessdata_get('user') ){
			bby_errbox('用户名验证错误。');
		}
		if( md5($f_pass) != sessdata_get('pass') ){
			bby_errbox('密码验证错误。');
		}
		
		$ok =0;
		
		//修改用户名
		if( !empty($newuser) && $newuser!=sessdata_get('user') ){
			if( strlen($newuser)<3 ){
				bby_errbox('新用户名必须至少3个字符。');
			}
			
			$f_user =$newuser;
			$ok =1;
		}
		
		//修改密码
		if( !empty($newpass) && md5($newpass)!=sessdata_get('pass') ){
			if( strlen($newpass)<5 || $newpass!=$renewpass ){
				bby_errbox('新密码必须至少5个字符，且两次密码输入必须一致。');
			}
			
			$f_pass =$newpass;
			$ok =1;
		}
		
		if($ok){
			global $mpdata;
			$mpdata['user'] =$f_user;
			$mpdata['pass'] =md5($f_pass);
			
			minipass_savedata();
			mpdata_logout();
			
			bby_succbox('修改成功，请重新登录。', '?act=login');
			
		}else{
			bby_succbox('无任何修改。', '?act=home');
		}
	}
	
	cp_skin_header();
?>
<h1>修改密码</h1>
<p class="gray"><code>用户名和密码是进入保险箱的唯一凭证，请谨慎修改。</code></p>
<form action="?act=myinfo" method="POST" >
<p>用户名：<br/><input type="text" name="user" value="" style="width:200px" autocomplete="off" /></p>
<p>密码：<br/><input type="password" name="pass" value="" style="width:200px" autocomplete="off" /></p>
<p>新用户名<span class="gray">（不修改，请留空）</span>：<br/><input type="text" name="newuser" value="" style="width:200px" autocomplete="off" /></p>
<p>新密码<span class="gray">（不修改，请留空）</span>：<br/><input type="password" name="newpass" value="" style="width:200px" autocomplete="off" /></p>
<p>确认新密码：<br/><input type="password" name="renewpass" value="" style="width:200px" autocomplete="off" /></p>
<p class="buttons"><input class="btn" type="submit" value="保 存" /></p>
<p class="buttons gray">[<a href="javascript:history.back(-1)">取消</a>]</p>
</form>
<?php
	cp_skin_footer();
}

//view:login
function mp_view_login(){
	if( $_SERVER['BBY_METHOD'] == 'POST' ){
		$f_user =bby_input_post('user');
		$f_pass =bby_input_post('pass');
		if( empty($f_user) || empty($f_pass) ){
			bby_errbox('用户名密码不能为空。');
		}else{
			$ret =mpdata_check_login($f_user, $f_pass);
			if(!$ret){
				bby_errbox('用户名密码错误。');
			}else{
				bby_location('?act=passbox');
			}
		}
	}
	
	cp_skin_header();
?>
<h1>登录</h1>
<form action="?act=login" method="POST" >
<p>用户名：<br/><input type="text" name="user" value="" style="width:200px" autocomplete="off" /></p>
<p>密 码：<br/><input type="password" name="pass" value="" style="width:200px" autocomplete="off" /></p>
<p class="buttons"><input class="btn" type="submit" value="提 交" /></p>
</form>
<?php
	cp_skin_footer();
}

//view:home
function mp_view_home(){
	cp_skin_header();
?>
<div class="logo">
	<!--<img src="">-->
	<h1 class="slogan">Minipass</h1>
	<h2 class="slogan" style="line-height:150%">一款免费、小巧、开源的密码管理工具，PHP单文件密码保险箱。</h2>
</div>

<p class="buttons">
<?php if( _G('islogin') ){ ?>
	<a href="?act=myinfo" class="btn">修改密码</a>
	<a href="?act=passbox" class="btn">我的保险箱</a>
<?php }else{ ?>
	<a href="?act=login" class="btn">登录保险箱</a>
<?php } ?>
</p>

<p class="buttons">
	<a href="https://github.com/babaiyun" class="btn" target="_blank">Babaiyun</a>
	<a href="https://github.com/babaiyun/minipass" class="btn" target="_blank">GitHub</a>
</p>

<?php
	cp_skin_footer();
}


//顶部导航
function mp_page_topnav(){
	global $mp_act, $islogin;
?>
<header>
<a class="<?php if($mp_act=='home') echo 'active';?>" href="?act=home">首页</a>
<?php if( !$islogin ){ ?>
	<a class="<?php if($mp_act=='login') echo 'active';?>" href="?act=login">登录</a>
<?php }else{?>
	<a class="<?php if($mp_act=='passbox') echo 'active';?>" href="?act=passbox">我的保险箱</a>
	<a class="<?php if($mp_act=='passbox_add') echo 'active';?>" href="?act=passbox_add">新建项目</a>
	<a href="?act=logout">注销(<?php echo sessdata_get('user');?>)</a>
<?php }?>
<a class="last" href="https://github.com/babaiyun/minipass" target="_blank">GitHub</a>
</header>
<?php
	if( $islogin && sessdata_get('pass')==mpdata_defpass() && $mp_act!='myinfo' ){
		cp_skin_warnbox('警告：你还在使用默认初始密码，为提高安全性，请重新设置自己的用户名和密码。[<a href="?act=myinfo">立即修改</a>]');
	}
}

//页脚
function mp_page_foot(){
?>
<p class="license">Minipass released under the <a href="https://github.com/babaiyun/minipass/blob/master/LICENSE">MIT license</a>.</p>
<?php
}

function mp_view_logout(){
	mpdata_logout();
	bby_location('?act=login');
}


$act_func ='mp_view_'. $mp_act;
$act_notlogin =array('home'=>1, 'login'=>1);

if( function_exists($act_func) ){
	if( !isset($act_notlogin[$mp_act]) && !$islogin ){
		bby_location('?act=login');
		exit();
	}
	
	$act_func();
	
}else{
	bby_diebox('router not found');
}
