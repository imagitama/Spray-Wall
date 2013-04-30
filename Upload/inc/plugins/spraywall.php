<?php
/**
 * Spray Wall 1.0
 * Users can spray short messages to a wall.
 *  
 * By Jared Williams
 * Copyright 2012
 * 
 * Website: http://www.jaredwilliams.com.au
 *  
 * Please do not redistribute or sell this plugin.
 */


//This file should be used as a plugin!
if(!defined("IN_MYBB")) {
	die("This file cannot be accessed directly.");
}


//Make the function available to all
//$plugins->add_hook('global_start',		'spraywall_generate_dropdown');


//FUNCTION: Plugin info
function spraywall_info() {
	return array(
		"name"					=> "Spray Wall",
		"description"			=> "Users can spray short messages to a wall.",
		"author"				=> "Jared Williams",
		"authorsite"			=> "http://www.jaredwilliams.com.au/",
		"version"				=> "1.0",
		"compatibility"			=> "6",
		"guid"					=> "9d67bafa5bc3ca4fa0f94496154830d4"
	);
}


//FUNCTION: Is it installed
function spraywall_is_installed() {
	global $mybb, $db;
	
	//TODO: Use wildcard!
	$tables = array(
		'spraywall'
	);

	//Loop through all tables and if one exists, it is installed...
	foreach ($tables as $tablename) {
		if ($db->table_exists($tablename)) {
			return true;
		}
	}
	
	return false;
}
 

//FUNCTION: Install the plugin
function spraywall_install() {
	global $mybb, $db;
	
	$collation = $db->build_create_table_collation();
	
	//Create our notifications table if it does not exist...
	//TODO: Use spraywall_ prefix!
	if(!$db->table_exists('spraywall')) {
		$db->write_query("CREATE TABLE `".TABLE_PREFIX."spraywall` (
			`sprayid` int(10) UNSIGNED NOT NULL auto_increment,
			`uid` int(10) NOT NULL default 0,
			`message` varchar(1024) NOT NULL default '',
			`posX` varchar(100) NOT NULL default '',
			`posY` varchar(100) NOT NULL default '',
			`fontfamily` varchar(100) NOT NULL default '',
			`fontsize` varchar(100) NOT NULL default '',
			`fontweight` varchar(100) NOT NULL default 0,
			`fontcolor` varchar(100) NOT NULL default '',
			`status` int(10) NOT NULL default 1,
			`dateadded` int(10) NOT NULL default 0,
			PRIMARY KEY  (`sprayid`)
		) ENGINE=MyISAM{$collation}");
	}
	
	//Add templates...
	$templates = array(
		//TEMPLATE: view
		'spraywall_view'			=> '
<html>
	<head>
		<title>{$lang->title_root}</title>
		{$headerinclude}
		
		<style type="text/css">
			.wall {
				width: 100%;
				height: 500px;
				position: relative;
				background: url(images/wall.jpg) repeat;
			}
		</style>
	</head>
	<body>
		{$header}
		
		<div class="wall">
			{$messages}
		</div>
		
		{$controls}

		{$footer}
	</body>
</html>',
			
		//TEMPLATE: Messages
		'spraywall_message'				=> '
<div id="spray_{$sprayid}" class="message" style="{$style}">
	{$message}
</div>',
			
		'spraywall_user_controls' => '
<br />
<table width="100%" class="tborder">
	<tr>
		<td class="thead" colspan="4">
			{$lang->title_user_controls}
		</td>
	</tr>
	<tr>
		<td width="100%" class="trow1">
			<form action="wall.php" method="post">
				<input type="hidden" name="action" value="spray" />
				<input name="message" type="text" value="" placeholder="Spray message" /><input type="submit" value="{$lang->button_add}" />
			</form>
		</td>
	</tr>
</table>',
			
		'spraywall_mod_controls' => '
<br />
<table width="100%" class="tborder">
	<tr>
		<td class="thead" colspan="4">
			{$lang->title_mod_controls}
		</td>
	</tr>
	<tr>
		<td width="100%" class="trow1">
			<form action="wall.php" method="post">
				<input type="hidden" name="action" value="remove" />
				<input name="sprayid" type="text" value="" placeholder="Spray ID" /><input type="submit" value="{$lang->button_remove}" />
			</form>
		</td>
	</tr>
	<tr>
		<td width="100%" class="trow1">
			<form action="wall.php" method="post">
				<input type="hidden" name="action" value="clear" />
				Clear wall of sprays (this cannot be undone) <input type="submit" value="{$lang->button_clear}" />
			</form>
		</td>
	</tr>
</table>',
			
			'spraywall_mod_controls_unapprove' => '
<br />
<table width="100%" class="tborder">
	<tr>
		<td class="thead" colspan="10">
			{$lang->title_unapproved_list}
		</td>
	</tr>
	{$unapprovedsprays}
</table>',
			
			'spraywall_unapproved_spray' => '
<tr>
	<td width="15%" class="trow1">
		{$sprayer}
	</td>
	<td width="60%" class="trow1">
		{$message}
	</td>
	<td width="15%" class="trow1">
		{$whensprayed}
	</td>
	<td width="5%" class="trow1">
		<form action="wall.php" method="post">
			<input type="hidden" name="action" value="approve" />
			<input type="hidden" name="sprayid" value="{$spray[\'sprayid\']}" />
			<input type="submit" value="{$lang->button_approve}" />
		</form>
	</td>
	<td width="5%" class="trow1">
		<form action="wall.php" method="post">
			<input type="hidden" name="action" value="disapprove" />
			<input type="hidden" name="sprayid" value="{$spray[\'sprayid\']}" />
			<input type="submit" value="{$lang->button_disapprove}" />
		</form>
	</td>
</tr>',
			
					
			'spraywall_unapproved_spray_none' => '
<tr>
	<td class="trow1" style="text-align: center">
		No sprays need approving
	</td>
</tr>'
	);
	
	//Insert templates...
	foreach ($templates as $title => $data) {
		$insert = array(
			'title' => $db->escape_string($title),
			'template' => $db->escape_string($data),
			'sid' => "-1",
			'version' => '1',
			'dateline' => TIME_NOW
		);
		$db->insert_query('templates', $insert);
	}
	
	
	
	//Insert a new settings group...
	$insertarray = array(
		'name' => 'spraywall',
		'title' => 'Spray Wall',
		'description' => 'Settings for Spray Wall.',
		'disporder' => '70',
		'isdefault' => 0
	);
	$group['gid'] = $db->insert_query("settinggroups", $insertarray);
	
	$insertarray = array();
	
	//SETTINGS: Access
	$insertarray[] = array(
		'name' => 'spraywall_access',
		'title' => 'Enable wall access',
		'description' => $db->escape_string('Toggle if the wall can be viewed and moderated.'),
		'optionscode' => 'yesno',
		'value' => '1',
		'disporder' => 1,
		'gid' => $group['gid']
	);
	
	$insertarray[] = array(
		'name' => 'spraywall_can_spray',
		'title' => 'Allow sprays',
		'description' => $db->escape_string('Toggle if all users can spray to the wall.'),
		'optionscode' => 'yesno',
		'value' => '1',
		'disporder' => 2,
		'gid' => $group['gid']
	);
	
	$insertarray[] = array(
		'name' => 'spraywall_need_approval',
		'title' => 'Approve new sprays',
		'description' => $db->escape_string('Toggle if new sprays must be approved by a moderator first.'),
		'optionscode' => 'yesno',
		'value' => '0',
		'disporder' => 3,
		'gid' => $group['gid']
	);
	
	//SETTINGS: Groups
	$insertarray[] = array(
		'name' => 'spraywall_mod_usergroups',
		'title' => 'Moderator usergroups',
		'description' => $db->escape_string('Usergroup IDs that can moderate messages.'),
		'optionscode' => 'text',
		'value' => '3,4,6', //Admins, Super Mods, Mods
		'disporder' => 10,
		'gid' => $group['gid']
	);
	
	$insertarray[] = array(
		'name' => 'spraywall_allowed_usergroups',
		'title' => 'Allowed usergroups',
		'description' => $db->escape_string('Usergroup IDs that can access the wall (leave blank to allow all). Disallowed setting has precedence.'),
		'optionscode' => 'text',
		'value' => '', //All
		'disporder' => 11,
		'gid' => $group['gid']
	);
	
	$insertarray[] = array(
		'name' => 'spraywall_disallowed_usergroups',
		'title' => 'Disallowed usergroups',
		'description' => $db->escape_string('Usergroup IDs that cannot access the wall (leave blank to allow all, default banned).'),
		'optionscode' => 'text',
		'value' => '7', //Banned
		'disporder' => 12,
		'gid' => $group['gid']
	);
	
	//SETTINGS: Limits
	$insertarray[] = array(
		'name' => 'spraywall_spray_limit',
		'title' => 'Spray limit',
		'description' => $db->escape_string('Limit for messages for each user (low number recommended).'),
		'optionscode' => 'text',
		'value' => '1',
		'disporder' => 15,
		'gid' => $group['gid']
	);
	
	$insertarray[] = array(
		'name' => 'spraywall_flood_control',
		'title' => 'Flood control timer',
		'description' => $db->escape_string('Wait time inbetween spraying messages (0 for no flood control).'),
		'optionscode' => 'text',
		'value' => '0', //No flood control
		'disporder' => 16,
		'gid' => $group['gid']
	);
	
	$insertarray[] = array(
		'name' => 'spraywall_message_length',
		'title' => 'Max message length',
		'description' => $db->escape_string('Maximum number of characters allowed per message.'),
		'optionscode' => 'text',
		'value' => '64',
		'disporder' => 17,
		'gid' => $group['gid']
	);
	
	//SETTINGS: Styles
	$insertarray[] = array(
		'name' => 'spraywall_font_families',
		'title' => 'Font families',
		'description' => $db->escape_string('List of font families that can be used.'),
		'optionscode' => 'textarea',
		'value' => 'Arial,Verdana,Tahoma,Times New Roman',
		'disporder' => 20,
		'gid' => $group['gid']
	);
	
	$insertarray[] = array(
		'name' => 'spraywall_font_sizes',
		'title' => 'Font sizes',
		'description' => $db->escape_string('List of font sizes that can be used (integer percentages, eg 100, 200, 300).'),
		'optionscode' => 'text',
		'value' => '100,120,140,160,180,200',
		'disporder' => 20,
		'gid' => $group['gid']
	);
	
	$insertarray[] = array(
		'name' => 'spraywall_font_colors',
		'title' => 'Font colors',
		'description' => $db->escape_string('List of font colors that can be used (leave blank for all, accepts 6 digit HEX without hash).'),
		'optionscode' => 'text',
		'value' => '',
		'disporder' => 20,
		'gid' => $group['gid']
	);
	
	$insertarray[] = array(
		'name' => 'spraywall_font_weights',
		'title' => 'Font weights',
		'description' => $db->escape_string('List of font weights that can be used (normal and bold only recommended).'),
		'optionscode' => 'text',
		'value' => 'Normal,Bold',
		'disporder' => 20,
		'gid' => $group['gid']
	);
	
	$insertarray[] = array(
		'name' => 'spraywall_left_limits',
		'title' => 'Horizontal positioning',
		'description' => $db->escape_string('Minimum and maximum for horizontal spray positioning (0-100,0-100).'),
		'optionscode' => 'text',
		'value' => '5,80',
		'disporder' => 25,
		'gid' => $group['gid']
	);
	
	$insertarray[] = array(
		'name' => 'spraywall_top_limits',
		'title' => 'Vertical positioning',
		'description' => $db->escape_string('Minimum and maximum for vertical spray positioning (0-100,0-100).'),
		'optionscode' => 'text',
		'value' => '5,80',
		'disporder' => 25,
		'gid' => $group['gid']
	);
	
	//Insert the settings...
	foreach ($insertarray as $settingarray) {
		$db->insert_query("settings", $settingarray);
	}
	
	//Update all settings...
	rebuild_settings();
}


//FUNCTION: Uninstall the plugin
function spraywall_uninstall() {
	global $mybb, $db;
	
	//Deactivate just to be sure...
	spraywall_deactivate();
	
	//Remove all settings from the database...
	$db->delete_query("settings", "name LIKE 'spraywall'");
	$db->delete_query("settinggroups", "name = 'spraywall'");

	//Update the settings...
	rebuild_settings();

	//TODO: Use wildcard!
	$tables = array(
		'spraywall'
	);

	//Drop tables if they exist...
	foreach ($tables as $tablename) {
		if ($db->table_exists($tablename)) {
			$db->drop_table($tablename);
		}
	}
	
	//Remove all other templates...
	$db->delete_query("templates", "`title` LIKE 'spraywall%'");
}


//FUNCTION: Activate the plugin
function spraywall_activate() {
	global $mybb, $db;
	
	//Deactivate it first so we start fresh...
	prostore_deactivate();
	
	//Add the variable to templates...
	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
	#find_replace_templatesets("header", '#{\$pm_notice}#', "{\$spraywall}\n{\$pm_notice}");
}


//FUNCTION: Deactivate the plugin
function spraywall_deactivate() {
	global $mybb, $db;
	
	//Remove the variable from templates...
	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
	#find_replace_templatesets("header", '#{\$spraywall}(\n?)#', '', 0);
}


//FUNCTION: Get if in a group
function spraywall_is_in_group($Group, $User=array()) {
	global $mybb, $db;
	
	switch ($Group) {
		case 'mod':
			$groupids = $mybb->settings['spraywall_mod_usergroups'];
			break;
		case 'allowed':
			$groupids = $mybb->settings['spraywall_allowed_usergroups'];
			break;
		case 'disallowed':
			$groupids = $mybb->settings['spraywall_disallowed_usergroups'];
			break;
		default:
			
	}
	
	$groupids = explode(',', $groupids);
	
	//If we're being passed an array...
	if ($User['usergroup'] || $User['additionalgroups']) {
		$usergroup = $User['usergroup'];
		$additionalgroups = explode(',', $User['additionalgroups']);
	} else {
		$usergroup = $mybb->user['usergroup'];
		$additionalgroups = explode(',', $mybb->user['additionalgroups']);
	}
	
	//Check if they are allowed...
	if (in_array($usergroup, $groupids)) {
		return true;
	} else {
		foreach ($additionalgroups as $gid) {
			if (in_array($gid, $groupids)) {
				return true;
			}
		}
	}
	
	return false;
}


//FUNCTION: Generates styling for sprays
function spraywall_generate_style() {
	global $mybb, $db;
	
	$tops				=	explode(',', $mybb->settings['spraywall_top_limits']);
	$lefts			=	explode(',', $mybb->settings['spraywall_left_limits']);
	$families		=	explode(',', $mybb->settings['spraywall_font_families']);
	$sizes			= explode(',', $mybb->settings['spraywall_font_sizes']);
	$weights		= explode(',', $mybb->settings['spraywall_font_weights']);
	
	
	//If to use provided colors...
	if ($colors = $mybb->settings['spraywall_font_colors']) {
		$colors			= explode(',', $colors);
		$color = $colors[array_rand($colors)];
	} else {
		//Generate random HEX color
		$rand = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'a', 'b', 'c', 'd', 'e', 'f');
		$color = $rand[rand(0,15)].$rand[rand(0,15)].$rand[rand(0,15)].$rand[rand(0,15)].$rand[rand(0,15)].$rand[rand(0,15)];
	}
	
	$top = rand($tops[0],$tops[1]);
	$left = rand($lefts[0],$lefts[1]);
	
	return array(
		'top'				=> $top.'%',
		'left'			=> $left.'%',
		'family'		=> $families[array_rand($families)],
		'size'			=> $sizes[array_rand($sizes)].'%',
		'weight'		=> $weights[array_rand($weights)],
		'color'			=> '#'.$color
	);
}


//FUNCTION: Insert a new spray.
function spraywall_insert_spray($Message) {
	global $mybb, $db;
	
	//Generate a style...
	$font = spraywall_generate_style();
	
	//If sprays require approval...
	if ($mybb->settings['spraywall_need_approval'] == '1') {
		$status = '0';
	} else {
		$status = '1';
	}

	//Insert the spray...
	$insertspray = array(
		'uid'					=> $mybb->user['uid'],
		'message'			=> $db->escape_string($Message),
		'posX'				=> $db->escape_string($font['left']),
		'posY'				=> $db->escape_string($font['top']),
		'fontfamily'	=> $db->escape_string($font['family']),
		'fontsize'		=> $db->escape_string($font['size']),
		'fontweight'	=> $db->escape_string($font['weight']),
		'fontcolor'		=> $db->escape_string($font['color']),
		'status'			=> $status,
		'dateadded'		=> time()
	);
	$lastsprayid = $db->insert_query("spraywall", $insertspray);
	return $lastsprayid;
}


//FUNCTION: Deletes a spray.
function spraywall_delete_spray($SprayID) {
	global $mybb, $db;

	if ($SprayID = intval($SprayID)) {	
		$db->query("DELETE FROM `".TABLE_PREFIX."spraywall` WHERE `sprayid` = '{$SprayID}'");
		return true;
	} else {
		return false;
	}
}


//FUNCTION: Approves a spray.
function spraywall_approve_spray($SprayID) {
	global $mybb, $db;

	if ($SprayID = intval($SprayID)) {	
		$db->query("UPDATE `".TABLE_PREFIX."spraywall` SET `status` = '1' WHERE `sprayid` = '{$SprayID}'");
		return true;
	} else {
		return false;
	}
}


//FUNCTION: Disapproves a spray.
function spraywall_disapprove_spray($SprayID) {
	global $mybb, $db;

	if ($SprayID = intval($SprayID)) {	
		//$db->query("UPDATE `".TABLE_PREFIX."spraywall` SET `status` = '0' WHERE `sprayid` = '{$SprayID}'");
		spraywall_delete_spray($SprayID);
		return true;
	} else {
		return false;
	}
}


//FUNCTION: Deletes a user's old sprays.
function spraywall_delete_old_sprays($UserID=0) {
	global $mybb, $db;
	
	//Clean up...
	$UserID = intval($UserID);
	
	//If nothing is passed, assume to delete all of current users sprays... 
	if (!$UserID || $UserID == 0) {
		$UserID = $mybb->user['uid'];
	}
	
	$where = "WHERE `uid` = '{$UserID}'";
	
	//If to limit...
	if (intval($mybb->settings['spraywall_spray_limit']) > 0) {
		//$where .= " ORDER BY `dateadded` DESC LIMIT 1"; //18446744073709551615 //".intval($mybb->settings['spraywall_spray_limit']).", 
	}
	
	//Delete!
	$db->query("DELETE FROM `".TABLE_PREFIX."spraywall` ".$where);
}


//FUNCTION: Deletes all sprays.
function spraywall_clear_wall() {
	global $mybb, $db;
	
	//Delete!
	$db->query("DELETE FROM `".TABLE_PREFIX."spraywall`");
	return true;
}


//FUNCTION: Gets spray info.
function spraywall_get_spray($SprayID) {
	global $mybb, $db;
	
	//Sanatise...
	if ($SprayID = intval($SprayID)) {
		$query = $db->simple_select("spraywall", "*", "`sprayid` = '{$SprayID}'", array('limit' => 1));
		$spray = $db->fetch_array($query);

		if ($spray['sprayid']) {
			return $spray;
		}
	}
	
	return false;
}


//FUNCTION: Gets unapproved sprays and returns as raw array.
function spraywall_get_unapproved() {
	global $mybb, $db;
	
	//$query = $db->simple_select("spraywall", "*", "`status` = '0'");
	$query = $db->query("SELECT * FROM `".TABLE_PREFIX."spraywall` spray
		LEFT JOIN `".TABLE_PREFIX."users` user ON user.`uid` = spray.`uid`
		WHERE spray.`status` = '0'");

	if ($db->num_rows($query) > 0) {
		$unapproved = array();
		while ($spray = $db->fetch_array($query)) {
			$unapproved[] = $spray;
		}
		return $unapproved;
	}
	
	return false;
}


//FUNCTION: Add notification
function spraywall_add_notification($Message) {
	if (function_exists('proactivity_log_action')) {
		proactivity_log_action('', '', 'spray_wall', '', '', $Message);
	}
}
?>