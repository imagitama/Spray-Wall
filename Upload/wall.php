<?php
/**
 * Spray Wall 1.0
 * Users can spray short messages to a wall.
 * 
 * Page: Store frontend & backend.
 *  
 * By Jared Williams
 * Copyright 2012
 * 
 * Website: http://www.jaredwilliams.com.au
 *  
 * Please do not redistribute or sell this plugin.
 */

define("IN_MYBB", 1);
require_once "global.php";


$lang->load("spraywall");


//If the plugin is ready...
if (!function_exists('spraywall_activate')) {
	die('Plugin has not been activated! Please contact your administrator!');
}

//If allowed groups are set and you're not in them OR if in group not allowed...
if (($mybb->settings['spraywall_allowed_usergroups'] && !spraywall_is_in_group('allowed')) || spraywall_is_in_group('disallowed')) {
	#error($lang->error_not_allowed);
	error_no_permission();
}


//If the plugin is ready...
//if (!function_exists('spraywall_activate')) {
//	die('Plugin has not been activated! Please contact your administrator!');
//}


$spray = spraywall_get_spray($mybb->input['sprayid']);


add_breadcrumb($lang->title_root, 'wall.php');


//****************************************************[ ADD SPRAY ]
if ($mybb->input['action'] == 'spray') {
	//If new sprays are permitted...
	if ($mybb->settings['spraywall_can_spray']) {
		//If a spray was actually provided...
		if ($mybb->input['message'] != '') {
			add_breadcrumb($lang->title_spray_success, 'wall.php');

			//Delete old sprays (if set)...
			if (intval($mybb->settings['spraywall_spray_limit']) > 0) {
				spraywall_delete_old_sprays();
			}

			//Trim...
			if ($trimto = intval($mybb->settings['spraywall_message_length'])) {
				$message = substr($mybb->input['message'], 0, $trimto);
			} else {
				$message = $mybb->input['message'];
			}

			//Insert...
			spraywall_insert_spray($message);
			
			if ($mybb->settings['spraywall_need_approval'] != '1') {
				spraywall_add_notification('added a spray to the wall');
			}

			redirect('wall.php',$lang->msg_spray_success);
		} else {
			error($lang->error_no_message);
		}
	} else {
		error($lang->error_cant_spray);
	}
}

//*************************************************[ REMOVE SPRAY ]
if ($mybb->input['action'] == 'remove') {
	if (spraywall_is_in_group('mod')) {
		if ($spray) {
			add_breadcrumb($lang->title_spray_removed, 'wall.php');
			
			spraywall_delete_spray($spray['sprayid']);

			redirect('wall.php',$lang->msg_spray_removed);
		} else {
			error($lang->error_no_spray);
		}
	} else {
		#error($lang->error_not_allowed);
		error_no_permission();
	}
}

//************************************************[ APPROVE SPRAY ]
if ($mybb->input['action'] == 'approve') {
	if (spraywall_is_in_group('mod')) {
		if ($spray) {
			if ($spray['status'] == '0') {
				//add_breadcrumb($lang->title_spray_approved, 'wall.php');

				spraywall_approve_spray($spray['sprayid']);

				redirect('wall.php',$lang->msg_spray_approved);
			} else {
				error($lang->error_already_approved);
			}
		} else {
			error($lang->error_no_sprayid);
		}
	} else {
		#error($lang->error_not_allowed);
		error_no_permission();
	}
}

//*********************************************[ DISAPPROVE SPRAY ]
if ($mybb->input['action'] == 'disapprove') {
	if (spraywall_is_in_group('mod')) {
		if ($spray) {
			if ($spray['status'] == '0') {
				//add_breadcrumb($lang->title_spray_approved, 'wall.php');

				spraywall_disapprove_spray($spray['sprayid']);

				redirect('wall.php',$lang->msg_spray_disapproved);
			} else {
				error($lang->error_already_approved);
			}
		} else {
			error($lang->error_no_spray);
		}
	} else {
		#error($lang->error_not_allowed);
		error_no_permission();
	}
}

//***************************************************[ CLEAR WALL ]
if ($mybb->input['action'] == 'clear') {
	if (spraywall_is_in_group('mod')) {
		add_breadcrumb($lang->title_wall_cleared, 'wall.php');
			
		spraywall_clear_wall();

		redirect('wall.php',$lang->msg_wall_cleared);
	} else {
		#error($lang->error_not_allowed);
		error_no_permission();
	}
}

//****************************************************[ SHOW WALL ]
if ($mybb->input['action'] == '') {
	//Get sprays...
	$query = $db->simple_select("spraywall", "*", "`status` = '1'"); //Only visible sprays
	if ($db->num_rows($query) > 0) {
		while ($spray = $db->fetch_array($query)) {
			//Fill vars...
			$sprayid = $spray['sprayid'];
			$message = strip_tags($spray['message']);
			$style = 
				'position:'.			'absolute;'.
				'top:'.						$spray['posY'].';'.
				'left:'.					$spray['posX'].';'.			
				'font-family:'.		$spray['fontfamily'].';'.
				'font-size:'.			$spray['fontsize'].';'.
				'font-weight:'.		$spray['fontweight'].';'.
				'color:'.					$spray['fontcolor'].';';
			
			eval("\$messages .= \"".$templates->get('spraywall_message')."\";");
		}
	} else {
		//No sprays!
	}

	//Get user controls...
	eval("\$controls = \"".$templates->get('spraywall_user_controls')."\";");
	
	//Get moderator controls...
	if (spraywall_is_in_group('mod')) {
		eval("\$controls .= \"".$templates->get('spraywall_mod_controls')."\";");
		
		//If sprays need approving...
		if ($mybb->settings['spraywall_need_approval'] == '1') {
			//Get them...
			$unapproved = spraywall_get_unapproved();
			if ($unapproved) {
				//List them...
				foreach ($unapproved as $spray) {
					//$sprayer = '<a href=\"".get_profile_link($user[\'uid\'])."\">';
					$username = format_name($spray['username'], $spray['usergroup'], $spray['displaygroup']);
					$message = strip_tags($spray['message']);
					$whensprayed = my_date('F j, Y, g:i a', $spray['dateadded']);
					$sprayer = build_profile_link($username, $spray['uid']);

					eval("\$unapprovedsprays .= \"".$templates->get('spraywall_unapproved_spray')."\";");
				}
			} else {
				eval("\$unapprovedsprays .= \"".$templates->get('spraywall_unapproved_spray_none')."\";");
			}
			
			eval("\$controls .= \"".$templates->get('spraywall_mod_controls_unapprove')."\";");
		}
	}

	eval("\$wall = \"".$templates->get('spraywall_view')."\";");

	output_page($wall);
}
?>