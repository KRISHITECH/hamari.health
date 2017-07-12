<?php
/* +-----------------------------------------------------------------------------+
*    OpenEMR - Open Source Electronic Medical Record
*    Copyright (C) 2013 Z&H Consultancy Services Private Limited <sam@zhservices.com>
*
*    This program is free software: you can redistribute it and/or modify
*    it under the terms of the GNU Affero General Public License as
*    published by the Free Software Foundation, either version 3 of the
*    License, or (at your option) any later version.
*
*    This program is distributed in the hope that it will be useful,
*    but WITHOUT ANY WARRANTY; without even the implied warranty of
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*    GNU Affero General Public License for more details.
*
*    You should have received a copy of the GNU Affero General Public License
*    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*    @author  Remesh Babu S <remesh@zhservices.com>
*    @author  Chandni Babu <chandnib@zhservices.com> 
* +------------------------------------------------------------------------------+
*/
namespace Application\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Application\Model\ApplicationTable;
use Application\Listener\Listener;

class CommonPlugin extends AbstractPlugin
{
  protected $application;
  
  /**
   * Application Table Object 
   * Listener Oblect
   * @param type $sm Service Manager
   */
  public function __construct($sm)
  {
    $sm->get('Zend\Db\Adapter\Adapter');
    $this->application    = new ApplicationTable();
    $this->listenerObject	= new Listener;
  }
  
  /**
   * Function checkACL
   * Plugin functions are easily access from any where in the project 
   * Call the ACL Check function zAclCheck from ApplicationTable
   *  
   * @param int     $useID
   * @param string  $sectionID
   * @return type
   */
  public function checkACL($useID, $sectionID)
  {
    return $this->application->zAclCheck($useID, $sectionID);
  }
  
  /**
    * Keyword color hightlight (primary keyword and secondary)
    * ? - The question mark used for omit the error.
    * Error occur in second word of the search keyword,
    * if maches any of the letter in the html element
  */
  public function hightlight($str, $keywords = '') {
    
    $keywords   = preg_replace('/\s\s+/', ' ', strip_tags(trim($keywords)));
    $style      = '???';
    $style_i	= 'highlight_i';
    $var        = '';
    foreach(explode(' ', $keywords) as $keyword) {
      $replacement  =   "<?? ?='" . $style . "'>" . trim($keyword). "</??>";
      $var          .=  $replacement . " ";
      $str	    =   str_ireplace($keyword, $replacement, $str);
    }

    $str = str_ireplace(rtrim($var), "<?? ?='" . $style_i . "'>" . trim($keywords) . "</??>", $str);
    $str = str_ireplace('???', 'highlight_i', $str);
    $str = str_ireplace('??', 'span', $str);
    $str = str_ireplace('?', 'class', $str);
    return $str;
  }
  
  public function date_format($date, $output_format, $input_format)
  {
    $this->application    = new ApplicationTable();
    $date_formatted = $this->application->fixDate($date, $output_format, $input_format);
    return $date_formatted;
  }
  
  public function escapeLimit($val){
    return escape_limit($val);
  }
  
    /*
  * Insert the imprted data to audit master table
  *
  * @param    var   Array   Details parsed from the CCR xml file
  * @return   audit_master_id   Integer   ID from audit_master table
  */
  public function insert_ccr_into_audit_data($var)
  {
    $appTable   = new ApplicationTable();
    $audit_master_id_to_delete  = $var['audit_master_id_to_delete'];
    $approval_status = $var['approval_status'];
    $type       = $var['type'];
    $ip_address = $var['ip_address'];
    $field_name_value_array     = $var['field_name_value_array'];
    $entry_identification_array = $var['entry_identification_array'];
    
    if($audit_master_id_to_delete){
      $qry  = "DELETE from audit_details WHERE audit_master_id=?";
      $appTable->zQuery($qry,array($audit_master_id_to_delete));
      
      $qry  = "DELETE from audit_master WHERE id=?";
      $appTable->zQuery($qry,array($audit_master_id_to_delete));
    }
    
    $master_query = "INSERT INTO audit_master SET pid = ?,approval_status = ?,ip_address = ?,type = ?";
    $result       = $appTable->zQuery($master_query,array(0,$approval_status,$ip_address,$type));
    $audit_master_id    = $result->getGeneratedValue();
    $detail_query = "INSERT INTO `audit_details` (`table_name`, `field_name`, `field_value`, `audit_master_id`, `entry_identification`) VALUES ";
    $detail_query_array = '';
    foreach($field_name_value_array as $key=>$val){
      foreach($field_name_value_array[$key] as $cnt => $field_details){
        foreach($field_details as $field_name => $field_value){
          $detail_query         .= "(? ,? ,? ,? ,?),";
          $detail_query_array[] = $key;
          $detail_query_array[] = trim($field_name);
          if(is_array($field_value)) {
            if($field_value['status']||$field_value['enddate']) {
              $detail_query_array[] = trim($field_value['value'])."|".trim($field_value['status'])."|".trim($field_value['begdate']);
            }
            else {
              $detail_query_array[] = trim($field_value['value']);
            }
          }
          else {
            $detail_query_array[] = trim($field_value);
          }
          $detail_query_array[] = $audit_master_id;
          $detail_query_array[] = trim($entry_identification_array[$key][$cnt]);
        }
      }
    }
    $detail_query = substr($detail_query, 0, -1);
    $detail_query = $detail_query.';';
    $appTable->zQuery($detail_query,$detail_query_array);
    return $audit_master_id;
  }
  
  public function getList($list_id,$selected='',$opt='')
   {
    $appTable = new ApplicationTable();
    $this->listenerObject = new Listener;
    $res = $appTable->zQuery("SELECT * FROM list_options WHERE list_id=? ORDER BY seq, title",array($list_id));
    $i = 0;
    if ($opt == 'search') {
	    $rows[$i] = array (
        'value' => 'all',
        'label' => $this->listenerObject->z_xlt('All'),
        'selected' => TRUE,
		  );
	    $i++;
    } elseif ($opt == '') {
	    $rows[$i] = array (
		    'value' => '',
		    'label' => $this->listenerObject->z_xlt('Unassigned'),
		    'disabled' => FALSE
	    );
	    $i++;
    }
  
    foreach($res as $row) {
      $sel = ($row['option_id']==$selected) ? TRUE : FALSE;
      $rows[$i] = array (
        'value' => htmlspecialchars($row['option_id'],ENT_QUOTES),
        'label' => $this->listenerObject->z_xlt($row['title']),
        'selected' => $sel,
      );
      $i++;
    }
    return $rows;
  }
  
  /*
  * $this->escapeHtml() cannot be used in any files other than view.
  * This function will enable a user to use escapeHtml in any files like controller model etc.
  */
  public function escape($string){
      $viewHelperManager  = $this->getServiceLocator()->get('ViewHelperManager');
      $escapeHtml         = $viewHelperManager->get('escapeHtml'); // $escapeHtml can be called as function because of its __invoke method
      return $escapeHtml($string);
  }
  
  public function getListtitle($listId,$listOptionId)
  {
      $appTable = new ApplicationTable();
      $sql = "SELECT title FROM list_options WHERE list_id = ? AND option_id = ? ";
      $result = $appTable->zQuery($sql, array($listId,$listOptionId));
      $row = $result->current();
      $return = xl_list_label($row['title']);
      return $return;
  }
  /**
   * Procedure Providers for Lab
   * function getProviders
   * To fetch all Providers
   */
  
  public function getProviders()
  {
    global $encounter;
    global $pid;
    $sqlSelctProvider     = "SELECT * FROM form_encounter WHERE encounter=? AND pid=?";
    $result               = $this->application->zQuery($sqlSelctProvider, array($encounter, $pid));    
    foreach($result as $row ){
      $provider = $row['encounter_provideID'];
    } 
    $res =  $this->application->zQuery("SELECT id, fname, lname, specialty FROM users " .
      "WHERE active = 1 AND ( info IS NULL OR info NOT LIKE '%Inactive%' ) " .
      "AND authorized = 1 " .
      "ORDER BY lname, fname");

    $rows[0] = array (
      'value' => '',
      'label' => $this->listenerObject->z_xlt('Unassigned'),
      'selected' => TRUE,
      'disabled' => FALSE
    );
    $i = 1;

     foreach($res as $row) {
      if ($row['id'] == $provider) {
        $select =  TRUE;
      } else {
        $select = FALSE;
      }
      $rows[$i] = array (
        'value' => $row['id'],
        'label' => $row['fname']." ".$row['lname'],
        'selected' => $select,
      );
      $i++;
    }
    return $rows;
  }
  /**
    * selected menu item at the top of the array
    * @param array $array
    * @param int $key selected menu id
    * @return array $array
  */
  
   public function getLabs($type='',$opt='')
   {
     
       $res = $this->application->zQuery("SELECT ppid,name,remote_host,login,password,mirth_lab_id FROM procedure_providers ORDER BY name, ppid"); 

		$i = 0;
    if ($opt == 'search') {
	    $rows[$i] = array (
        'value' => 'all',
        'label' => $this->listenerObject->z_xlt('All'),
        'selected' => TRUE,
		  );
	    $i++;
    }

                  foreach($res as $row) {
				$value = '';
				if ($type == 'y') {
			if ($row['remote_host'] != '' && $row['login'] != '' && $row['password'] != '') {
				$value = $row['ppid'] . '|' . 1 . '|' . $row['mirth_lab_id']; // 0 - Local Lab and 1 - External Lab
			} else {
				$value = $row['ppid'] . '|' . 0 . '|' . $row['mirth_lab_id'];
			}
				} else {
			$value = $row['ppid']. '|' . $row['mirth_lab_id'];
				}
				if ($row['name'] == 'Quest') {
					//$select =  TRUE;
				} else {
					$select = FALSE;
				}
				$rows[$i] = array (
			'value' => $value,
			'label' => $row['name'],
			'selected' => $select,
				);
				$i++;
		}
		return $rows;
   }
   /**
   * Procedure Providers for Lab
   * function getProcedureProviders
   * List all Procedure Providers
   */
  public function getProcedureProviders()
  {
    $arr = array();
    $sql = "SELECT pp.*
      FROM procedure_providers AS pp 
      ORDER BY pp.name";
    $result = $this->application->zQuery($sql);
    $i = 0;
    //while ($row = sqlFetchArray($result)) {
      foreach($result as $row) {
      $arr[$i]['ppid']					= $row['ppid'];
      $arr[$i]['name'] 					= htmlspecialchars($row['name'],ENT_QUOTES);
      $arr[$i]['npi'] 					= htmlspecialchars($row['npi'],ENT_QUOTES);
      $arr[$i]['protocol'] 			= htmlspecialchars($row['protocol'],ENT_QUOTES);
      $arr[$i]['DorP'] 					= htmlspecialchars($row['DorP'],ENT_QUOTES);
      $arr[$i]['send_app_id'] 	= htmlspecialchars($row['send_app_id'],ENT_QUOTES);
      $arr[$i]['send_fac_id'] 	= htmlspecialchars($row['send_fac_id'],ENT_QUOTES);
      $arr[$i]['recv_app_id'] 	= htmlspecialchars($row['recv_app_id'],ENT_QUOTES);
      $arr[$i]['recv_fac_id'] 	= htmlspecialchars($row['recv_fac_id'],ENT_QUOTES);
      $arr[$i]['remote_host'] 	= htmlspecialchars($row['remote_host'],ENT_QUOTES);
      $arr[$i]['login'] 				= htmlspecialchars($row['login'],ENT_QUOTES);
      $arr[$i]['password'] 			= htmlspecialchars($row['password'],ENT_QUOTES);
      $arr[$i]['orders_path'] 	= htmlspecialchars($row['orders_path'],ENT_QUOTES);
      $arr[$i]['results_path'] 	= htmlspecialchars($row['results_path'],ENT_QUOTES);
      $arr[$i]['notes'] 				= htmlspecialchars($row['notes'],ENT_QUOTES);
      if ($row['remote_host'] != '' && $row['login'] != '' && $row['password'] != '') {
          $arr[$i]['labtype']	= 'External';
      } else {
          $arr[$i]['labtype']	= 'Local';
      }
      $i++;
		}
		return $arr;
    } 
    /**
     * getDropdownValAsText
     * @param type $list_id
     * @param type $option_id
     * @return type
     */
    public function getDropdownValAsText($list_id,$option_id)
  {
    $this->application    = new ApplicationTable();
    $result =  $this->application->zQuery("SELECT title FROM list_options WHERE list_id = ? AND option_id = ?",array($list_id,$option_id )); 
    $res    =  $result->current();
    return $res['title'];
  }
  /**
   * getClientCredentialsLab
   * @param type $labname
   * @return type
   */
  public function getClientCredentialsLab($labname){
    $this->application = new ApplicationTable();
    $result = $this->application->zQuery("SELECT * FROM procedure_providers WHERE mirth_lab_name = ?",array($labname)); 
    $res    = $result->current();
    return $res;
  }
}