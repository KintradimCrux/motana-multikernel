Motana Multi-Kernel App Console - Symfony `[symfony-version]` (kernel: app, env: test, debug: false)
========================================================================================

Commands
--------

* about
* help
* list

**assets:**

* assets:install

**cache:**

* cache:clear
* cache:pool:clear
* cache:warmup

**config:**

* config:dump-reference

**debug:**

* debug:config
* debug:container
* debug:event-dispatcher
* debug:translation

**lint:**

* lint:xliff
* lint:yaml

**server:**

* server:log
* server:run
* server:start
* server:status
* server:stop

**translation:**

* translation:update

Command "about"
---------------

* Description: Displays information about the current project
* Usage:

  * `bin/console app about [options]`

Displays information about the current project

### Options:

**help:**

* Name: `--help`
* Shortcut: `-h`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Display this help message
* Default: `false`

**quiet:**

* Name: `--quiet`
* Shortcut: `-q`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Do not output any message
* Default: `false`

**verbose:**

* Name: `--verbose`
* Shortcut: `-v|-vv|-vvv`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
* Default: `false`

**version:**

* Name: `--version`
* Shortcut: `-V`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Display this application version
* Default: `false`

**ansi:**

* Name: `--ansi`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Force ANSI output
* Default: `false`

**no-ansi:**

* Name: `--no-ansi`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Disable ANSI output
* Default: `false`

**no-interaction:**

* Name: `--no-interaction`
* Shortcut: `-n`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Do not ask any interactive question
* Default: `false`

**env:**

* Name: `--env`
* Shortcut: `-e`
* Accepts value: yes
* Is value required: yes
* Is multiple: no
* Description: The environment name
* Default: `'test'`

**no-debug:**

* Name: `--no-debug`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Switches off debug mode
* Default: `false`

Command "help"
--------------

* Description: Displays help for a command
* Usage:

  * `bin/console app help [options] [--] [<command_name>]`
  * `bin/console app help:help`

The `help` command displays help for a given command:

  `bin/console help list`

You can also output the help in other formats by using the --format option:

  `bin/console help --format=xml list`

To display the list of available commands, please use the `list` command.

### Arguments:

**command_name:**

* Name: command_name
* Is required: no
* Is array: no
* Description: The command name
* Default: `'help'`

### Options:

**format:**

* Name: `--format`
* Shortcut: <none>
* Accepts value: yes
* Is value required: yes
* Is multiple: no
* Description: The output format (txt, xml, json, or md)
* Default: `'txt'`

**raw:**

* Name: `--raw`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: To output raw command help
* Default: `false`

**help:**

* Name: `--help`
* Shortcut: `-h`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Display this help message
* Default: `false`

**quiet:**

* Name: `--quiet`
* Shortcut: `-q`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Do not output any message
* Default: `false`

**verbose:**

* Name: `--verbose`
* Shortcut: `-v|-vv|-vvv`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
* Default: `false`

**version:**

* Name: `--version`
* Shortcut: `-V`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Display this application version
* Default: `false`

**ansi:**

* Name: `--ansi`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Force ANSI output
* Default: `false`

**no-ansi:**

* Name: `--no-ansi`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Disable ANSI output
* Default: `false`

**no-interaction:**

* Name: `--no-interaction`
* Shortcut: `-n`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Do not ask any interactive question
* Default: `false`

**env:**

* Name: `--env`
* Shortcut: `-e`
* Accepts value: yes
* Is value required: yes
* Is multiple: no
* Description: The environment name
* Default: `'test'`

**no-debug:**

* Name: `--no-debug`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Switches off debug mode
* Default: `false`

Command "list"
--------------

* Description: Lists commands
* Usage:

  * `bin/console app list [options] [--] [<namespace>]`

The `list` command lists all commands:

  `bin/console list`

You can also display the commands for a specific namespace:

  `bin/console list test`

You can also output the information in other formats by using the --format option:

  `bin/console list --format=xml`

It's also possible to get raw list of commands (useful for embedding command runner):

  `bin/console list --raw`

### Arguments:

**namespace:**

* Name: namespace
* Is required: no
* Is array: no
* Description: The namespace name
* Default: `NULL`

### Options:

**raw:**

* Name: `--raw`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: To output raw command list
* Default: `false`

**format:**

* Name: `--format`
* Shortcut: <none>
* Accepts value: yes
* Is value required: yes
* Is multiple: no
* Description: The output format (txt, xml, json, or md)
* Default: `'txt'`

Command "assets:install"
------------------------

* Description: Installs bundles web assets under a public web directory
* Usage:

  * `bin/console app assets:install [options] [--] [<target>]`

The `assets:install` command installs bundle assets into a given
directory (e.g. the web directory).

  `bin/console assets:install web`

A "bundles" directory will be created inside the target directory and the
"Resources/public" directory of each bundle will be copied into it.

To create a symlink to each bundle instead of copying its assets, use the
`--symlink` option (will fall back to hard copies when symbolic links aren't possible:

  `bin/console assets:install web --symlink`

To make symlink relative, add the `--relative` option:

  `bin/console assets:install web --symlink --relative`

### Arguments:

**target:**

* Name: target
* Is required: no
* Is array: no
* Description: The target directory
* Default: `'web'`

### Options:

**symlink:**

* Name: `--symlink`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Symlinks the assets instead of copying it
* Default: `false`

**relative:**

* Name: `--relative`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Make relative symlinks
* Default: `false`

**help:**

* Name: `--help`
* Shortcut: `-h`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Display this help message
* Default: `false`

**quiet:**

* Name: `--quiet`
* Shortcut: `-q`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Do not output any message
* Default: `false`

**verbose:**

* Name: `--verbose`
* Shortcut: `-v|-vv|-vvv`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
* Default: `false`

**version:**

* Name: `--version`
* Shortcut: `-V`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Display this application version
* Default: `false`

**ansi:**

* Name: `--ansi`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Force ANSI output
* Default: `false`

**no-ansi:**

* Name: `--no-ansi`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Disable ANSI output
* Default: `false`

**no-interaction:**

* Name: `--no-interaction`
* Shortcut: `-n`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Do not ask any interactive question
* Default: `false`

**env:**

* Name: `--env`
* Shortcut: `-e`
* Accepts value: yes
* Is value required: yes
* Is multiple: no
* Description: The environment name
* Default: `'test'`

**no-debug:**

* Name: `--no-debug`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Switches off debug mode
* Default: `false`

Command "cache:clear"
---------------------

* Description: Clears the cache
* Usage:

  * `bin/console app cache:clear [options]`

The `cache:clear` command clears the application cache for a given environment
and debug mode:

  `bin/console cache:clear --env=dev`
  `bin/console cache:clear --env=prod --no-debug`

### Options:

**no-warmup:**

* Name: `--no-warmup`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Do not warm up the cache
* Default: `false`

**no-optional-warmers:**

* Name: `--no-optional-warmers`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Skip optional cache warmers (faster)
* Default: `false`

**help:**

* Name: `--help`
* Shortcut: `-h`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Display this help message
* Default: `false`

**quiet:**

* Name: `--quiet`
* Shortcut: `-q`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Do not output any message
* Default: `false`

**verbose:**

* Name: `--verbose`
* Shortcut: `-v|-vv|-vvv`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
* Default: `false`

**version:**

* Name: `--version`
* Shortcut: `-V`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Display this application version
* Default: `false`

**ansi:**

* Name: `--ansi`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Force ANSI output
* Default: `false`

**no-ansi:**

* Name: `--no-ansi`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Disable ANSI output
* Default: `false`

**no-interaction:**

* Name: `--no-interaction`
* Shortcut: `-n`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Do not ask any interactive question
* Default: `false`

**env:**

* Name: `--env`
* Shortcut: `-e`
* Accepts value: yes
* Is value required: yes
* Is multiple: no
* Description: The environment name
* Default: `'test'`

**no-debug:**

* Name: `--no-debug`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Switches off debug mode
* Default: `false`

Command "cache:pool:clear"
--------------------------

* Description: Clears cache pools
* Usage:

  * `bin/console app cache:pool:clear [options] [--] <pools> (<pools>)...`

The `cache:pool:clear` command clears the given cache pools or cache pool clearers.

    bin/console cache:pool:clear  [...]

### Arguments:

**pools:**

* Name: pools
* Is required: yes
* Is array: yes
* Description: A list of cache pools or cache pool clearers
* Default: `array ()`

### Options:

**help:**

* Name: `--help`
* Shortcut: `-h`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Display this help message
* Default: `false`

**quiet:**

* Name: `--quiet`
* Shortcut: `-q`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Do not output any message
* Default: `false`

**verbose:**

* Name: `--verbose`
* Shortcut: `-v|-vv|-vvv`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
* Default: `false`

**version:**

* Name: `--version`
* Shortcut: `-V`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Display this application version
* Default: `false`

**ansi:**

* Name: `--ansi`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Force ANSI output
* Default: `false`

**no-ansi:**

* Name: `--no-ansi`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Disable ANSI output
* Default: `false`

**no-interaction:**

* Name: `--no-interaction`
* Shortcut: `-n`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Do not ask any interactive question
* Default: `false`

**env:**

* Name: `--env`
* Shortcut: `-e`
* Accepts value: yes
* Is value required: yes
* Is multiple: no
* Description: The environment name
* Default: `'test'`

**no-debug:**

* Name: `--no-debug`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Switches off debug mode
* Default: `false`

Command "cache:warmup"
----------------------

* Description: Warms up an empty cache
* Usage:

  * `bin/console app cache:warmup [options]`

The `cache:warmup` command warms up the cache.

Before running this command, the cache must be empty.

This command does not generate the classes cache (as when executing this
command, too many classes that should be part of the cache are already loaded
in memory). Use curl or any other similar tool to warm up
the classes cache if you want.

### Options:

**no-optional-warmers:**

* Name: `--no-optional-warmers`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Skip optional cache warmers (faster)
* Default: `false`

**help:**

* Name: `--help`
* Shortcut: `-h`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Display this help message
* Default: `false`

**quiet:**

* Name: `--quiet`
* Shortcut: `-q`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Do not output any message
* Default: `false`

**verbose:**

* Name: `--verbose`
* Shortcut: `-v|-vv|-vvv`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
* Default: `false`

**version:**

* Name: `--version`
* Shortcut: `-V`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Display this application version
* Default: `false`

**ansi:**

* Name: `--ansi`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Force ANSI output
* Default: `false`

**no-ansi:**

* Name: `--no-ansi`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Disable ANSI output
* Default: `false`

**no-interaction:**

* Name: `--no-interaction`
* Shortcut: `-n`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Do not ask any interactive question
* Default: `false`

**env:**

* Name: `--env`
* Shortcut: `-e`
* Accepts value: yes
* Is value required: yes
* Is multiple: no
* Description: The environment name
* Default: `'test'`

**no-debug:**

* Name: `--no-debug`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Switches off debug mode
* Default: `false`

Command "config:dump-reference"
-------------------------------

* Description: Dumps the default configuration for an extension
* Usage:

  * `bin/console app config:dump-reference [options] [--] [<name>] [<path>]`

The `config:dump-reference` command dumps the default configuration for an
extension/bundle.

Either the extension alias or bundle name can be used:

  `bin/console config:dump-reference framework`
  `bin/console config:dump-reference FrameworkBundle`

With the `--format` option specifies the format of the configuration,
this is either yaml or xml.
When the option is not provided, yaml is used.

  `bin/console config:dump-reference FrameworkBundle --format=xml`

For dumping a specific option, add its path as second argument (only available for the yaml format):

  `bin/console config:dump-reference framework profiler.matcher`

### Arguments:

**name:**

* Name: name
* Is required: no
* Is array: no
* Description: The Bundle name or the extension alias
* Default: `NULL`

**path:**

* Name: path
* Is required: no
* Is array: no
* Description: The configuration option path
* Default: `NULL`

### Options:

**format:**

* Name: `--format`
* Shortcut: <none>
* Accepts value: yes
* Is value required: yes
* Is multiple: no
* Description: The output format (yaml or xml)
* Default: `'yaml'`

**help:**

* Name: `--help`
* Shortcut: `-h`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Display this help message
* Default: `false`

**quiet:**

* Name: `--quiet`
* Shortcut: `-q`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Do not output any message
* Default: `false`

**verbose:**

* Name: `--verbose`
* Shortcut: `-v|-vv|-vvv`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
* Default: `false`

**version:**

* Name: `--version`
* Shortcut: `-V`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Display this application version
* Default: `false`

**ansi:**

* Name: `--ansi`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Force ANSI output
* Default: `false`

**no-ansi:**

* Name: `--no-ansi`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Disable ANSI output
* Default: `false`

**no-interaction:**

* Name: `--no-interaction`
* Shortcut: `-n`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Do not ask any interactive question
* Default: `false`

**env:**

* Name: `--env`
* Shortcut: `-e`
* Accepts value: yes
* Is value required: yes
* Is multiple: no
* Description: The environment name
* Default: `'test'`

**no-debug:**

* Name: `--no-debug`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Switches off debug mode
* Default: `false`

Command "debug:config"
----------------------

* Description: Dumps the current configuration for an extension
* Usage:

  * `bin/console app debug:config [options] [--] [<name>] [<path>]`

The `debug:config` command dumps the current configuration for an
extension/bundle.

Either the extension alias or bundle name can be used:

  `bin/console debug:config framework`
  `bin/console debug:config FrameworkBundle`

For dumping a specific option, add its path as second argument:

  `bin/console debug:config framework serializer.enabled`

### Arguments:

**name:**

* Name: name
* Is required: no
* Is array: no
* Description: The bundle name or the extension alias
* Default: `NULL`

**path:**

* Name: path
* Is required: no
* Is array: no
* Description: The configuration option path
* Default: `NULL`

### Options:

**help:**

* Name: `--help`
* Shortcut: `-h`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Display this help message
* Default: `false`

**quiet:**

* Name: `--quiet`
* Shortcut: `-q`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Do not output any message
* Default: `false`

**verbose:**

* Name: `--verbose`
* Shortcut: `-v|-vv|-vvv`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
* Default: `false`

**version:**

* Name: `--version`
* Shortcut: `-V`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Display this application version
* Default: `false`

**ansi:**

* Name: `--ansi`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Force ANSI output
* Default: `false`

**no-ansi:**

* Name: `--no-ansi`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Disable ANSI output
* Default: `false`

**no-interaction:**

* Name: `--no-interaction`
* Shortcut: `-n`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Do not ask any interactive question
* Default: `false`

**env:**

* Name: `--env`
* Shortcut: `-e`
* Accepts value: yes
* Is value required: yes
* Is multiple: no
* Description: The environment name
* Default: `'test'`

**no-debug:**

* Name: `--no-debug`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Switches off debug mode
* Default: `false`

Command "debug:container"
-------------------------

* Description: Displays current services for an application
* Usage:

  * `bin/console app debug:container [options] [--] [<name>]`

The `debug:container` command displays all configured public services:

  `bin/console debug:container`

To get specific information about a service, specify its name:

  `bin/console debug:container validator`

To see available types that can be used for autowiring, use the `--types` flag:

  `bin/console debug:container --types`

By default, private services are hidden. You can display all services by
using the `--show-private` flag:

  `bin/console debug:container --show-private`

Use the --tags option to display tagged public services grouped by tag:

  `bin/console debug:container --tags`

Find all services with a specific tag by specifying the tag name with the `--tag` option:

  `bin/console debug:container --tag=form.type`

Use the `--parameters` option to display all parameters:

  `bin/console debug:container --parameters`

Display a specific parameter by specifying its name with the `--parameter` option:

  `bin/console debug:container --parameter=kernel.debug`

### Arguments:

**name:**

* Name: name
* Is required: no
* Is array: no
* Description: A service name (foo)
* Default: `NULL`

### Options:

**show-private:**

* Name: `--show-private`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Used to show public *and* private services
* Default: `false`

**show-arguments:**

* Name: `--show-arguments`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Used to show arguments in services
* Default: `false`

**tag:**

* Name: `--tag`
* Shortcut: <none>
* Accepts value: yes
* Is value required: yes
* Is multiple: no
* Description: Shows all services with a specific tag
* Default: `NULL`

**tags:**

* Name: `--tags`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Displays tagged services for an application
* Default: `false`

**parameter:**

* Name: `--parameter`
* Shortcut: <none>
* Accepts value: yes
* Is value required: yes
* Is multiple: no
* Description: Displays a specific parameter for an application
* Default: `NULL`

**parameters:**

* Name: `--parameters`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Displays parameters for an application
* Default: `false`

**types:**

* Name: `--types`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Displays types (classes/interfaces) available in the container
* Default: `false`

**format:**

* Name: `--format`
* Shortcut: <none>
* Accepts value: yes
* Is value required: yes
* Is multiple: no
* Description: The output format (txt, xml, json, or md)
* Default: `'txt'`

**raw:**

* Name: `--raw`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: To output raw description
* Default: `false`

**help:**

* Name: `--help`
* Shortcut: `-h`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Display this help message
* Default: `false`

**quiet:**

* Name: `--quiet`
* Shortcut: `-q`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Do not output any message
* Default: `false`

**verbose:**

* Name: `--verbose`
* Shortcut: `-v|-vv|-vvv`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
* Default: `false`

**version:**

* Name: `--version`
* Shortcut: `-V`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Display this application version
* Default: `false`

**ansi:**

* Name: `--ansi`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Force ANSI output
* Default: `false`

**no-ansi:**

* Name: `--no-ansi`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Disable ANSI output
* Default: `false`

**no-interaction:**

* Name: `--no-interaction`
* Shortcut: `-n`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Do not ask any interactive question
* Default: `false`

**env:**

* Name: `--env`
* Shortcut: `-e`
* Accepts value: yes
* Is value required: yes
* Is multiple: no
* Description: The environment name
* Default: `'test'`

**no-debug:**

* Name: `--no-debug`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Switches off debug mode
* Default: `false`

Command "debug:event-dispatcher"
--------------------------------

* Description: Displays configured listeners for an application
* Usage:

  * `bin/console app debug:event-dispatcher [options] [--] [<event>]`

The `debug:event-dispatcher` command displays all configured listeners:

  `bin/console debug:event-dispatcher`

To get specific listeners for an event, specify its name:

  `bin/console debug:event-dispatcher kernel.request`

### Arguments:

**event:**

* Name: event
* Is required: no
* Is array: no
* Description: An event name
* Default: `NULL`

### Options:

**format:**

* Name: `--format`
* Shortcut: <none>
* Accepts value: yes
* Is value required: yes
* Is multiple: no
* Description: The output format  (txt, xml, json, or md)
* Default: `'txt'`

**raw:**

* Name: `--raw`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: To output raw description
* Default: `false`

**help:**

* Name: `--help`
* Shortcut: `-h`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Display this help message
* Default: `false`

**quiet:**

* Name: `--quiet`
* Shortcut: `-q`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Do not output any message
* Default: `false`

**verbose:**

* Name: `--verbose`
* Shortcut: `-v|-vv|-vvv`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
* Default: `false`

**version:**

* Name: `--version`
* Shortcut: `-V`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Display this application version
* Default: `false`

**ansi:**

* Name: `--ansi`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Force ANSI output
* Default: `false`

**no-ansi:**

* Name: `--no-ansi`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Disable ANSI output
* Default: `false`

**no-interaction:**

* Name: `--no-interaction`
* Shortcut: `-n`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Do not ask any interactive question
* Default: `false`

**env:**

* Name: `--env`
* Shortcut: `-e`
* Accepts value: yes
* Is value required: yes
* Is multiple: no
* Description: The environment name
* Default: `'test'`

**no-debug:**

* Name: `--no-debug`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Switches off debug mode
* Default: `false`

Command "debug:translation"
---------------------------

* Description: Displays translation messages information
* Usage:

  * `bin/console app debug:translation [options] [--] <locale> [<bundle>]`

The `debug:translation` command helps finding unused or missing translation
messages and comparing them with the fallback ones by inspecting the
templates and translation files of a given bundle or the app folder.

You can display information about bundle translations in a specific locale:

  `bin/console debug:translation en AcmeDemoBundle`

You can also specify a translation domain for the search:

  `bin/console debug:translation --domain=messages en AcmeDemoBundle`

You can only display missing messages:

  `bin/console debug:translation --only-missing en AcmeDemoBundle`

You can only display unused messages:

  `bin/console debug:translation --only-unused en AcmeDemoBundle`

You can display information about app translations in a specific locale:

  `bin/console debug:translation en`

You can display information about translations in all registered bundles in a specific locale:

  `bin/console debug:translation --all en`

### Arguments:

**locale:**

* Name: locale
* Is required: yes
* Is array: no
* Description: The locale
* Default: `NULL`

**bundle:**

* Name: bundle
* Is required: no
* Is array: no
* Description: The bundle name or directory where to load the messages, defaults to app/Resources folder
* Default: `NULL`

### Options:

**domain:**

* Name: `--domain`
* Shortcut: <none>
* Accepts value: yes
* Is value required: no
* Is multiple: no
* Description: The messages domain
* Default: `NULL`

**only-missing:**

* Name: `--only-missing`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Displays only missing messages
* Default: `false`

**only-unused:**

* Name: `--only-unused`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Displays only unused messages
* Default: `false`

**all:**

* Name: `--all`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Load messages from all registered bundles
* Default: `false`

**help:**

* Name: `--help`
* Shortcut: `-h`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Display this help message
* Default: `false`

**quiet:**

* Name: `--quiet`
* Shortcut: `-q`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Do not output any message
* Default: `false`

**verbose:**

* Name: `--verbose`
* Shortcut: `-v|-vv|-vvv`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
* Default: `false`

**version:**

* Name: `--version`
* Shortcut: `-V`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Display this application version
* Default: `false`

**ansi:**

* Name: `--ansi`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Force ANSI output
* Default: `false`

**no-ansi:**

* Name: `--no-ansi`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Disable ANSI output
* Default: `false`

**no-interaction:**

* Name: `--no-interaction`
* Shortcut: `-n`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Do not ask any interactive question
* Default: `false`

**env:**

* Name: `--env`
* Shortcut: `-e`
* Accepts value: yes
* Is value required: yes
* Is multiple: no
* Description: The environment name
* Default: `'test'`

**no-debug:**

* Name: `--no-debug`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Switches off debug mode
* Default: `false`

Command "lint:xliff"
--------------------

* Description: Lints a XLIFF file and outputs encountered errors
* Usage:

  * `bin/console app lint:xliff [options] [--] [<filename>]`

The `lint:xliff` command lints a XLIFF file and outputs to STDOUT
the first encountered syntax error.

You can validates XLIFF contents passed from STDIN:

  `cat filename | bin/console lint:xliff`

You can also validate the syntax of a file:

  `bin/console lint:xliff filename`

Or of a whole directory:

  `bin/console lint:xliff dirname`
  `bin/console lint:xliff dirname --format=json`

Or find all files in a bundle:

  `bin/console lint:xliff @AcmeDemoBundle`

### Arguments:

**filename:**

* Name: filename
* Is required: no
* Is array: no
* Description: A file or a directory or STDIN
* Default: `NULL`

### Options:

**format:**

* Name: `--format`
* Shortcut: <none>
* Accepts value: yes
* Is value required: yes
* Is multiple: no
* Description: The output format
* Default: `'txt'`

**help:**

* Name: `--help`
* Shortcut: `-h`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Display this help message
* Default: `false`

**quiet:**

* Name: `--quiet`
* Shortcut: `-q`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Do not output any message
* Default: `false`

**verbose:**

* Name: `--verbose`
* Shortcut: `-v|-vv|-vvv`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
* Default: `false`

**version:**

* Name: `--version`
* Shortcut: `-V`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Display this application version
* Default: `false`

**ansi:**

* Name: `--ansi`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Force ANSI output
* Default: `false`

**no-ansi:**

* Name: `--no-ansi`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Disable ANSI output
* Default: `false`

**no-interaction:**

* Name: `--no-interaction`
* Shortcut: `-n`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Do not ask any interactive question
* Default: `false`

**env:**

* Name: `--env`
* Shortcut: `-e`
* Accepts value: yes
* Is value required: yes
* Is multiple: no
* Description: The environment name
* Default: `'test'`

**no-debug:**

* Name: `--no-debug`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Switches off debug mode
* Default: `false`

Command "lint:yaml"
-------------------

* Description: Lints a file and outputs encountered errors
* Usage:

  * `bin/console app lint:yaml [options] [--] [<filename>]`

The `lint:yaml` command lints a YAML file and outputs to STDOUT
the first encountered syntax error.

You can validates YAML contents passed from STDIN:

  `cat filename | bin/console lint:yaml`

You can also validate the syntax of a file:

  `bin/console lint:yaml filename`

Or of a whole directory:

  `bin/console lint:yaml dirname`
  `bin/console lint:yaml dirname --format=json`

Or find all files in a bundle:

  `bin/console lint:yaml @AcmeDemoBundle`

### Arguments:

**filename:**

* Name: filename
* Is required: no
* Is array: no
* Description: A file or a directory or STDIN
* Default: `NULL`

### Options:

**format:**

* Name: `--format`
* Shortcut: <none>
* Accepts value: yes
* Is value required: yes
* Is multiple: no
* Description: The output format
* Default: `'txt'`

**help:**

* Name: `--help`
* Shortcut: `-h`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Display this help message
* Default: `false`

**quiet:**

* Name: `--quiet`
* Shortcut: `-q`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Do not output any message
* Default: `false`

**verbose:**

* Name: `--verbose`
* Shortcut: `-v|-vv|-vvv`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
* Default: `false`

**version:**

* Name: `--version`
* Shortcut: `-V`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Display this application version
* Default: `false`

**ansi:**

* Name: `--ansi`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Force ANSI output
* Default: `false`

**no-ansi:**

* Name: `--no-ansi`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Disable ANSI output
* Default: `false`

**no-interaction:**

* Name: `--no-interaction`
* Shortcut: `-n`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Do not ask any interactive question
* Default: `false`

**env:**

* Name: `--env`
* Shortcut: `-e`
* Accepts value: yes
* Is value required: yes
* Is multiple: no
* Description: The environment name
* Default: `'test'`

**no-debug:**

* Name: `--no-debug`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Switches off debug mode
* Default: `false`

Command "server:log"
--------------------

* Description: Starts a log server that displays logs in real time
* Usage:

  * `bin/console app server:log [options]`

`server:log` starts a log server to display in real time the log
messages generated by your application:

  `bin/console server:log`

To get the information as a machine readable format, use the
--filter option:

`bin/console server:log --filter=port`

### Options:

**host:**

* Name: `--host`
* Shortcut: <none>
* Accepts value: yes
* Is value required: yes
* Is multiple: no
* Description: The server host
* Default: `'0.0.0.0:9911'`

**format:**

* Name: `--format`
* Shortcut: <none>
* Accepts value: yes
* Is value required: yes
* Is multiple: no
* Description: The line format
* Default: `'%datetime% %start_tag%%level_name%%end_tag% <comment>[%channel%]</> %message%%context%%extra%'`

**date-format:**

* Name: `--date-format`
* Shortcut: <none>
* Accepts value: yes
* Is value required: yes
* Is multiple: no
* Description: The date format
* Default: `'H:i:s'`

**filter:**

* Name: `--filter`
* Shortcut: <none>
* Accepts value: yes
* Is value required: yes
* Is multiple: no
* Description: An expression to filter log. Example: "level > 200 or channel in ['app', 'doctrine']"
* Default: `NULL`

**help:**

* Name: `--help`
* Shortcut: `-h`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Display this help message
* Default: `false`

**quiet:**

* Name: `--quiet`
* Shortcut: `-q`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Do not output any message
* Default: `false`

**verbose:**

* Name: `--verbose`
* Shortcut: `-v|-vv|-vvv`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
* Default: `false`

**version:**

* Name: `--version`
* Shortcut: `-V`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Display this application version
* Default: `false`

**ansi:**

* Name: `--ansi`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Force ANSI output
* Default: `false`

**no-ansi:**

* Name: `--no-ansi`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Disable ANSI output
* Default: `false`

**no-interaction:**

* Name: `--no-interaction`
* Shortcut: `-n`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Do not ask any interactive question
* Default: `false`

**env:**

* Name: `--env`
* Shortcut: `-e`
* Accepts value: yes
* Is value required: yes
* Is multiple: no
* Description: The environment name
* Default: `'test'`

**no-debug:**

* Name: `--no-debug`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Switches off debug mode
* Default: `false`

Command "server:run"
--------------------

* Description: Runs a local web server
* Usage:

  * `bin/console app server:run [options] [--] [<addressport>]`

`server:run` runs a local web server: By default, the server
listens on 127.0.0.1 address and the port number is automatically selected
as the first free port starting from 8000:

  `bin/console server:run`

This command blocks the console. If you want to run other commands, stop it by
pressing Control+C or use the non-blocking server:start
command instead.

Change the default address and port by passing them as an argument:

  `bin/console server:run 127.0.0.1:8080`

Use the `--docroot` option to change the default docroot directory:

  `bin/console server:run --docroot=htdocs/`

Specify your own router script via the `--router` option:

  `bin/console server:run --router=app/config/router.php`

See also: http://www.php.net/manual/en/features.commandline.webserver.php

### Arguments:

**addressport:**

* Name: addressport
* Is required: no
* Is array: no
* Description: The address to listen to (can be address:port, address, or port)
* Default: `NULL`

### Options:

**docroot:**

* Name: `--docroot`
* Shortcut: `-d`
* Accepts value: yes
* Is value required: yes
* Is multiple: no
* Description: Document root, usually where your front controllers are stored
* Default: `NULL`

**router:**

* Name: `--router`
* Shortcut: `-r`
* Accepts value: yes
* Is value required: yes
* Is multiple: no
* Description: Path to custom router script
* Default: `NULL`

**help:**

* Name: `--help`
* Shortcut: `-h`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Display this help message
* Default: `false`

**quiet:**

* Name: `--quiet`
* Shortcut: `-q`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Do not output any message
* Default: `false`

**verbose:**

* Name: `--verbose`
* Shortcut: `-v|-vv|-vvv`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
* Default: `false`

**version:**

* Name: `--version`
* Shortcut: `-V`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Display this application version
* Default: `false`

**ansi:**

* Name: `--ansi`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Force ANSI output
* Default: `false`

**no-ansi:**

* Name: `--no-ansi`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Disable ANSI output
* Default: `false`

**no-interaction:**

* Name: `--no-interaction`
* Shortcut: `-n`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Do not ask any interactive question
* Default: `false`

**env:**

* Name: `--env`
* Shortcut: `-e`
* Accepts value: yes
* Is value required: yes
* Is multiple: no
* Description: The environment name
* Default: `'test'`

**no-debug:**

* Name: `--no-debug`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Switches off debug mode
* Default: `false`

Command "server:start"
----------------------

* Description: Starts a local web server in the background
* Usage:

  * `bin/console app server:start [options] [--] [<addressport>]`

`server:start` runs a local web server: By default, the server
listens on 127.0.0.1 address and the port number is automatically selected
as the first free port starting from 8000:

  `bin/console server:start`

The server is run in the background and you can keep executing other commands.
Execute server:stop to stop it.

Change the default address and port by passing them as an argument:

  `bin/console server:start 127.0.0.1:8080`

Use the `--docroot` option to change the default docroot directory:

  `bin/console server:start --docroot=htdocs/`

Specify your own router script via the `--router` option:

  `bin/console server:start --router=app/config/router.php`

See also: http://www.php.net/manual/en/features.commandline.webserver.php

### Arguments:

**addressport:**

* Name: addressport
* Is required: no
* Is array: no
* Description: The address to listen to (can be address:port, address, or port)
* Default: `NULL`

### Options:

**docroot:**

* Name: `--docroot`
* Shortcut: `-d`
* Accepts value: yes
* Is value required: yes
* Is multiple: no
* Description: Document root
* Default: `NULL`

**router:**

* Name: `--router`
* Shortcut: `-r`
* Accepts value: yes
* Is value required: yes
* Is multiple: no
* Description: Path to custom router script
* Default: `NULL`

**pidfile:**

* Name: `--pidfile`
* Shortcut: <none>
* Accepts value: yes
* Is value required: yes
* Is multiple: no
* Description: PID file
* Default: `NULL`

**help:**

* Name: `--help`
* Shortcut: `-h`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Display this help message
* Default: `false`

**quiet:**

* Name: `--quiet`
* Shortcut: `-q`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Do not output any message
* Default: `false`

**verbose:**

* Name: `--verbose`
* Shortcut: `-v|-vv|-vvv`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
* Default: `false`

**version:**

* Name: `--version`
* Shortcut: `-V`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Display this application version
* Default: `false`

**ansi:**

* Name: `--ansi`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Force ANSI output
* Default: `false`

**no-ansi:**

* Name: `--no-ansi`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Disable ANSI output
* Default: `false`

**no-interaction:**

* Name: `--no-interaction`
* Shortcut: `-n`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Do not ask any interactive question
* Default: `false`

**env:**

* Name: `--env`
* Shortcut: `-e`
* Accepts value: yes
* Is value required: yes
* Is multiple: no
* Description: The environment name
* Default: `'test'`

**no-debug:**

* Name: `--no-debug`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Switches off debug mode
* Default: `false`

Command "server:status"
-----------------------

* Description: Outputs the status of the local web server for the given address
* Usage:

  * `bin/console app server:status [options]`

`server:status` shows the details of the given local web
server, such as the address and port where it is listening to:

  `bin/console server:status`

To get the information as a machine readable format, use the
--filter option:

`bin/console server:status --filter=port`

Supported values are port, host, and address.

### Options:

**pidfile:**

* Name: `--pidfile`
* Shortcut: <none>
* Accepts value: yes
* Is value required: yes
* Is multiple: no
* Description: PID file
* Default: `NULL`

**filter:**

* Name: `--filter`
* Shortcut: <none>
* Accepts value: yes
* Is value required: yes
* Is multiple: no
* Description: The value to display (one of port, host, or address)
* Default: `NULL`

**help:**

* Name: `--help`
* Shortcut: `-h`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Display this help message
* Default: `false`

**quiet:**

* Name: `--quiet`
* Shortcut: `-q`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Do not output any message
* Default: `false`

**verbose:**

* Name: `--verbose`
* Shortcut: `-v|-vv|-vvv`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
* Default: `false`

**version:**

* Name: `--version`
* Shortcut: `-V`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Display this application version
* Default: `false`

**ansi:**

* Name: `--ansi`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Force ANSI output
* Default: `false`

**no-ansi:**

* Name: `--no-ansi`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Disable ANSI output
* Default: `false`

**no-interaction:**

* Name: `--no-interaction`
* Shortcut: `-n`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Do not ask any interactive question
* Default: `false`

**env:**

* Name: `--env`
* Shortcut: `-e`
* Accepts value: yes
* Is value required: yes
* Is multiple: no
* Description: The environment name
* Default: `'test'`

**no-debug:**

* Name: `--no-debug`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Switches off debug mode
* Default: `false`

Command "server:stop"
---------------------

* Description: Stops the local web server that was started with the server:start command
* Usage:

  * `bin/console app server:stop [options]`

`server:stop` stops the local web server:

  `bin/console server:stop`

### Options:

**pidfile:**

* Name: `--pidfile`
* Shortcut: <none>
* Accepts value: yes
* Is value required: yes
* Is multiple: no
* Description: PID file
* Default: `NULL`

**help:**

* Name: `--help`
* Shortcut: `-h`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Display this help message
* Default: `false`

**quiet:**

* Name: `--quiet`
* Shortcut: `-q`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Do not output any message
* Default: `false`

**verbose:**

* Name: `--verbose`
* Shortcut: `-v|-vv|-vvv`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
* Default: `false`

**version:**

* Name: `--version`
* Shortcut: `-V`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Display this application version
* Default: `false`

**ansi:**

* Name: `--ansi`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Force ANSI output
* Default: `false`

**no-ansi:**

* Name: `--no-ansi`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Disable ANSI output
* Default: `false`

**no-interaction:**

* Name: `--no-interaction`
* Shortcut: `-n`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Do not ask any interactive question
* Default: `false`

**env:**

* Name: `--env`
* Shortcut: `-e`
* Accepts value: yes
* Is value required: yes
* Is multiple: no
* Description: The environment name
* Default: `'test'`

**no-debug:**

* Name: `--no-debug`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Switches off debug mode
* Default: `false`

Command "translation:update"
----------------------------

* Description: Updates the translation file
* Usage:

  * `bin/console app translation:update [options] [--] <locale> [<bundle>]`

The `translation:update` command extracts translation strings from templates
of a given bundle or the app folder. It can display them or merge the new ones into the translation files.

When new translation strings are found it can automatically add a prefix to the translation
message.

Example running against a Bundle (AcmeBundle)
  `bin/console translation:update --dump-messages en AcmeBundle`
  `bin/console translation:update --force --prefix="new_" fr AcmeBundle`

Example running against app messages (app/Resources folder)
  `bin/console translation:update --dump-messages en`
  `bin/console translation:update --force --prefix="new_" fr`

### Arguments:

**locale:**

* Name: locale
* Is required: yes
* Is array: no
* Description: The locale
* Default: `NULL`

**bundle:**

* Name: bundle
* Is required: no
* Is array: no
* Description: The bundle name or directory where to load the messages, defaults to app/Resources folder
* Default: `NULL`

### Options:

**prefix:**

* Name: `--prefix`
* Shortcut: <none>
* Accepts value: yes
* Is value required: no
* Is multiple: no
* Description: Override the default prefix
* Default: `'__'`

**no-prefix:**

* Name: `--no-prefix`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: If set, no prefix is added to the translations
* Default: `false`

**output-format:**

* Name: `--output-format`
* Shortcut: <none>
* Accepts value: yes
* Is value required: no
* Is multiple: no
* Description: Override the default output format
* Default: `'yml'`

**dump-messages:**

* Name: `--dump-messages`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Should the messages be dumped in the console
* Default: `false`

**force:**

* Name: `--force`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Should the update be done
* Default: `false`

**no-backup:**

* Name: `--no-backup`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Should backup be disabled
* Default: `false`

**clean:**

* Name: `--clean`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Should clean not found messages
* Default: `false`

**domain:**

* Name: `--domain`
* Shortcut: <none>
* Accepts value: yes
* Is value required: no
* Is multiple: no
* Description: Specify the domain to update
* Default: `NULL`

**help:**

* Name: `--help`
* Shortcut: `-h`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Display this help message
* Default: `false`

**quiet:**

* Name: `--quiet`
* Shortcut: `-q`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Do not output any message
* Default: `false`

**verbose:**

* Name: `--verbose`
* Shortcut: `-v|-vv|-vvv`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
* Default: `false`

**version:**

* Name: `--version`
* Shortcut: `-V`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Display this application version
* Default: `false`

**ansi:**

* Name: `--ansi`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Force ANSI output
* Default: `false`

**no-ansi:**

* Name: `--no-ansi`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Disable ANSI output
* Default: `false`

**no-interaction:**

* Name: `--no-interaction`
* Shortcut: `-n`
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Do not ask any interactive question
* Default: `false`

**env:**

* Name: `--env`
* Shortcut: `-e`
* Accepts value: yes
* Is value required: yes
* Is multiple: no
* Description: The environment name
* Default: `'test'`

**no-debug:**

* Name: `--no-debug`
* Shortcut: <none>
* Accepts value: no
* Is value required: no
* Is multiple: no
* Description: Switches off debug mode
* Default: `false`
