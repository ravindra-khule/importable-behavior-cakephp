<?php
/**
 * Model behavior to support importing datafiles into models.
 *
 * @package app
 * @subpackage app.models.behaviors
 * @author Carlos G. Limardo <carlos.limardo@gmail.com>
 */
class ImportableBehavior extends ModelBehavior {
	var $runtime = array();
	var $_defaults = array(
			'option' => 'REPLACE', // either REPLACE or IGNORE
			'field_terminator' => ',',
			'field_encloser' => '\"',
			'field_escaper' => '',
			'line_terminator' => '',
			'line_skip' => 1, // number of lines to skip (csv header)
			'whitelist' => array(), // list of fields, will be autofilled if not selected
			'blacklist' => array(),
			'preset' => array(), // array of columns and values to prefill
			);
	
	function setup(&$Model, $config = array()) {
		$settings = array_merge($this->_defaults, (array)$config);

		$this->settings[$Model->alias] = $settings;
	}
    
    /**
     * Simple function to truncate/clear the MySQL table
     *
     * @return bool
     */
    function truncate(&$Model) {
		$sql = "TRUNCATE ";
		$sql .= $Model->table;
		
		return $Model->query($sql);
	}
    
    /**
     * Breaks ORM and only works for MySQL, but it's the only way to do it besides
     * looping through an entire CSV and adding one field at a time.
     * 
     * example: $this->Model->loadData('/the/file.csv');
     * @return bool
     * @todo add presets (ie. created=now()) to automagically add a timestampe, etc.
     */
	function loadData(&$Model, $file_path = null, $preset = array()) {
		//echo $this->alias;
		$sql = "LOAD DATA LOCAL INFILE '{$file_path}' ";
		
		if (!empty($this->settings[$Model->alias]['option'])) {
			$sql .= $this->settings[$Model->alias]['option']." ";
		}
		
		$sql .= "INTO TABLE ".$Model->table." ";
		
		$sql .= "FIELDS TERMINATED BY ";
			if (is_numeric($this->settings[$Model->alias]['field_terminator'])) {
				$sql .=  "x'0".$this->settings[$Model->alias]['field_terminator']."' "; // if uses special char for terminating
			} else {
				$sql .= "'".$this->settings[$Model->alias]['field_terminator']."' ";
			}
		
		if (!empty($this->settings[$Model->alias]['field_encloser'])) {
			$sql .= "OPTIONALLY ENCLOSED BY '".$this->settings[$Model->alias]['field_encloser']."' ";
		}
		
		if (!empty($this->settings[$Model->alias]['line_terminator'])) {
			// let it figure it out on its own if its empty
			$sql .= "LINES TERMINATED BY ";
			if(is_numeric($this->settings[$Model->alias]['line_terminator'])) { 
				$sql .= "x'0".$this->settings[$Model->alias]['line_terminator']."' "; // if uses special char for terminating
			} else {
				$sql .= "'".$this->settings[$Model->alias]['line_terminator']."' ";
			}
		}
		
		$sql .= "IGNORE ".$this->settings[$Model->alias]['line_skip']." LINES ";
		$sql .= "(".$this->_fieldsList($Model).") ";
		
		//e($sql)."\n";
		return $Model->query($sql);
	}

	function _fieldsList($Model) {
		if (sizeof($this->settings[$Model->alias]['whitelist']) <= 0) {
			$this->settings[$Model->alias]['whitelist'] = array_diff(array_keys($Model->schema()), $this->settings[$Model->alias]['blacklist']);
		}
			
		return "`".implode('`,`', $this->settings[$Model->alias]['whitelist'])."`";
	}
}
