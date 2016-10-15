# jUpgradeNext
Next generation for Joomla! migrations.

## Installation

```
git clone https://github.com/matware-lab/jUpgradeNext
cd jUpgradeNext
composer update
```
or
```
composer update matware-lab/jupgradenext
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
./bin/jUpgradeNext
```
