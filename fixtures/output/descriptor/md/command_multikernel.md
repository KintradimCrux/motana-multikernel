Command "help"
--------------

* Description: Displays help for a command
* Usage:

  * `bin/console help [options] [--] [<command_name>]`
  * `bin/console <kernel> help [--format FORMAT] [--raw] [--] [<command_name>]`

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
