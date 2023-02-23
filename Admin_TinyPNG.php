<?php
/*
Author: a2exfr
http://my-sitelab.com/
 */

defined('is_running') or die('Not an entry point...');

class Admin_TinyPNG{

	private $PAU;
	private $opts;
	private $free_images;
	private $all_files;

	function __construct(){
		$this->PAU = "Admin_TinyPNG";
		$this->loadConfig();
		global $page, $addonRelativeCode, $config;
		gpPlugin::js('/js/jquery.tablesorter.js');
		gpPlugin::js('/js/tiny_png.js');
		gpPlugin::css('/css/tiny_png.css');

		require_once("lib/Tinify/Exception.php");
		require_once("lib/Tinify/ResultMeta.php");
		require_once("lib/Tinify/Result.php");
		require_once("lib/Tinify/Source.php");
		require_once("lib/Tinify/Client.php");
		require_once("lib/Tinify.php");

		if ($this->opts['apikey'] == ""){
			msg('Api key needed to compress images. Redirect to  <a href="' . common::GetUrl('Admin_TinyPNG_options') . '">settings page</a> in 5 seconds!');
			header('Refresh:5; url=' . common::GetUrl('Admin_TinyPNG_options'));
		} else {
			\Tinify\setKey($this->opts['apikey']);
			$this->CheckFreeImage();

			echo "<h3>TinyPNG</h3>";
			echo '<div class="free_images" > <i class="fa fa-info-circle"></i> <b>' . (500 - $this->free_images) . '</b> images compressed <br /><b> ' . $this->free_images . '</b> free images left in this mounth</div>';
			echo '<div class="warning_images">  <i class="fa fa-warning"></i>  Compressing many images at once,<br /> can take a lot of time. </div>';
			echo '<a class="gpbutton un_check" href ="#" ><i  class="fa fa-check-square-o" ></i> Select all</a> ';
			echo '<a class="gpbutton compress_selected" href ="#" ><i  class="fa fa-compress" ></i> Compress selected</a> ';
			echo '<a class="gpbutton" href ="' . common::GetUrl('Admin_TinyPNG_options') . '" ><i  class="fa fa-cogs" ></i> Settings</a> ';

			$this->CommandHandleAdmin();

		}
	}

	function CommandHandleAdmin(){

		$my_cmd = common::GetCommand();

		switch ($my_cmd) {
			case 'compress_selected':
				$this->compress_selected();
				break;
			case 'compress_one':
				$this->compress_one();
				break;
			default:
				$this->Check_Site_Move();
				$this->Set_Cache();
				$this->Show_Table();
		}
	}


	function Show_Table(){
		global $dataDir;
		$this->Check_Cache();
		$data = $this->Get_Cache();

		$total_space = $saved_space = 0;
		foreach ($data as $file) {
			$total_space += $file['size'];
			if (array_key_exists("old_size", $file)){
				$delta = $file['old_size'] - $file['size'];
				$saved_space += $delta;
			}
		}
		echo '<div class="info_tp"> <i class="fa fa-check"></i> Total <b>' . $this->human_filesize($total_space) . '</b> in  ' . count($data) . ' images. <br /> Saved <b>' . $this->human_filesize($saved_space) . '</b></div>';
		echo '<table id="myTable" class="bordered full_width tablesorter tp-tablesorter" data-sortlist="[[4,1],[5,0]]">
			<thead>
			<tr>
				<th data-sorter="false">Select</th>
				<th>Image Name</th>
				<th data-sorter="false"></th>
				<th>Old Size</th>
				<th class="sorter-digit">Image Size</th>
				<th>Saved</th>
				<th>Action</th>

			</tr>
			</thead>
			 <tbody> ';
		foreach ($data as $file) {

			$clean_name = str_replace($dataDir, "", $file["name"]);
			//Name
			echo '
			<tr>
				<td>';
			if ($file["compress"] == "no"){
				echo ' <input class="compr" type="checkbox" value="' . $file["name"] . '">';
			}
			echo '</td>
				<td>' . $clean_name . '</td> ';

			//View button
			echo '<td>';
			echo '<a class="gpbutton" href ="' . $clean_name . '" name="gallery" rel="gallery_tp" ><i  class="fa fa-eye"></i></a>';
			echo '</td> ';

			//Old Size
			echo '<td>';
			echo '<span style="display:none;">';
			if (array_key_exists("old_size", $file)){
				echo $file["old_size"];
			}
			echo '</span>';
			echo '<span>';
			if (array_key_exists("old_size", $file)){
				echo $this->human_filesize($file["old_size"]);
			}
			echo '</span>';
			echo '</td> ';

			//Image Size
			echo '	<td><span style="display:none;">' . $file["size"] . '</span> <span>' . $this->human_filesize($file["size"]) . '</span></td>';

			//Saved
			echo '	<td>';
			echo '<span style="display:none;">';
			if (array_key_exists("old_size", $file)){
				echo($file["old_size"] - $file["size"]);
			}
			echo '</span>';
			echo '<span>';
			if (array_key_exists("old_size", $file)){
				echo $this->human_filesize(($file["old_size"] - $file["size"]));
			}
			echo '</span>';
			echo '</td> ';

			//Action
			echo '<td>';
			if ($file["compress"] == "yes"){
				echo '<span class="good"><i class="fa fa-thumbs-up good"></i> Compressed!</span>';
			} else {
				echo '<a class="gpbutton compress_one" href ="#" ><i  class="fa fa-compress"></i> Compress</a>';
			}
			echo '</td>

			</tr> ';

		}

		echo '</tbody> </table> ';

	}

	function Set_Cache(){
		global $addonPathData;
		$cache_file = $addonPathData . '/images.php';
		if (file_exists($cache_file)){
			$this->Check_Cache();
			return;
		} else {
			$this->Get_Images();
		}
		$i = 0;
		if (!count($this->all_files)){
			return;
		}
		foreach ($this->all_files as $file) {
			$cache_data[$i]['name'] = $file;
			$cache_data[$i]['size'] = filesize($file);
			$cache_data[$i]['compress'] = "no";
			$i++;
		}
		gpFiles::SaveData($cache_file, 'cache_data', $cache_data);

	}

	function Check_Cache(){
		global $addonPathData;
		$stored = $this->Get_Cache();
		$this->Get_Images();

		if (!count($this->all_files)){
			return;
		}

		foreach ($this->all_files as $file) {

			$flag_file_in_cache = false;
			//check image size changed
			foreach ($stored as $key => $store_file) {
				if ($file == $store_file['name']){
					$flag_file_in_cache = true;
					if (filesize($file) > $store_file['size'])
						$stored[$key]['compress'] = "no";
					$stored[$key]['size'] = filesize($file);
				}

			}
			//if new image add to cache
			if (!$flag_file_in_cache){
				$max_key = count($stored) > 0 ? max(array_keys($stored)) : 0;
				$stored[$max_key + 1]['name'] = $file;
				$stored[$max_key + 1]['size'] = filesize($file);
				$stored[$max_key + 1]['compress'] = "no";
			}

		}

		//check all cache images exist
		foreach ($stored as $key => $file) {
			if (!file_exists($file['name'])){
				unset($stored[$key]);
			}
		}
		//save cache
		$cache_file = $addonPathData . '/images.php';
		gpFiles::SaveData($cache_file, 'cache_data', $stored);

	}

	function Check_Site_Move(){
		global $dataDir, $addonPathData;
		$file = $addonPathData . '/datadir.php';
		if (!file_exists($file)){
			gpFiles::SaveData($file, 'path', $dataDir);
		} else {
			include $file;
			// $path comes from file
			if ($path <> $dataDir){
				$cache_file = $addonPathData . '/images.php';
				if (!file_exists($cache_file)){
					return;
				}
				$data = $this->Get_Cache();
				foreach ($data as $key => $item) {
					$data[$key]['name'] = str_replace($path, $dataDir, $item["name"]);
				}
				gpFiles::SaveData($cache_file, 'cache_data', $data);
				gpFiles::SaveData($file, 'path', $dataDir);

			}

		}

	}

	function Get_Cache(){
		global $addonPathData;
		$cache_file = $addonPathData . '/images.php';
		$data = gpFiles::Get($cache_file, 'cache_data');
		return $data;
	}

	function compress_one(){
		global $page, $addonPathData;
		$file = $_REQUEST["file"];
		$old_size = filesize($file);
		try {
			$source = \Tinify\fromFile($file);
			$source->toFile($file);

		} catch (\Tinify\Exception $e) {
			$page->ajaxReplace[] = ['CompressOneRespond', 'arg', $e];
			return;
		}

		clearstatcache($file);
		$data = $this->Get_Cache();
		foreach ($data as $key => $image) {
			if ($image["name"] == $file){
				$data[$key]['old_size'] = $old_size;
				$data[$key]['size'] = filesize($file);
				$data[$key]['compress'] = "yes";
			}
		}
		$cache_file = $addonPathData . '/images.php';
		gpFiles::SaveData($cache_file, 'cache_data', $data);
		$arg_value = true;
		$page->ajaxReplace[] = ['CompressOneRespond', 'arg', $arg_value];
	}

	function compress_selected(){
		$max_time = ini_get("max_execution_time");
		$time_start = microtime(true);

		global $page, $addonPathData;
		$page->ajaxReplace = [];
		$img_urls = &$_REQUEST['my_value'];
		//	$img_urls = explode( ',', $img_urls ) ;
		$i = 0;
		$arg_value = true;
		foreach ($img_urls as $file) {
			$old_size = filesize($file);
			try {
				$source = \Tinify\fromFile($file);
				$source->toFile($file);
				$i++;
			} catch (\Tinify\Exception $e) {
				$page->ajaxReplace[] = ['CompressRespond', 'arg', $e];
				return;
			}
			clearstatcache($file);
			$data = $this->Get_Cache();
			foreach ($data as $key => $image) {
				if ($image["name"] == $file){
					$data[$key]['old_size'] = $old_size;
					$data[$key]['size'] = filesize($file);
					$data[$key]['compress'] = "yes";
				}
			}
			$cache_file = $addonPathData . '/images.php';
			gpFiles::SaveData($cache_file, 'cache_data', $data);

			$time_spend = (microtime(true) - $time_start);
			if ($time_spend + 5 > $max_time){
				$arg_value = false;
				break;
			}

		}

		$page->ajaxReplace[] = ['CompressRespond', 'arg', $arg_value];
	}


	function CheckFreeImage(){

		try {
			\Tinify\validate();
			$compressionsThisMonth = \Tinify\compressionCount();
		} catch (\Tinify\Exception $e) {
			// Validation of API key failed.
			echo $e;
		}

		$this->free_images = 500 - $compressionsThisMonth;

	}

	function Get_Images($dir_rel = ''){
		global $dataDir;

		if ($this->opts['thumb'] <> ""){
			$temp = ".";
		} else {
			$temp = 'thumbnails';
		}

		$dir_full = $dataDir . '/data/_uploaded' . $dir_rel;
		$files = scandir($dir_full);

		foreach ($files as $file) {

			if ($file == '.' || $file == '..' || $file == $temp || $file == '.tmb'){
				continue;
			}

			$file_full = $dir_full . '/' . $file;
			$file_rel = $dir_rel . '/' . $file;

			if (is_dir($file_full)){
				$this->Get_Images($file_rel);
				continue;
			}

			if ($this->IsImgValid($file_full)){
				$this->all_files[] = $file_full;
			}
		}

	}

	function human_filesize($bytes, $decimals = 2){
		$sz = ['B', 'K', 'M', 'G', 'T', 'P'];
		$factor = floor((strlen($bytes) - 1) / 3);
		return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
	}


	function IsImgValid($file){
		$img_types = ['png' => 1, 'jpg' => 1, 'jpeg' => 1];

		$type = \gp\admin\Content\Uploaded::GetFileType($file);

		return isset($img_types[$type]);
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


}


?>