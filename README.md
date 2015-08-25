# Team Planer

A simple calendar allowing to plan recurring events in teams (e.g. shift planing).

## Requirements

   * Webserver (e.g. Apache)
   * PHP 5.4 or newer
   * MySQL
   * [bower](http://bower.io) (to download frontend JavaScript dependencies)

## Installation

   * Clone this repository: **git clone https://github.com/Programie/TeamPlaner.git**
   * Execute **bin/build.sh** to build the application
   * Execute **php bin/update-config.php** to create or update your configuration file
   * Edit the **config.json** file inside of the **config** folder (See section **Configuration** for details)
   * Import **database.sql** into your MySQL database
   * Configure your webserver so your document root points to the **httpdocs** folder
   * Create the users for your team (See section **User management** for details)
   * Use it

## Configuration

The configuration is done in a simple JSON file **config.json** which is located in the **config** folder. You can create or update it by executing **php bin/update-config.php**.

## User management

Currently you have to manage users directly in the database. An easier user management is planed for one of the next releases.

### Create a new user

   * Connect to your database
   * Create a new entry in the **teams** table
      * id: NULL or omit the field (Use next auto increment value)
      * name: An unique name not containing any special characters (especially **NOT** a slash "/")
      * title: The title for the team
   * Create an entry for each user in the **users** table
      * id: NULL or omit the field (Use next auto increment value)
      * username: The username you want to use
      * additionalInfo: Any additional information for the user (or NULL or omit the field)
      * token: Any 32 character string which can be used for authorization (set to NULL or omit the field to disable token authorization)
   * Create an entry for each team member in the **teammembers** table
      * id: NULL or omit the field (Use next auto increment value)
      * teamId: id from the **teams** table
      * userId: id from the **users** table
      * startDate: NULL or a date in format "YYYY-MM-DD" on which the user joins in the team
      * endDate: NULL or a date in format "YYYY-MM-DD" on which the user leaves the team

### Remove an existing user

   * Connect to your database
   * Delete the entry of the user you want to remove from the **users** table