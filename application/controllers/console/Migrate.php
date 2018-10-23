<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migrate extends CI_Controller{

  public function __construct(){
    parent::__construct();
    if(!$this->input->is_cli_request())
     {
       show_error("You don't have permission to access this controller");
       exit();
     }
     $this->load->library('Migration');
  }

  /*
   * Index function to check if everything is working
   *
   */
  function index(){
    // Check if migration library has been loaded successfully
    if ($this->migration->current() === FALSE)
    {
      show_error($this->migration->error_string());
      exit;
    }
    // All checks done and can proceed
  }

  /*
   * Upgrade to certain version of the database
   *
   */
  public function version($version){
    $migration = $this->migration->version($version);
    if(!$migration){
      echo $this->migration->error_string();
    }else{
      echo "Migration done \n";
    }
  }

  /*
   * Upgrade to latest version of the database
   *
   */
  public function latest_version(){
    $latest = $this->latest();
    $migration = $this->migration->version($latest);
    if(!$migration){
      echo $this->migration->error_string();
    }else{
      echo "Migrated to $latest version\n";
    }
  }

  /*
   * Generate a new version file
   *
   */
  public function generate($name=false){
    // Name check
    if(!$name)
    {
        echo "Please define migration name". PHP_EOL;
        return;
    }

    if (!preg_match('/^[a-z_]+$/i', $name)) {
        if (strlen($name) < 4) {
            echo "Migration must be at least 4 characters long" . PHP_EOL;
            return;
        }
        echo "Wrong migration name, allowed characters: a-z and _\nExample: first_migration" . PHP_EOL;
        return;
    }
    // Name is correct
    $filename = date('YmdHis') . '_' . $name . '.php';
    try {
        // Folder exists ?
        $folderPath = APPPATH . 'migrations';
        if (!is_dir($folderPath)) {
            try{
                mkdir($folderPath);
            }
            catch(Exception $e) {
                echo "Error:\n" . $e->getMessage() . PHP_EOL;
            }
        }

        $filepath = APPPATH . 'migrations/' . $filename;
        if (file_exists($filepath)) {
            echo "File allredy exists:\n" . $filepath . PHP_EOL;
            return;
        }
        $data['className'] = str_replace(".php","",$filename);
        $template = $this->load->view('cli/migrations/migration_class_template', $data, TRUE);
        //Create file
        try{
            $file = fopen($filepath, "w");
            $content = "<?php\n" . $template;
            fwrite($file, $content);
            fclose($file);
        }
        catch(Exception $e){
            echo "Error:\n" . $e->getMessage() . PHP_EOL;
        }
        echo "Migration created successfully!\nLocation: " . $filepath . PHP_EOL;
    } catch (Exception $e) {
        echo "Can't create migration file!\nError: " . $e->getMessage() . PHP_EOL;
    }
  }

  /*
   * Gives the latest version available
   *
   */
  public function latest(){
    // Folder exists?
    $folderPath = APPPATH . 'migrations';
    if(!is_dir($folderPath)){
      echo "No migrations\n";
      exit;
    }
    $files = array_diff(scandir($folderPath), array('.', '..'));
    $file = max($files);
    return (int)explode("_", $file)[0];
  }

  /*
   * Adds all the migrations that weren't added
   *
   */
  public function upgrade(){
    $this->load->database();
    // Folder exists?
    $folderPath = APPPATH . 'migrations';
    if(!is_dir($folderPath)){
      echo "No migrations\n";
      exit;
    }

    $files = array_diff(scandir($folderPath), array('.', '..'));
    sort($files);

    $max_file = $file = max($files);

    if($this->db->table_exists('migration_all') == FALSE){
      // Make all migrations
      $this->latest_version();
    }else{
      $query = $this->db->select('file')->get('migration_all');
      $result = $query->result_array();

      $all_files = [];
      foreach($result as $name => $file){
        array_push($all_files, $file['file']);
      }

      foreach($files as $file){
        if(!in_array($file, $all_files) ){
          $this->migration->version_add(APPPATH.'migrations/'.$file);
        }
      }

      $max_file_time = explode('_', $max_file)[0];
      $this->db->update('migrations', array("version" => $max_file_time));
      echo "Done \n";
    }
  }

}
