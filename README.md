# Team Planer

A simple calendar allowing to plan recurring events in teams (e.g. shift planing).

## Requirements

   * Webserver (e.g. Apache)
   * PHP 5.3 or newer
   * MySQL

## Installation

   * Clone this repository: **git clone https://github.com/Programie/TeamPlaner.git**
   * Create a **config.json** file inside of the **config** folder (See section **Configuration** for details)
   * Import **database.sql** into your MySQL database
   * Configure your webserver
      * Point your document root to the frontend folder
      * Create an alias **service** pointing to the service folder
   * Use it

## Configuration

The configuration is done in a simple JSON file **config.json** which is located in the config folder. On first installation you have to create that file yourself.

### Structure

Here you can the default configuration:

```json
{
	"userAuth" : "",
	"reportClass" : null,
	"holidaysMethod" : null,
	"database" :
	{
		"dsn" : "mysql:host=127.0.0.1;dbname=calendar",
		"username" : "root",
		"password" : ""
	},
	"types" :
	[
		{
			"name" : "none",
			"title" : "None",
			"color" : "white",
			"noSave" : true
		},
		{
			"name" : "day",
			"title" : "Day",
			"color" : "lightgreen",
			"showInReport" : true
		},
		{
			"name" : "night",
			"title" : "Night",
			"color" : "indianred",
			"showInReport" : true
		},
		{
			"name" : "holiday",
			"title" : "Holiday",
			"color" : "orange"
		}
	],
	"colors" :
	{
		"holiday" : "dodgerblue",
		"weekend" : "lightskyblue"
	}
}
```
