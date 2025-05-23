<?php
/**
 * @Author: ido
 * @Date:   2016-06-16 21:34:22
 * @Modified by:   ido, wardiyono
 * @Modified time: 2016-06-16 21:40:48
 * @Last Modified time: 2016-09-10 14:24
 */

/* P2P/Copy Cataloging Server Management section */

use SLiMS\Url;

// key to authenticate
define('INDEX_AUTH', '1');
// key to get full database access
define('DB_ACCESS', 'fa');

if (!defined('SB')) {
    // main system configuration
    require '../../../sysconfig.inc.php';
    // start the session
    require SB.'admin/default/session.inc.php';
}

// IP based access limitation
require LIB.'ip_based_access.inc.php';
do_checkIP('smc');
do_checkIP('smc-masterfile');

require SB.'admin/default/session_check.inc.php';
require SIMBIO.'simbio_GUI/table/simbio_table.inc.php';
require SIMBIO.'simbio_GUI/form_maker/simbio_form_table_AJAX.inc.php';
require SIMBIO.'simbio_GUI/paging/simbio_paging.inc.php';
require SIMBIO.'simbio_DB/datagrid/simbio_dbgrid.inc.php';
require SIMBIO.'simbio_DB/simbio_dbop.inc.php';

// privileges checking
$can_read = utility::havePrivilege('master_file', 'r');
$can_write = utility::havePrivilege('master_file', 'w');

if (!$can_read) {
    die('<div class="errorBox">'.__('You don\'t have enough privileges to view this section').'</div>');
}

$serverType = array_map(function($index) {
    global $sysconf;
    return [$index, $sysconf['p2pserver_type'][$index]];
}, array_keys($sysconf['p2pserver_type']));

if (isset($_POST['saveData']) AND $can_read AND $can_write) {
  $server_name = trim(strip_tags($_POST['serverName']));
  $server_uri = trim(strip_tags($_POST['serverUri']));
  // check form validity
    if (empty($server_name) OR empty($server_uri)) {
      toastr(__('Server Name And URI can\'t be empty'))->error();
      exit();
    } else {
      $data['name'] = $dbs->escape_string($server_name);
      if (!Url::isValid($server_uri)) exit(toastr(__('URI isn\'t valid, start witch prefix like e.g: http://, https:// etc.'))->error());
      $data['uri'] = $dbs->escape_string($server_uri);
      $data['server_type'] = $dbs->escape_string($_POST['serverType']);
      $data['input_date'] = date('Y-m-d H:i:s');
      $data['last_update'] = date('Y-m-d H:i:s');

      // create sql op object
      $sql_op = new simbio_dbop($dbs);
      if (isset($_POST['updateRecordID'])) {
        // remove input date
        unset($data['input_date']);
        // filter update record ID
        $updateRecordID = $dbs->escape_string(trim($_POST['updateRecordID']));
        // update the data
        $update = $sql_op->update('mst_servers', $data, 'server_id='.$updateRecordID);
        if ($update) {
            toastr(__('Server Data Successfully Updated'))->success();
            echo '<script type="text/javascript">parent.jQuery(\'#mainContent\').simbioAJAX(parent.jQuery.ajaxHistory[0].url);</script>';
        } else { toastr(__('Server Data FAILED to Updated. Please Contact System Administrator')."\nDEBUG : ".$sql_op->error); }
        exit();
      } else {
        // insert the data
        if ($sql_op->insert('mst_servers', $data)) {
            toastr(__('New Server Data Successfully Saved'))->success();
            echo '<script type="text/javascript">parent.jQuery(\'#mainContent\').simbioAJAX(\''.$_SERVER['PHP_SELF'].'\');</script>';
        } else { toastr(__('Server Data FAILED to Save. Please Contact System Administrator')."\nDEBUG : ".$sql_op->error)->error(); }
        exit();
      }
    }
} else if (isset($_POST['itemID']) AND !empty($_POST['itemID']) AND isset($_POST['itemAction'])) {
  if (!($can_read AND $can_write)) {
    die();
  }
  /* DATA DELETION PROCESS */
  $sql_op = new simbio_dbop($dbs);
  $failed_array = array();
  $error_num = 0;
  if (!is_array($_POST['itemID'])) {
      // make an array
      $_POST['itemID'] = array((integer)$_POST['itemID']);
  }
  
  // loop array
  foreach ($_POST['itemID'] as $itemID) {
      $itemID = (integer)$itemID;
      if (!$sql_op->delete('mst_servers', 'server_id='.$itemID)) {
          $error_num++;
      }
  }

  // error alerting
  if ($error_num == 0) {
      toastr(__('All Data Successfully Deleted'))->success();
      echo '<script type="text/javascript">parent.jQuery(\'#mainContent\').simbioAJAX(\''.$_SERVER['PHP_SELF'].'?'.$_POST['lastQueryStr'].'\');</script>';
  } else {
      toastr(__('Some or All Data NOT deleted successfully!\nPlease contact system administrator'))->warning();
      echo '<script type="text/javascript">parent.jQuery(\'#mainContent\').simbioAJAX(\''.$_SERVER['PHP_SELF'].'?'.$_POST['lastQueryStr'].'\');</script>';
  }
  exit();
}

?>
<div class="menuBox">
<div class="menuBoxInner masterFileIcon">
  <div class="per_title">
      <h2><?php echo __('Copy Cataloging Server Configuration'); ?></h2>
  </div>
  <div class="sub_section">
    <div class="btn-group">
      <a href="<?php echo MWB; ?>master_file/p2pservers.php" class="btn btn-default"><?php echo __('Server List'); ?></a>
      <a href="<?php echo MWB; ?>master_file/p2pservers.php?action=detail" class="btn btn-default"><?php echo __('Add New Server'); ?></a>
    </div>

    <form name="search" action="<?php echo MWB; ?>master_file/p2pservers.php" id="search" method="get" class="form-inline"><?php echo __('Search'); ?> 
    <input type="text" name="keywords" class="form-control col-md-3" />
    <input type="submit" id="doSearch" value="<?php echo __('Search'); ?>" class="s-btn btn btn-default" />
    </form>
  </div>
</div>
</div>
<?php
if (isset($_POST['detail']) OR (isset($_GET['action']) AND $_GET['action'] == 'detail')) {
  if (!($can_read AND $can_write)) {
        die('<div class="errorBox">'.__('You don\'t have enough privileges to view this section').'</div>');
  }
  
  $itemID = (integer)isset($_POST['itemID'])?$_POST['itemID']:0;
  $rec_q = $dbs->query('SELECT * FROM mst_servers WHERE server_id='.$itemID);
  $rec_d = $rec_q->fetch_assoc();

  // create new instance
  $form = new simbio_form_table_AJAX('mainForm', $_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'], 'post');
  $form->submit_button_attr = 'name="saveData" value="'.__('Save').'" class="s-btn btn btn-default"';

  // form table attributes
  $form->table_attr = 'id="dataList" class="s-table table"';
  $form->table_header_attr = 'class="alterCell font-weight-bold"';
  $form->table_content_attr = 'class="alterCell2"';

  // edit mode flag set
  if ($rec_q->num_rows > 0) {
    $form->edit_mode = true;
    // record ID for delete process
    $form->record_id = $itemID;
    // form record title
    $form->record_title = $rec_d['name'];
    // submit button attribute
    $form->submit_button_attr = 'name="saveData" value="'.__('Update').'" class="s-btn btn btn-primary"';
  }

  /* Form Element(s) */
  // Server name
  $form->addTextField('text', 'serverName', __('Server Name').'*', $rec_d['name']??'', 'style="width: 50%;" class="form-control"');
  // Server URI
  $form->addTextField('text', 'serverUri', __('URI').'*', $rec_d['uri']??'', 'class="form-control"');
  // Server type
  $form->addSelectList('serverType', __('Server Type'), $serverType, $rec_d['server_type']??'','class="form-control col-3"');

  // edit mode messagge
  if ($form->edit_mode) {
      echo '<div class="infoBox">'.__('You are going to edit server data').' : <b>'.$rec_d['name'].'</b>  <br />'.__('Last Update').' '.$rec_d['last_update'].'</div>'; //mfc
  }
  // print out the form object
  echo $form->printOut();

} else {
  // table spec
  $table_spec = 'mst_servers AS ms';
  
  // create datagrid
  $datagrid = new simbio_datagrid();
  if ($can_read AND $can_write) {
    $datagrid->setSQLColumn('ms.server_id',
      'ms.name AS \''.__('Server Name').'\'',
      'ms.uri AS \''.__('URI').'\'',
      'ms.server_type AS \''.__('SERVER').'\'',
      'ms.last_update AS \''.__('Last Update').'\'');
  } else {
    $datagrid->setSQLColumn(
      'ms.name AS \''.__('Server Name').'\'',
      'ms.uri AS \''.__('URI').'\'',
      'ms.server_type AS \''.__('SERVER').'\'',
      'ms.last_update AS \''.__('Last Update').'\'');
  }
  $datagrid->setSQLorder('name ASC');
  // criteria
  $criteria = 'ms.server_id IS NOT NULL';
  // is there any search
  if (isset($_GET['keywords']) AND $_GET['keywords']) {}
  $datagrid->setSQLCriteria($criteria);

  // set table and table header attributes
  $datagrid->table_attr = 'id="dataList" class="s-table table"';
  $datagrid->table_header_attr = 'class="dataListHeader" style="font-weight: bold;"';
  // set delete proccess URL
  $datagrid->chbox_form_URL = $_SERVER['PHP_SELF'];

  // callback function to change value of authority type
  function callbackServerType($obj_db, $rec_d)
  {
      global $sysconf;
	  return $sysconf['p2pserver_type'][$rec_d[3]];
  }
  // modify column content
  $datagrid->modifyColumnContent(3, 'callback{callbackServerType}');

  // put the result into variables
  $datagrid_result = $datagrid->createDataGrid($dbs, $table_spec, 20, ($can_read AND $can_write));
  if (isset($_GET['keywords']) AND $_GET['keywords']) {
      $msg = str_replace('{result->num_rows}', $datagrid->num_rows, __('Found <strong>{result->num_rows}</strong> from your keywords')); //mfc
      echo '<div class="infoBox">'.$msg.' : "'.htmlspecialchars($_GET['keywords']).'"</div>';
  }
  // print datagrid
  echo $datagrid_result;
}
