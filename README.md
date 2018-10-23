# EasyMigrate

This is a code igniter starter project with a custom migrate functionality.
This functon is provided with an additional controller which can only be accessed from the command line
Controller is in application/controller/console


## Pre Requisites

You need to establish connection to a database with all basic privileges.
Change configuration in application/config/database.php


## Commands to use migrate

### 1. php index.php console migrate generate <name>
Generates a php file in applicaion/migrations/ folder
Write the database change commands in the up method
Write the reverse of those changes in the down method

Eg: create table in up method then drop table in down method

### 2. php index.php console migrate version <version number>
##### Vesion number is the timestamp at the beginning of the files
This function moves the database to the specified version.
If the current version is greater that the specified version, the database will be reverted back.

###### Warning: If you have merged with someone else's database changes (changed your application/migration directory to also include the database change done by someone else) and your current version is greater than their version and you try to go to their version number, this will generate an error as this will try to revert some change that has not been made. Use command 4 in that case

### 3. php index.php console migrate latest_version
This function moves the database to the highest version available.
This will check the current version and latest version available and make all the changes from current verion to latest version

###### Warning: If you have merged with someone else's database changes (changed your application/migration directory to also include the database change done by someone else) and your current version is greater than their version and you try to go to the latest version, it will not reflect the other person's changes. Use command 4 for that

### 4. php index.php console migrate upgrade
This function checks all the files from which changed have not been applied to the database and those changes are applied.

###### Warning: Conflicting commands by different people like creating a table twice etc will generate an error.


## Use it in your project
If you don't want to start a fresh project and just want to use the functionality.

###1. Copy and paste config folder from application/controller directory to your own application/controller directory

###2. Copy and paste MY_Migration from application/libraries to your own application/libraries

###3. Copy and paste cli folder from application/views to your application/views folder

###4. Change 