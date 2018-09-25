# Magento CMD Export
Magento command line export

Add script to shell/ dicrectory in your magento project.

Give your ```profile ID``` as an argument to the script (System > Import/Export > Profiles get profile ID from).

Run as 

```
php shell/export.php --profile 1
```

After that, you can find file in var/export/

## Trick and Treat
1. If you get error like this `PHP Fatal error:  Allowed memory size of...`:

 a. Magento parse `.httaccess` in project root before run any CLI command. 
 This is not obviously but you need comment next two line in that file
 ```php
 ...
 ## adjust memory limit
 
 #php_value memory_limit 256M
 #php_value max_execution_time 18000
 ...
 ```
 Uncomment those two line after script execution.
 
 > If you want make sure in this information, please, check method `Mage_Shell_Abstract::_applyPhpVariables` 
 
  
 b. Run command as
```
php -d memory_limit=-1 shell/export.php --profile 1
```