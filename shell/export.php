<?php
/**
 * Import/Export from Shell
 *
 * @category Popov
 * @package Popov_Shell
 * @author Serhii Popov <popow.serhii@gmail.com>
 * @datetime: 10.04.14 12:20
 * @link http://blog.variux.com/?p=124
 * @link http://magento.stackexchange.com/a/45671
 * @link http://phpmysqltalk.com/1718-magento-dataflow-exportimport-form-the-command-line.html
 */
/**
 * Import/Export Script to run Import/Export profile
 * from command line or cron. Cleans entries from dataflow_batch_(import|export) table
 */
require_once 'abstract.php';

class Mage_Shell_Export extends Mage_Shell_Abstract {

	/** @var  Import/Export log file */
	protected $logFile;

	public function _construct() {
		$this->logFile = Mage::getBaseDir() . '/var/log/export_data.log';

		return parent::_construct();
	}

	public function run() {
		/** Magento Import/Export Profiles */
		$profileId = $this->getArg('profile');
		if ($profileId) {
			$this->log('Starting...');

			/** @var Mage_Dataflow_Model_Profile $profile */
			$profile = Mage::getModel('dataflow/profile');
			$profile->load($profileId);
			if (!$profile->getId()) {
				Mage::throwException('ERROR: Incorrect Profile for id ' . $profileId);
			}

			Mage::register('current_convert_profile', $profile);

			$profile->run();

			$batchModel = Mage::getSingleton('dataflow/batch');
			$this->log('Export Complete. ProfileID: ' . $profileId . '. BatchID: ' . $batchModel->getId());
			
			/** Connect to Magento database */
			sleep(30);

			$config  = Mage::getConfig()->getResourceConnectionConfig('default_setup');
			$db['host'] = $config->host;
			$db['name'] = $config->dbname;
			$db['user'] = $config->username;
			$db['pass'] = $config->password;
			$db['pref'] = $config->table_prefix;

			/** @todo Use Magento workflow */
			mysql_connect($db['host'], $db['user'], $db['pass']) or die(mysql_error());
			mysql_select_db($db['name']) or die(mysql_error());

			/**
			 * Truncate dataflow_batch_(import|export) table for housecleaning
			 *
			 * Post run housekeeping table bloat removal
			 * imports use "dataflow_batch_import" table
			 * exports use "dataflow_batch_export" table
			 */
			$table = 'dataflow_batch_export';
			$queryString = 'TRUNCATE ' . $db['pref'] . $table;

			mysql_query($queryString) or die(mysql_error());
		
			$this->log('Completed!');
		} else {
			echo $this->usageHelp();
		}
	}
	
	protected function log($msg) {
		echo $msg . "\n";
		Mage::log($msg, null, $this->logFile);
	}

	/**
	 * Retrieve Usage Help Message
	 */
	public function usageHelp()	{
		return <<<USAGE
Usage:  php -f export.php -- [options]

  --profile <identifier>            Profile ID from System > Import/Export > Profiles

USAGE;
	}

}

$shell = new Mage_Shell_Export();
$shell->run();
