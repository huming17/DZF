<?php

if(!defined('IN_SYSTEM')) {
	exit('Access Denied');
}

function updatecache($cachename = '') {

	$updatelist = empty($cachename) ? array() : (is_array($cachename) ? $cachename : array($cachename));

	if(!$updatelist) {
		//@include_once libfile('cache/setting', 'function');
		build_cache_setting();
		$cachedir = SITE_ROOT.'./source/function/cache';
		$cachedirhandle = dir($cachedir);
		while($entry = $cachedirhandle->read()) {
			if(!in_array($entry, array('.', '..')) && preg_match("/^cache\_([\_\w]+)\.php$/", $entry, $entryr) && $entryr[1] != 'setting' && substr($entry, -4) == '.php' && is_file($cachedir.'/'.$entry)) {
				@include_once libfile('cache/'.$entryr[1], 'function');
				call_user_func('build_cache_'.$entryr[1]);
			}
		}
	} else {
		foreach($updatelist as $entry) {
			@include_once libfile('cache/'.$entry, 'function','..');
			call_user_func('build_cache_'.$entry);
		}
	}

}

function writetocache($script, $cachedata, $prefix = 'cache_', $define_path='') {
	global $_G;
    if($define_path){
       $dir = $define_path; 
    }else{
       $dir = SITE_ROOT.'./data/sysdata/'; 
    }
	if(!is_dir($dir)) {
		dmkdir($dir, 0777);
	}
	if($fp = @fopen("$dir$prefix$script.php", 'wb')) {
		fwrite($fp, "<?php\n//DZF! cache file, DO NOT modify me!\n//Identify: ".md5($prefix.$script.'.php'.$cachedata.$_G['config']['security']['authkey'])."\n\n$cachedata?>");
		fclose($fp);
	} else {
		exit('Can not write to cache files, please check directory ./data/ and ./data/sysdata/ .');
	}
}


function getcachevars($data, $type = 'VAR') {
	$evaluate = '';
	foreach($data as $key => $val) {
		if(!preg_match("/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/", $key)) {
			continue;
		}
		if(is_array($val)) {
			$evaluate .= "\$$key = ".arrayeval($val).";\n";
		} else {
			$val = addcslashes($val, '\'\\');
			$evaluate .= $type == 'VAR' ? "\$$key = '$val';\n" : "define('".strtoupper($key)."', '$val');\n";
		}
	}
	return $evaluate;
}

function smthumb($size, $smthumb = 50) {
	if($size[0] <= $smthumb && $size[1] <= $smthumb) {
		return array('w' => $size[0], 'h' => $size[1]);
	}
	$sm = array();
	$x_ratio = $smthumb / $size[0];
	$y_ratio = $smthumb / $size[1];
	if(($x_ratio * $size[1]) < $smthumb) {
		$sm['h'] = ceil($x_ratio * $size[1]);
		$sm['w'] = $smthumb;
	} else {
		$sm['w'] = ceil($y_ratio * $size[0]);
		$sm['h'] = $smthumb;
	}
	return $sm;
}

function arrayeval($array, $level = 0) {
	if(!is_array($array)) {
		return "'".$array."'";
	}
	if(is_array($array) && function_exists('var_export')) {
		return var_export($array, true);
	}

	$space = '';
	for($i = 0; $i <= $level; $i++) {
		$space .= "\t";
	}
	$evaluate = "Array\n$space(\n";
	$comma = $space;
	if(is_array($array)) {
		foreach($array as $key => $val) {
			$key = is_string($key) ? '\''.addcslashes($key, '\'\\').'\'' : $key;
			$val = !is_array($val) && (!preg_match("/^\-?[1-9]\d*$/", $val) || strlen($val) > 12) ? '\''.addcslashes($val, '\'\\').'\'' : $val;
			if(is_array($val)) {
				$evaluate .= "$comma$key => ".arrayeval($val, $level + 1);
			} else {
				$evaluate .= "$comma$key => $val";
			}
			$comma = ",\n$space";
		}
	}
	$evaluate .= "\n$space)";
	return $evaluate;
}

function pluginsettingvalue($type) {
	$pluginsetting = $pluginvalue = array();
	@include SITE_ROOT.'./data/sysdata/cache_pluginsetting.php';
	$pluginsetting = isset($pluginsetting[$type]) ? $pluginsetting[$type] : array();

	$varids = $pluginids = array();
	foreach($pluginsetting as $pluginid => $v) {
		foreach($v['setting'] as $varid => $var) {
			$varids[] = $varid;
			$pluginids[$varid] = $pluginid;
		}
	}
	if($varids) {
		foreach(C::t('common_pluginvar')->fetch_all($varids) as $plugin) {
			$values = (array)dunserialize($plugin['value']);
			foreach($values as $id => $value) {
				$pluginvalue[$id][$pluginids[$plugin['pluginvarid']]][$plugin['variable']] = $value;
			}
		}
	}

	return $pluginvalue;
}

function cleartemplatecache() {
	$tpl = dir(SITE_ROOT.'./data/template');
	while($entry = $tpl->read()) {
		if(preg_match("/\.tpl\.php$/", $entry)) {
			@unlink(SITE_ROOT.'./data/template/'.$entry);
		}
	}
	$tpl->close();
}
?>