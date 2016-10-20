# jUpgradeNext

Next generation for Joomla! migrations.

**jUpgradeNext** uses two methods to migrate:

♫ Database

Read data directly from the database.

♫ RESTful

This method allows you to migrate data between two installations of Joomla! by webservices, which adds ease when setting the extension.

## Versions of Joomla! supported

Different versions of Joomla! They are supported by this extension:

⊚ 1.0 ↠ 2.5, 3.0+ ※ Only supported database method

⊚ 1.5 ↠ 2.5, 3.0+ ※ Both methods supported

⊚ 2.5 ↠ 2.5, 3.0+ ※ Both methods supported

⊚ 3.0+ ↠ 2.5, 3.0+ ※ Both methods supported. Downgrade available.

## Requirements

1. PHP **^5.3.10|~7.0**
2. Composer **^1.1**
3. MySQL **5.5.3 +**

## Installation

```
$ git clone https://github.com/matware-lab/jUpgradeNext
$ cd jUpgradeNext
$ composer update
```

## Configuration

You should edit the config.json.dist file on /etc directory and rename to /etc/config.json. Into this file you can set your database of your current site (the site installed empty that receive the data) and your old site (the site that you have your old data and like to migrate).

```
{
	"method" : "database",
	"database": {
		"driver"   : "mysqli",
		"host"     : "localhost",
		"user"     : "",
		"password" : "",
		"database" : "",
		"prefix"   : "jos_",
		"debug"    : false
	},
	"ext_database": {
		"driver"   : "mysqli",
		"host"     : "localhost",
		"user"     : "",
		"password" : "",
		"database" : "",
		"prefix"   : "jos_",
		"debug"    : false
	},
	"chunk_limit" : 100,
	"positions" : 0,
	"keep_ids" : 0,
	"skip_core_users" : false,
	"skip_core_categories" : false,
	"skip_core_contents" : false,
	"skip_core_contents_frontpage" : false,
	"skip_core_menus" : false,
	"skip_core_menus_types" : false,
	"skip_core_modules" : false,
	"skip_core_modules_menu" : false,
	"skip_core_banners" : false,
	"skip_core_banners_clients" : false,
	"skip_core_banners_tracks" : false,
	"skip_core_contacts" : false,
	"skip_core_newsfeeds" : false,
	"skip_core_weblinks" : false,
	"debug" : false,
	"logger" : {
		"channel" : "jUpgradeNext"
	}
}
```

Note that **database** is your current site and **ext_database** your old site.

## Migrate

When you have all configured run this command:

```
$ ./bin/jUpgradeNext

 jUpgradeNext 1.0

 Author: Matias Aguirre (maguirre@matware.com.ar)
 URL: http://www.matware.com.ar
 License: GNU/GPL http://www.gnu.org/licenses/gpl-2.0-standalone.html

-------------------------------------------------------------------------------------------------
|  	Migrating Joomla! 3.3 core data to Joomla! 3.5
-------------------------------------------------------------------------------------------------
|  [141] Migrating users (Start:0 - Stop: 1 - Total: 2)
|  [••]
|  [Benchmark] 0.007 seconds.
-------------------------------------------------------------------------------------------------
|  [142] Migrating usergroupmap (Start:0 - Stop: 1 - Total: 2)
|  [••]
|  [Benchmark] 0.004 seconds.
-------------------------------------------------------------------------------------------------
|  [143] Migrating categories (Start:0 - Stop: 23 - Total: 24)
|  [••••••••••••••••••••••••]
|  [Benchmark] 0.277 seconds.
-------------------------------------------------------------------------------------------------
|  [144] Migrating contents (Start:0 - Stop: 23 - Total: 24)
|  [•••••••••••••••••••••••••••••]
|  [Benchmark] 0.343 seconds.
-------------------------------------------------------------------------------------------------
|  [145] Migrating contents_frontpage (Start:0 - Stop: 28 - Total: 29)
|  [•]
|  [Benchmark] 0.003 seconds.
-------------------------------------------------------------------------------------------------
|  [146] Migrating menus (Start:0 - Stop: 0 - Total: 1)
|  [••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••]
|  [Benchmark] 0.373 seconds.
-------------------------------------------------------------------------------------------------
|  [147] Migrating menus_types (Start:0 - Stop: 99 - Total: 126)
|  [•••••••••]
|  [Benchmark] 0.011 seconds.
-------------------------------------------------------------------------------------------------
|  [148] Migrating modules (Start:0 - Stop: 55 - Total: 56)
|  [••••••••••••••••••••••••••••••••••••••••••••••••••••••••]
|  [Benchmark] 0.216 seconds.
-------------------------------------------------------------------------------------------------
|  [149] Migrating modules_menu (Start:0 - Stop: 99 - Total: 271)
|  [••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••]
|  [Benchmark] 1.005 seconds.
-------------------------------------------------------------------------------------------------
|  [149] Migrating modules_menu (Start:0 - Stop: 99 - Total: 271)
|  [••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••]
|  [Benchmark] 0.176 seconds.
-------------------------------------------------------------------------------------------------
|  [149] Migrating modules_menu (Start:0 - Stop: 99 - Total: 271)
|  [•••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••]
|  [Benchmark] 0.118 seconds.
-------------------------------------------------------------------------------------------------
|  [150] Migrating banners (Start:0 - Stop: 7 - Total: 8)
|  [••••••••]
|  [Benchmark] 0.011 seconds.
-------------------------------------------------------------------------------------------------
|  [151] Migrating banners_clients (Start:0 - Stop: 7 - Total: 8)
|  []
|  [Benchmark] 0.001 seconds.
-------------------------------------------------------------------------------------------------
|  [152] Migrating banners_tracks (Start:0 - Stop: -1 - Total: 0)
|  []
|  [Benchmark] 0 seconds.
-------------------------------------------------------------------------------------------------
|  [153] Migrating contacts (Start:0 - Stop: -1 - Total: 0)
|  []
|  [Benchmark] 0 seconds.
-------------------------------------------------------------------------------------------------
|  [154] Migrating newsfeeds (Start:0 - Stop: 10 - Total: 11)
|  [•••••••••••]
|  [Benchmark] 0.014 seconds.
-------------------------------------------------------------------------------------------------
|  [155] Migrating weblinks (Start:0 - Stop: 27 - Total: 28)
|  [••••••••••••••••••••••••••••]
|  [Benchmark] 0.037 seconds.
-------------------------------------------------------------------------------------------------
|  [155] Migrating weblinks (Start:0 - Stop: 27 - Total: 28)
|  []
|  [Benchmark] 0.002 seconds.
-------------------------------------------------------------------------------------------------

[[TOTAL Benchmark]] 2.762 seconds
```
