# Team Planer

A simple calendar allowing to plan recurring events in teams (e.g. shift planing).

## Requirements

   * Webserver (e.g. Apache)
   * PHP 5.3 or newer
   * MySQL
   * [bower](http://bower.io) (to download frontend JavaScript dependencies)

## Installation

   * Clone this repository: **git clone https://github.com/Programie/TeamPlaner.git**
   * Execute **php bin/update-config.php** to create or update your configuration file
   * Edit the **config.json** file inside of the **config** folder (See section **Configuration** for details)
   * Import **database.sql** into your MySQL database
   * Execute **bin/build.sh** to build the application
   * Configure your webserver
      * Point your document root to the **frontend** folder
      * Create an alias **service** pointing to the **service** folder
   * Create the users for your team (See section **User management** for details)
   * Use it

## Configuration

The configuration is done in a simple JSON file **config.json** which is located in the **config** folder. You can create or update it by executing **php bin/update-config.php**.

## User management

Currently you have to manage users directly in the database. A easier user management is planed for a future release.

### Create a new user

   * Connect to your database
   * Create a new entry in the **users** table
      * id: NULL or omit the field (Use next auto increment value)
      * username: The username you want to use
      * additionalInfo: Any additional information for the user (or NULL or omit the field)

### Remove an existing user

   * Connect to your database
   * Delete the entry of the user you want to remove