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
1. If you get error like this `PHP Fatal error:  Allowed memory size of...` try run command as
```
php -d memory_limit=-1 shell/export.php --profile 1
```
