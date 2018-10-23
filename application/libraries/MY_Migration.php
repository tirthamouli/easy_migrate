<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Migration extends CI_Migration{

  /*
   * Overriding
   *
   */

   public function version($target_version)
 	{
    // Creating a table for all_migrations
    $this->load->database();

    if($this->db->table_exists('migration_all') == FALSE){
      $this->dbforge->add_field(array(
              'id' => array(
                      'type' => 'INT',
                      'constraint' => 5,
                      'unsigned' => TRUE,
                      'auto_increment' => TRUE
              ),
              'file' => array(
                      'type' => 'VARCHAR',
                      'constraint' => '1000',
              )
      ));
      $this->dbforge->add_key('id', TRUE);
      $this->dbforge->add_key('file', TRUE);
      $this->dbforge->create_table('migration_all');
    }
 		// Note: We use strings, so that timestamp versions work on 32-bit systems
 		$current_version = $this->_get_version();

 		if ($this->_migration_type === 'sequential')
 		{
 			$target_version = sprintf('%03d', $target_version);
 		}
 		else
 		{
 			$target_version = (string) $target_version;
 		}

 		$migrations = $this->find_migrations();

 		if ($target_version > 0 && ! isset($migrations[$target_version]))
 		{
 			$this->_error_string = sprintf($this->lang->line('migration_not_found'), $target_version);
 			return FALSE;
 		}

 		if ($target_version > $current_version)
 		{
 			$method = 'up';
 		}
 		elseif ($target_version < $current_version)
 		{
 			$method = 'down';
 			// We need this so that migrations are applied in reverse order
 			krsort($migrations);
 		}
 		else
 		{
 			// Well, there's nothing to migrate then ...
 			return TRUE;
 		}

 		// Validate all available migrations within our target range.
 		//
 		// Unfortunately, we'll have to use another loop to run them
 		// in order to avoid leaving the procedure in a broken state.
 		//
 		// See https://github.com/bcit-ci/CodeIgniter/issues/4539
 		$pending = array();
 		foreach ($migrations as $number => $file)
 		{
 			// Ignore versions out of our range.
 			//
 			// Because we've previously sorted the $migrations array depending on the direction,
 			// we can safely break the loop once we reach $target_version ...
 			if ($method === 'up')
 			{
 				if ($number <= $current_version)
 				{
 					continue;
 				}
 				elseif ($number > $target_version)
 				{
 					break;
 				}
 			}
 			else
 			{
 				if ($number > $current_version)
 				{
 					continue;
 				}
 				elseif ($number <= $target_version)
 				{
 					break;
 				}
 			}

 			// Check for sequence gaps
 			if ($this->_migration_type === 'sequential')
 			{
 				if (isset($previous) && abs($number - $previous) > 1)
 				{
 					$this->_error_string = sprintf($this->lang->line('migration_sequence_gap'), $number);
 					return FALSE;
 				}

 				$previous = $number;
 			}

 			include_once($file);
 			$class = 'Migration_'.basename($file, '.php');

 			// Validate the migration file structure
 			if ( ! class_exists($class, FALSE))
 			{
 				$this->_error_string = sprintf($this->lang->line('migration_class_doesnt_exist'), $class);
 				return FALSE;
 			}
 			elseif ( ! is_callable(array($class, $method)))
 			{
 				$this->_error_string = sprintf($this->lang->line('migration_missing_'.$method.'_method'), $class);
 				return FALSE;
 			}

 			$pending[$number] = array($class, $method, basename($file));
 		}

 		// Now just run the necessary migrations
 		foreach ($pending as $number => $migration)
 		{
      $file = array_pop($migration);
 			log_message('debug', 'Migrating '.$method.' from version '.$current_version.' to version '.$number);

 			$migration[0] = new $migration[0];
 			call_user_func($migration);
 			$current_version = $number;
 			$this->_update_version($current_version);

      if($migration[1] === 'up'){
        // Insert into table
        $this->db->insert('migration_all',array('file'=>$file));
      }else{
        // delete from table
        $this->db->where('file',$file)
                 ->delete('migration_all');
      }
 		}

 		// This is necessary when moving down, since the the last migration applied
 		// will be the down() method for the next migration up from the target
 		if ($current_version <> $target_version)
 		{
 			$current_version = $target_version;
 			$this->_update_version($current_version);
 		}

 		log_message('debug', 'Finished migrating to '.$current_version);
 		return $current_version;
 	}

  public function version_add($file)
 {
   $this->load->database();

   include_once($file);
   $class = 'Migration_'.basename($file, '.php');
   $mig_class = new $class;
   $mig_class->up();

   // Table changes
   $this->db->insert('migration_all',array('file'=>basename($file)));
 }

}
