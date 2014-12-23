# Team Planer

A simple calendar allowing to plan recurring events in teams (e.g. shift planing).

## Installation

   * Clone this repository: **git clone https://github.com/Programie/TeamPlaner**
   * Create a **config.json** file inside of the **config** folder (See section **Configuguration** for details)
   * Use it

## Configuration

The configuration is done in a simple JSON file **config.json** which is located in the config folder. On first installation you have to create that file yourself.

### Structure

```json
{
	"userAuth" : "None",
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
			"color" : "lightgreen"
		},
		{
			"name" : "night",
			"title" : "Night",
			"color" : "indianred"
		},
		{
			"name" : "holiday",
			"title" : "Holiday",
			"color" : "orange"
		}
	],
	"colors" :
	{
		"weekend" : "lightskyblue"
	}
}
```
