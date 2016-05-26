<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Git_updater extends MY_Controller {

	function __construct() {
		parent::__construct();
	
	}

	public function index()
	{
		// $this->load->view('welcome_message');
		echo "Welcome to the the git updater. Updated Seven times Now";
	}

	public function admin_updates_home($update_status=NULL){
		// echo "<pre> This";print_r($update_status);exit;
		$permissions='super_permissions';
		$data['user_types']=Access_level::get_access_levels($permissions);
		$identifier = $this -> session -> userdata('user_indicator');

		$hash = $this->get_hash();
		$local_hash = $this->github_update_status_local();
		if ($hash != $local_hash) {
			$status = 1;
		}
		$res = $this->github_updater->get_hash();

		// echo "<pre>";print_r($hash);echo "</pre>";exit;	

		if (isset($status) && $status == 1) {
			$status_ = "TRUE";
			$available_update = 1;
		}else{
			$status_ = "FALSE";
			$available_update = 0;
		}
		// echo $available_update;exit;
		$git_records = Offline_model::get_prior_records();
		// echo "<pre>";print_r($git_records);exit;

		$data['available_update'] = $available_update;
		$data['most_recent_commit'] = $hash;
		$data['update_status'] = $status_;
		$data['git_records'] = $git_records;

		$data['title'] = "System Updates";
		$data['banner_text'] = "System Management";
		// $data['content_view'] = "offline/offline_admin_home";
		// $template = 'shared_files/template/dashboard_v';
		$data['banner_text'] = "System Management";
 		// if ($identifier=='facility_admin') {
 	 //       $template = 'shared_files/template/template';            
   //         // $data['content_view'] = 'facility/offline_admin';            
   //         $data['content_view'] = "offline/offline_admin_home";
   //      }else{
   //         $template = 'shared_files/template/dashboard_v';
   //         $data['content_view'] = "offline/offline_admin_home";
   //      }
 	       $template = 'shared_files/template/template';            
 	       $data['latest_hash'] = $hash;
           $data['content_view'] = "offline/offline_admin_home";


		// $update_status = $this->github_update();
		// $hash = $this->get_hash();
		// $update_files = $this->get_zip($hash);

		// echo "<pre>";print_r($update_files);exit;

		$this -> load -> view($template, $data);
	}

	public function update_status()
	{
		$hash = $this->get_hash();
		$local_hash = $this->github_update_status_local();
		if ($hash != $local_hash) {
			$status = 1;
		}else{
			$status = 0;
		}

		return $status;
	}

	public function update_system(){
		$hash = $this->get_hash();			
		$get_zip = $this->get_latest_zip();
		$update_files = $this->extract_and_copy_files($hash);
		$update_git_log = $this->update_log($hash);
		
		$extracted_path = $this->get_extracted_path();
		$delete_residual_repo = $this->delete_residual_files($hash.'.zip');
		$delete_residual_dir = $this->delete_residual_files($extracted_path);
		$update_logs = $this->update_log($hash);
		// echo "<pre>";print_r($update_files);exit;
		// echo $set_current_commit;exit;
		// echo "I worked";
		redirect('/git_updater/admin_updates_home/1');
	}

	public function update_log($hash){
		$current_time =date('Y-m-d H:i:s');
		$data = array('hash_value' => $hash,'last_updated'=>$current_time);	
		// echo "<pre>";print_r($data);die;
		$status = $this->db->insert('git_log',$data);
		return $status;
	}

	public function github_update_status_local(){
		$hash = $this->get_hash();
		$local_hash = git_updater_model::get_latest_hash();
		$actual_hash = $local_hash[0]['hash_value'];

		return $actual_hash;
	}

	public function github_update_status(){
		// echo "I WAS HERE";
		$res = $this->github_updater->has_update();

		// echo "<pre>"; print_r($res);
		return $res;
	}

	public function github_update(){
		// echo "I WAS HERE";
		$res = $this->github_updater->update();
		// $this->unzip->extract();
		echo "<pre>"; print_r($res);
	}

	public function github_update_download(){
		// echo "I WAS HERE";
		$res = $this->github_updater->update_download();
		// $this->unzip->extract();
		// echo "<pre>"; print_r($res);
		return $res;
	}
	
	public function get_hash(){
		// echo "I WAS HERE";
		$res = $this->github_updater->get_hash();
		// echo "<pre>"; print_r($res);
		return $res;
	}

	public function extract_and_copy_files(){
		$success_status = array();
		$hash = $this -> get_hash();

		$unzip_status = $this->unzip->extract($hash.'.zip');

		// echo "<pre>"; print_r($unzip_status);echo "</pre>";exit;
		$sanitized_directory = array();
		foreach ($unzip_status as $unzip) {
			$unzip = substr($unzip, 2);
			// echo "<pre>".$unzip;
			$del = "/";
			$trimmed=strpos($unzip, $del);
			$important=substr($unzip, $trimmed+strlen($del)-1, strlen($unzip)-1);
			$important = substr($important, 1);

			$sanitized_directory[] = $important;
		}
			// echo "<pre>";print_r($sanitized_directory);
			$ignored = $this->ignored_files();
			$squeaky = $this->array_cleaner($sanitized_directory,$ignored);
			$extracted_path = $this->get_extracted_path();
			// echo "<pre>";print_r($squeaky);
			// echo "<pre>";print_r($extracted_path);
			$status = $this->copy_and_replace($squeaky,$extracted_path);
			// echo "<pre>";print_r($status);
			// $set_hash = $this->github_updater->_set_config_hash($hash);
			$success_status['extracted_path'] = $extracted_path;
			$success_status['status'] = $status;

			return $success_status;
	}

	public function ignored_files(){
		$ignored = $this->github_updater->list_ignored();

		// echo "<pre>";print_r($ignored);
		return $ignored;
	}

	public function array_cleaner($dirty_array,$dirt){
		foreach ($dirty_array as $key => $leaving_elem) {
		    foreach ($dirt as $keys => $value) {
		    	if (strpos($leaving_elem,$value) !== false) {
				    // echo 'true';
			        unset($dirty_array[$key]);
				}
			    // echo $leaving_elem;
		    }
		}
		// echo "<pre>DIRTY ARR";print_r($dirty_array);
		// echo "<pre>DIRT";print_r($dirt);

		return $dirty_array;
		// echo FCPATH;
	}//end of array cleaner

	public function get_extracted_path(){
		$user_name = $this -> config -> item('github_user');
		$repo_name = $this -> config -> item('github_repo');
		$hash = $this->get_hash();
		$short_hash = substr($hash, 0, 7);

		// echo $short_hash."<pre>";
		$folder_name = $user_name.'-'.$repo_name.'-'.$short_hash;
		// echo $folder_name;
		return $folder_name;

	}

	public function copy_and_replace($directories,$source_path = NULL){
		$copy_status_ = array();
		// echo FCPATH.$source_path."<pre>";
		$fcpath = FCPATH;
		$sanitized_fcpath = str_replace('\\','/', $fcpath);
		// echo $sanitized_fcpath;
		foreach ($directories as $dir) {
		// echo FCPATH.$dir."<pre>";
			$dir = str_replace('/','\\', $dir);
			$src = $sanitized_fcpath.$source_path."/".$dir;
			$dest = $sanitized_fcpath.$dir;
			$copy_status_[]['src']= "\"".$src."\"";
			$copy_status_[]['dest']= "\"".$dest."\"";

			$this->copy($src,$dest);
		}
		return $copy_status_;
	}

	public function delete_residual_files($path){
		$delete_status = unlink($path);
		delete_files('./path/to/directory/', TRUE);
		return $delete_status;
	}

	public function get_latest_zip($hash = NULL){
		$hash = $this->get_hash();
		$res = $this->github_updater->get_commit_zip($hash);

		// echo "<pre>";print_r($res);
		return $res;
	}

	public function set_latest_hash($hash){
		$hash_set = $this -> config -> item('current_commit',$hash);

		return $hash_set;
	}

	public function tester(){
		// copy("C:/xampp/htdocs/HCMP-ALPHA/karsanrichard-HCMP-ALPHA-cad542a/README.md","C:/xampp/htdocs/HCMP-ALPHA/README.md");
		// echo FCPATH;
	}

	public function copy($src,$dest){
		$c = copy($src,$dest);
		return $c;
	}

	public function download_repo(){
		$repoDL = new Repo_downloader();

		$repoDL->download(array(
		    'user'   => 'karsanrichard',
		    'token'  => 'eb56cb95bdcdb44b81371662cc1962985d2799f5',
		    'repo'   => 'HCMP-ALPHA',
		    'saveAs' => 'myRepo-latest.zip'
		));

		// $this->repo_downloader->download();
	}

	public function get_with_get(){
		$url = "https://github.com/karsanrichard/HCMP-ALPHA/zipball/master";
		$data = file_get_contents($url);
		echo $data;
	}

	public function default_udpate_function(){
		$updater = $this->github_updater->update_default();

		echo $updater;
	}

}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */