<?php
/// Copyright (c) 2004-2008, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)

/** Pre-processor - This file 
    ----------
    
*/
/** Loading Basic Components */
require_once (ROOT.'/library/components/Needlworks.PHP.UnifiedEnvironment.php');
require_once (ROOT.'/library/components/Needlworks.PHP.Core.php');
require_once (ROOT.'/library/components/Needlworks.PHP.BaseClasses.php');
require_once (ROOT.'/library/components/Needlworks.PHP.Loader.php');

/** Basic POST/GET variable validation. */
if (isset($IV)) {
	if (!Validator::validate($IV)) {
		header('HTTP/1.1 404 Not Found');
		exit;
	}
}
/** Basic SERVER variable validation. */
$basicIV = array(
	'SCRIPT_NAME' => array('string'),
	'REQUEST_URI' => array('string'),
	'REDIRECT_URL' => array('string', 'mandatory' => false)
);
Validator::validateArray($_SERVER, $basicIV);
/** Basic URI information validation. */
if(isset($URLInfo)) {
	$basicIV = array(
		'fullpath' => array('string'),
		'input'    => array('string'),
		'position' => array('string'),
		'root'     => array('string'),
		'input'    => array('string', 'mandatory' => false)
	);
	$URLInfo['fullpath'] = urldecode($URLInfo['fullpath']);
	Validator::validateArray($URLInfo, $basicIV);
}

/** Loading Configuration */
global $config, $context;
		
$config = Config::getInstance();
$context = Context::getInstance(); // automatic initialization via first instanciation

/** Loading debug module */
if($config->service['debugmode'] == true) {
	if(isset($config->service['dbms'])) {
		switch($config->service['dbms']) {
			case 'mysqli':         require_once(ROOT. "/library/components/Needlworks.Debug.MySQLi.php");break;
			case 'mysql': default: require_once(ROOT. "/library/components/Needlworks.Debug.MySQL.php"); break;
		}
	} else require_once(ROOT. "/library/components/Needlworks.Debug.MySQL.php");
}
    
/** Loading components / models / views */
require_once (ROOT.'/library/include.'.$context->URLInfo['interfaceType'].'.php');
require_once (ROOT.'/library/include.php');

/** Sending header */
header('Content-Type: text/html; charset=utf-8');
/** Database I/O initialization. */
if(!empty($config->database) && !empty($config->database["database"])) {
	if(POD::bind($config->database) === false) {
		Respond::MessagePage('Problem with connecting database.<br /><br />Please re-visit later.');
		exit;
	}
}
$database['utf8'] = (POD::charset() == 'utf8') ? true : false;
/** Memcache module bind (if possible) */
$memcache = null;
if(!empty($config->database) && !empty($config->service['memcached']) && $config->service['memcached'] == true): 
	$memcache = new Memcache;
	$memcache->connect((isset($memcached['server']) && $memcached['server'] ? $memcached['server'] : 'localhost'));
endif;

/** Parse URI and gather blogID and URI parameters */
$context->URIParser();
/** Setting global variables */
$context->globalVariableParser();

/** Initializing Locale Resources */
$__locale = array(
	'locale' => null,
	'directory' => './locale',
	'domain' => null,
	);

// Set timezone.
if(isset($config->database) && !empty($config->database['database'])) {
	$timezone = new Timezone;
	$timezone->set(isset($blog['timezone']) ? $blog['timezone'] : $config->service['timezone']);
	POD::query('SET time_zone = \'' . $timezone->getCanonical() . '\'');
}

// Load administration panel locale.
if(!defined('NO_LOCALE')) {
	Locale::setDirectory(ROOT . '/resources/language');
	Locale::set(isset($blog['language']) ? $blog['language'] : $service['language']);

	// Load blog screen locale.
	if (!isset($blog['blogLanguage'])) {
		$blog['blogLanguage'] = $service['language'];
	}
	Locale::setSkinLocale(isset($blog['blogLanguage']) ? $blog['blogLanguage'] : $service['language']);
}

/** Initializing Session */
if (!defined('NO_SESSION')) {
	session_name(Session::getName());
	Session::set();
	session_set_save_handler( array('Session','open'), array('Session','close'), array('Session','read'), array('Session','write'), array('Session','destroy'), array('Session','gc') );
	session_cache_expire(1);
	session_set_cookie_params(0, '/', $service['domain']);
	if (session_start() !== true) {
		header('HTTP/1.1 503 Service Unavailable');
	}
}

/** Administration panel skin / editor template initialization */
if(in_array($context->URLInfo['interfaceType'], array('owner','reader')) || defined('__TEXTCUBE_ADMINPANEL__')) {
	$adminSkinSetting = array();
	$adminSkinSetting['skin'] = "/skin/admin/".getBlogSetting("adminSkin", "canon");
	// 1.5에서 올라온 경우 스킨이 있는 경우를 위한 workaround.
/*		if(($adminSkinSetting['skin'] == '/skin/admin/default') ||
	 ($adminSkinSetting['skin'] == '/skin/admin/whitedream')) {
		setBlogSetting("adminSkin", "canon");
		$adminSkinSetting['skin'] = "/skin/admin/canon";
	}*/
	
	// content 본문에 removeAllTags()가 적용되는 것을 방지하기 위한 프로세스를 위한 변수.
	$contentContainer = array();

	if (file_exists(ROOT . "/skin/blog/{$skinSetting['skin']}/wysiwyg.css"))
		$adminSkinSetting['editorTemplate'] = "/skin/blog/{$skinSetting['skin']}/wysiwyg.css";
	else
		$adminSkinSetting['editorTemplate'] = "/resources/style/default-wysiwyg.css";
}
	
/** Plugin module initialization (if necessary) */ 
if(in_array($context->URLInfo['interfaceType'], array('blog','owner','reader'))) {
	require_once(ROOT.'/library/plugins.php');
}

/** Access privilege Check */
header('Content-Type: text/html; charset=utf-8');

if($context->URLInfo['interfaceType'] == 'blog' && !defined('__TEXTCUBE_LOGIN__')) {
	$blogVisibility = Setting::getBlogSettingGlobal('visibility',2);
	if($blogVisibility == 0) requireOwnership();
	else if($blogVisibility == 1) requireMembership();
}

if(in_array($context->URLInfo['interfaceType'], array('owner','reader'))) {
	requireOwnership();     // Check access control list
	require ROOT .'/library/pageACL.php';
}
?>