<?php

//disallow unauthorize access
if(!defined("IN_MYBB")) {
	die("You are not authorize to view this");
}

global $mybb;
if (isset($mybb->settings['sbrepbar_enable']) && $mybb->settings['sbrepbar_enable'] == 1) {
	$plugins->add_hook('postbit', 'sbrepbar_postbit');
	$plugins->add_hook("member_profile_end", "sbrepbar_profile");
}


//Plugin Information
function sbrepbar_info()
{

	global $mybb;

	if($mybb->settings['sbrepbar_enable'] == 1){

		$config = '<div style="float: right;"><a href="index.php?module=config-settings&action=change&search=sbrepbar" style="color:#035488; padding: 21px; text-decoration: none;">Configure</a></div>';

	}

	else {

		$config = '<div style="float: right;"><span style="color:Red; padding: 21px; text-decoration: none;">Plugin disabled</span></div>';

	}

	return array(
		'name' => 'Reputation Bar Icons',
		'author' => 'Sunil Baral',
		'website' => 'https://github.com/snlbaral',
		'description' => 'This plugin will Reputation Bar until user don\'t reply'.$config,
		'version' => '1.0',
		'compatibility' => '18*',
		'guid' => '',
	);
}

//Activate Plugin
function sbrepbar_activate()
{
	global $db, $mybb, $settings, $templates;

	//Admin CP Settings
	$sbrepbar_group = array(
		'gid' => (int)'',
		'name' => 'sbrepbar',
		'title' => 'Reputation Bar',
		'description' => 'Settings for Reputation Bar Icons',
		'disporder' => '1',
		'isdefault' =>  '0',
	);
	$db->insert_query('settinggroups',$sbrepbar_group);
	$gid = $db->insert_id();

	//Enable or Disable
	$sbrepbar_enable = array(
		'sid' => 'NULL',
		'name' => 'sbrepbar_enable',
		'title' => 'Do you want to enable this plugin?',
		'description' => 'If you set this option to yes, this plugin will start working.',
		'optionscode' => 'yesno',
		'value' => '1',
		'disporder' => 1,
		'gid' => intval($gid),
	);

	$sbrepbar_icon = array(
		'sid' => 'NULL',
		'name' => 'sbrepbar_icon',
		'title' => 'Select the icon you want to display in rep bar?',
		'description' => 'Select the type of icon you want to display in rep bar.',
		'optionscode' => "select\ndefault_image=Default Image\ncircle=Round Circle\nsquare=Square Box\nbandcamp=Bandcamp",
		'value' => 'default_image',
		'disporder' => 2,
		'gid' => intval($gid),
	);


	$sbrepbar_opticon = array(
		'sid' => 'NULL',
		'name' => 'sbrepbar_opticon',
		'title' => 'Override above selection with custom icon?',
		'description' => 'Write font awesome icon code below to have custom icon display inplace of above selected icon i.e(fa fa-fire), else leave empty.',
		'optionscode' => 'text',
		'value' => '',
		'disporder' => 3,
		'gid' => intval($gid),
	);


	$sbrepbar_negative = array(
		'sid' => 'NULL',
		'name' => 'sbrepbar_negative',
		'title' => 'Which color do you want to use as a negative rep bar?',
		'description' => 'Write down color name or hex code you want to use for reputation having less than 0.',
		'optionscode' => 'text',
		'value' => 'red',
		'disporder' => 4,
		'gid' => intval($gid),
	);

	$sbrepbar_bronze = array(
		'sid' => 'NULL',
		'name' => 'sbrepbar_bronze',
		'title' => 'Which color do you want to use as a bronze level rep bar?',
		'description' => 'Write down color name or hex code you want to use for reputation having 1-99.',
		'optionscode' => 'text',
		'value' => '#28a745',
		'disporder' => 5,
		'gid' => intval($gid),
	);

	$sbrepbar_silver = array(
		'sid' => 'NULL',
		'name' => 'sbrepbar_silver',
		'title' => 'Which color do you want to use as a silver level rep bar?',
		'description' => 'Write down color name or hex code you want to use for reputation with 100-499.',
		'optionscode' => 'text',
		'value' => 'orange',
		'disporder' => 6,
		'gid' => intval($gid),
	);

	$sbrepbar_gold = array(
		'sid' => 'NULL',
		'name' => 'sbrepbar_gold',
		'title' => 'Which color do you want to use as a gold level rep bar?',
		'description' => 'Write down color name or hex code you want to use for reputation with 500-999.',
		'optionscode' => 'text',
		'value' => '#007bff',
		'disporder' => 7,
		'gid' => intval($gid),
	);

	$db->insert_query('settings',$sbrepbar_enable);
	$db->insert_query('settings',$sbrepbar_negative);
	$db->insert_query('settings',$sbrepbar_bronze);
	$db->insert_query('settings',$sbrepbar_silver);
	$db->insert_query('settings',$sbrepbar_gold);
	$db->insert_query('settings',$sbrepbar_icon);
	$db->insert_query('settings',$sbrepbar_opticon);
	rebuild_settings();


	//Templates
	$insert_temp = array(
		'tid' => NULL,
		'title' => 'sbrepbar_index',
		'template' => $db->escape_string('
<style>
	.sbrepbar_div i {
		padding: 0px 1px;
	}
	.sbrepbar-multi.default i {
		color: silver !important;
	}
	.sbrepbar-multi.bronze i {
		color: {$bronzelvl_color} !important;
	}
	.sbrepbar-multi.silver i {
		color: {$silverlvl_color} !important;
	}
	.sbrepbar-multi.gold i {
		color: {$goldlvl_color} !important;
	}	
</style>
<link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" integrity="sha384-wvfXpqpZZVQGK6TAh5PVlGOfQNHSoD2xbE+QkPxCAFlNEevoEH3Sl0sibVcOQVnN" crossorigin="anonymous">
<div class="sbrepbar_div">
	{$sbrepbar_bar}
</div>
			'),
		'sid' => '-1',
		'version' => $mybb->version_code,
		'dateline' => time(),
	);
	$db->insert_query('templates',$insert_temp);

	require MYBB_ROOT.'/inc/adminfunctions_templates.php';
	find_replace_templatesets("member_profile", '#'.preg_quote('{$groupimage}').'#', '{$groupimage} {$memprofile[\'sbrepbar\']}');
}

//Deactivate Plugin
function sbrepbar_deactivate()
{
	global $db, $mybb;
	$db->query("DELETE from ".TABLE_PREFIX."settinggroups WHERE name IN ('sbrepbar')");
	$db->query("DELETE from ".TABLE_PREFIX."settings WHERE name IN ('sbrepbar_enable')");
	$db->query("DELETE from ".TABLE_PREFIX."settings WHERE name IN ('sbrepbar_negative')");
	$db->query("DELETE from ".TABLE_PREFIX."settings WHERE name IN ('sbrepbar_bronze')");
	$db->query("DELETE from ".TABLE_PREFIX."settings WHERE name IN ('sbrepbar_silver')");
	$db->query("DELETE from ".TABLE_PREFIX."settings WHERE name IN ('sbrepbar_gold')");
	$db->query("DELETE from ".TABLE_PREFIX."settings WHERE name IN ('sbrepbar_icon')");
	$db->query("DELETE from ".TABLE_PREFIX."templates WHERE title LIKE 'sbrepbar%'");
	rebuild_settings();

	require MYBB_ROOT.'/inc/adminfunctions_templates.php';
	find_replace_templatesets("member_profile", "#" . preg_quote("{\$memprofile['sbrepbar']}") . "#i", "", 0);
}



function get_sbrepbar($reputation)
{
	global $settings;

	$negative_color = $settings['sbrepbar_negative'];
	$bronzelvl_color = $settings['sbrepbar_bronze'];
	$silverlvl_color = $settings['sbrepbar_silver'];
	$goldlvl_color = $settings['sbrepbar_gold'];

	if($reputation<0) {
		$sbrepbar_style = "color: ".$negative_color;
		$default_image_src = "images/rep/rep_neg.png";
	} elseif ($reputation==0) {
		$sbrepbar_style = "color: silver";
		$default_image_src = "images/rep/rep_neu.png";
	} elseif ($reputation<100) {
		$sbrepbar_style = "color: ".$bronzelvl_color;
		$default_image_src = "images/rep/rep_bronze.png";
	} elseif ($reputation<500) {
		$sbrepbar_style = "color: ".$silverlvl_color;
		$default_image_src = "images/rep/rep_silver.png";
	} elseif ($reputation<1000) {
		$sbrepbar_style = "color: ".$goldlvl_color;
		$default_image_src = "images/rep/rep_gold.png";
	} else {
	 	//multicolor //on line 326
		$sbrepbar_style = "color: silver";
		$default_image_src = "images/rep/rep_neu.png";
	}


	if($settings['sbrepbar_opticon']==NULL) {
		if($settings['sbrepbar_icon']=="default_image")
		{
			$sbrepbar_icon = '<img src="'.$default_image_src.'"/>';
		}
		elseif ($settings['sbrepbar_icon']=="circle") {
			$sbrepbar_icon = '<i class="fa fa-circle" style="'.$sbrepbar_style.'"></i>';
		}
		elseif ($settings['sbrepbar_icon']=="square") {
			$sbrepbar_icon = '<i class="fa fa-square" style="'.$sbrepbar_style.'"></i>';
		}
		elseif ($settings['sbrepbar_icon']=="bandcamp") {
			$sbrepbar_icon = '<i class="fa fa-bandcamp" style="'.$sbrepbar_style.'"></i>';
		}
		else {
			$sbrepbar_icon = '<i class="fa fa-square" style="'.$sbrepbar_style.'"></i>';
		}
	} else {
		$sbrepbar_icon = '<i class="'.$settings['sbrepbar_opticon'].'"></i>';
	}


	switch (true) {

		case $reputation<=0:
			$sbrepbar_bar = $sbrepbar_icon;
			break;
		case $reputation<100:
			if($reputation>0 && $reputation<15) {
				$sbrepbar_bar = $sbrepbar_icon;
			} elseif ($reputation<30) {
				$sbrepbar_bar = str_repeat($sbrepbar_icon, 2);
			} elseif ($reputation<45) {
				$sbrepbar_bar = str_repeat($sbrepbar_icon, 3);
			} elseif ($reputation<60) {
				$sbrepbar_bar = str_repeat($sbrepbar_icon, 4);
			} elseif ($reputation<75) {
				$sbrepbar_bar = str_repeat($sbrepbar_icon, 5);
			} elseif ($reputation<90) {
				$sbrepbar_bar = str_repeat($sbrepbar_icon, 6);
			} elseif ($reputation<100) {
				$sbrepbar_bar = str_repeat($sbrepbar_icon, 7);
			} else {
				$sbrepbar_bar = $sbrepbar_icon;
			}
			break;
		
		case $reputation<500:
			if($reputation<140) {
				$sbrepbar_bar = $sbrepbar_icon;
			} elseif ($reputation<210) {
				$sbrepbar_bar = str_repeat($sbrepbar_icon, 2);
			} elseif ($reputation<280) {
				$sbrepbar_bar = str_repeat($sbrepbar_icon, 3);
			} elseif ($reputation<350) {
				$sbrepbar_bar = str_repeat($sbrepbar_icon, 4);
			} elseif ($reputation<420) {
				$sbrepbar_bar = str_repeat($sbrepbar_icon, 5);
			} elseif ($reputation<490) {
				$sbrepbar_bar = str_repeat($sbrepbar_icon, 6);
			} elseif ($reputation<500) {
				$sbrepbar_bar = str_repeat($sbrepbar_icon, 7);
			} else {
				$sbrepbar_bar = $sbrepbar_icon;
			}
			break;

		case $reputation<1000:
			if($reputation<580) {
				$sbrepbar_bar = $sbrepbar_icon;
			} elseif ($reputation<660) {
				$sbrepbar_bar = str_repeat($sbrepbar_icon, 2);
			} elseif ($reputation<740) {
				$sbrepbar_bar = str_repeat($sbrepbar_icon, 3);
			} elseif ($reputation<820) {
				$sbrepbar_bar = str_repeat($sbrepbar_icon, 4);
			} elseif ($reputation<900) {
				$sbrepbar_bar = str_repeat($sbrepbar_icon, 5);
			} elseif ($reputation<980) {
				$sbrepbar_bar = str_repeat($sbrepbar_icon, 6);
			} elseif ($reputation<1000) {
				$sbrepbar_bar = str_repeat($sbrepbar_icon, 7);
			} else {
				$sbrepbar_bar = $sbrepbar_icon;
			}
			break;

		case $reputation>=1000:
			if($settings['sbrepbar_opticon']==NULL && $settings['sbrepbar_icon']=="default_image") {
				if($reputation<1100) {
					$sbrepbar_bar = str_repeat('<img src="images/rep/rep_neu.png"/>', 3).str_repeat('<img src="images/rep/rep_bronze.png"/>', 3).str_repeat('<img src="images/rep/rep_silver.png"/>', 3);
				} else {
					$sbrepbar_bar = str_repeat('<img src="images/rep/rep_neu.png"/>', 3).str_repeat('<img src="images/rep/rep_bronze.png"/>', 3).str_repeat('<img src="images/rep/rep_silver.png"/>', 3).str_repeat('<img src="images/rep/rep_gold.png"/>', 3);
				}

			} else {
				if($reputation<1100) {
					$sbrepbar_bar = '<span class="sbrepbar-multi default">'.$sbrepbar_icon.'</span><span class="sbrepbar-multi bronze">'.str_repeat($sbrepbar_icon, 2).'</span><span class="sbrepbar-multi silver">'.str_repeat($sbrepbar_icon, 2).'</span>';
				} else {
					$sbrepbar_bar = '<span class="sbrepbar-multi default">'.$sbrepbar_icon.'</span><span class="sbrepbar-multi bronze">'.str_repeat($sbrepbar_icon, 2).'</span><span class="sbrepbar-multi silver">'.str_repeat($sbrepbar_icon, 2).'</span><span class="sbrepbar-multi gold">'.str_repeat($sbrepbar_icon, 2).'</span>';
				}
			}
			break;			
		
		default:
			$sbrepbar_bar = $sbrepbar_icon;
			break;
	}

	return $sbrepbar_bar;

}


function sbrepbar_postbit(&$post)
{
	global $db, $mybb, $settings, $templates, $sbrepbar_bar;

	$sbrepbar_bar = get_sbrepbar((int)$post['reputation']);

	//Multi Color
	$negative_color = $settings['sbrepbar_negative'];
	$bronzelvl_color = $settings['sbrepbar_bronze'];
	$silverlvl_color = $settings['sbrepbar_silver'];
	$goldlvl_color = $settings['sbrepbar_gold'];


	eval("\$post['sbrepbar'] = \"".$templates->get('sbrepbar_index')."\";");

	//Position Rep bar right below reputation
	$temparr = explode("<br />", $post['user_details']);
	$pos = sizeof($temparr);
	foreach ($temparr as $key => $value) {
		if(strpos($value, "Reputation") !== false) {
			$pos = $key+1;
		}
	}
	array_splice($temparr, $pos, 0, $post['sbrepbar']);
	$post['user_details'] = implode("<div></div>", $temparr);

}

function sbrepbar_profile()
{
	global $db, $mybb, $settings, $templates, $memprofile;

	//Multi Color
	$negative_color = $settings['sbrepbar_negative'];
	$bronzelvl_color = $settings['sbrepbar_bronze'];
	$silverlvl_color = $settings['sbrepbar_silver'];
	$goldlvl_color = $settings['sbrepbar_gold'];

	$sbrepbar_bar = get_sbrepbar((int)$memprofile['reputation']);

	eval("\$memprofile['sbrepbar'] = \"".$templates->get('sbrepbar_index')."\";");

}