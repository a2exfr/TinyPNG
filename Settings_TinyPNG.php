<?php
/*
Author: a2exfr
http://my-sitelab.com/
 */

defined('is_running') or die('Not an entry point...');

class Settings_TinyPNG{

	private $opts;

	function __construct(){
		gpPlugin::css('/css/tiny_png.css');
		$this->loadConfig();

		$cmd = common::GetCommand();

		switch ($cmd) {
			case 'saveConfig':
				$this->saveConfig();
				break;
		}
		$this->loadConfig();
		if ($this->opts['apikey'] == ""){
			$this->Show_Api_info();
		}
		$this->Show_Form();
	}

	function Show_Api_info(){
		echo '<div class="warning_images">  <i class="fa fa-warning"></i> <a href="https://tinypng.com/developers" target="_blank"> Get API key </a></div>';
	}

	function Show_Form(){
		global $langmessage;

		echo '<h3>TinyPNG settings page</h3>';
		echo '<a class="gpbutton" style="float:right;" href ="' . common::GetUrl('Admin_TinyPNG') . '" ><i  class="fa fa-cogs" ></i> Back to TinyPNG</a> ';
		echo '<form action="' . common::GetUrl('Admin_TinyPNG_options') . '" method="post">';
		echo '<table  class="bordered full_width">
			<thead>
			<tr>
				<th>Option</th>
				<th>Value</th>

			</tr>
			</thead>
			 <tbody> ';

		echo '<tr><td>';
		echo '<p>TinyPNG <b>API key</b></p>';
		echo '</td>';
		echo '<td>';
		echo '<input   name="opts[apikey]" value="' . $this->opts["apikey"] . '" class="gpinput" style="width:300px" />';
		echo '</td></tr>';

		echo '<tr><td>';
		echo '<p>Include thumbnails folder?</p>';
		echo '</td>';
		echo '<td>';
		$ch = ($this->opts['thumb'] == "") ? "" : "checked";
		echo '<input   name="opts[thumb]" value="yes" type="checkbox" ' . $ch . '/>';
		echo '</td></tr>';

		echo '</tbody> </table> ';
		echo '<p>';
		echo '<input type="hidden" name="cmd" value="saveConfig" />';
		echo '<input type="submit" value="' . $langmessage['save_changes'] . '" class="gpsubmit"/>';
		echo '</p>';
		echo '</form>';

	}


	function loadConfig(){
		global $addonPathData;
		$configFile = $addonPathData . '/settings.php';
		$this->opts = gpFiles::Get($configFile, 'settings');
		$vals = [0 => 'apikey', 1 => 'thumb'];
		foreach ($vals as $val) {
			if (!array_key_exists($val, $this->opts)){
				$this->opts[$val] = "";
			}
		}
		return $this->opts;
	}

	function saveConfig(){
		global $addonPathData, $langmessage;
		$configFile = $addonPathData . '/settings.php';
		$settings = $_REQUEST['opts'];
		if (!gpFiles::SaveData($configFile, 'settings', $settings)){
			message($langmessage['OOPS']);
			return false;
		}

		message($langmessage['SAVED']);
		return true;
	}


}