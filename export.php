<?php
/**
 * Import/Export from Shell
 *
 * @category Agere
 * @package Agere_Shell
 * @author Popov Sergiy <popov@agere.com.ua>
 * @datetime: 10.04.14 12:20
 * @link http://blog.variux.com/?p=124
 * @link http://phpmysqltalk.com/1718-magento-dataflow-exportimport-form-the-command-line.html
 */

/**
 * Import/Export Script to run Import/Export profile
 * from command line or cron. 
 * Cleans entries from dataflow_batch_(import|export) table
 */
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);

define('MAGENTO_ROOT', dirname(__DIR__));

$mageconf = MAGENTO_ROOT . '/app/etc/local.xml';  // Mage local.xml config
$mageapp = MAGENTO_ROOT . '/app/Mage.php';       // Mage app location
$logfile = MAGENTO_ROOT . '/var/log/export_data.log';      // Import/Export log file

/**
 * uncomment following block when moved to server - to ensure this page is
 * not accessed from anywhere else
 */
//if ($_SERVER['REMOTE_ADDR'] !== '<your server ip address>') {
//   die("You are not a cron job!");
//}

/**
 * System > Import/Export > Profiles get profile ID from
 * Magento Import/Export Profiles
 */
$profileId = 1;

/**
 * Post run housekeeping table bloat removal
 * imports use "dataflow_batch_import" table
 * exports use "dataflow_batch_export" table
 */
$table = 'dataflow_batch_export';

/* Scan Magento local.xml file for connection information */
if (!file_exists($mageconf)) {
	Mage::log('Export script failed to open Mage local.xml', null, $logfile);
	exit('Failed to open Mage local.xml');
}

/* Initialize profile to be run as Magento Admin and log start of export */
require_once $mageapp;
umask(0);
Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

$profile = Mage::getModel('dataflow/profile');
$userModel = Mage::getModel('admin/user');
$userModel->setUserId(0);
Mage::getSingleton('admin/session')->setUser($userModel);
$profile->load($profileId);
if (!$profile->getId()) {
	Mage::getSingleton('adminhtml/session')->addError('ERROR: Incorrect profile id');
}

Mage::log('Export ' . $profileId . ' Started.', null, $logfile);

Mage::register('current_convert_profile', $profile);
$profile->run();
$recordCount = 0;
$batchModel = Mage::getSingleton('dataflow/batch');

Mage::log('Export ' . $profileId . ' Complete. BatchID: ' . $batchModel->getId(), null, $logfile);

echo "Export Complete. BatchID: " . $batchModel->getId() . "\n";

/* Connect to Magento database */
sleep(30);

$xml = simplexml_load_file($mageconf, NULL, LIBXML_NOCDATA);

$db['host'] = $xml->global->resources->default_setup->connection->host;
$db['name'] = $xml->global->resources->default_setup->connection->dbname;
$db['user'] = $xml->global->resources->default_setup->connection->username;
$db['pass'] = $xml->global->resources->default_setup->connection->password;
$db['pref'] = $xml->global->resources->db->table_prefix;

mysql_connect($db['host'], $db['user'], $db['pass']) or die(mysql_error());
mysql_select_db($db['name']) or die(mysql_error());

/* Truncate dataflow_batch_(import|export) table for housecleaning */
$querystring = "TRUNCATE " . $db['pref'] . $table;

mysql_query($querystring) or die(mysql_error());
