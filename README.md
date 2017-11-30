# MotanaMultikernelBundle

[![Build Status](https://travis-ci.org/KintradimCrux/motana-multikernel.svg?branch=master)](https://travis-ci.org/KintradimCrux/motana-multikernel)
[![Coverage Status](https://coveralls.io/repos/github/KintradimCrux/motana-multikernel/badge.svg?branch=master)](https://coveralls.io/github/KintradimCrux/motana-multikernel?branch=master)
[![Latest Stable Version](https://poser.pugx.org/motana/multikernel/v/stable)](https://packagist.org/packages/motana/multikernel)
[![Total Downloads](https://poser.pugx.org/motana/multikernel/downloads)](https://packagist.org/packages/motana/multikernel)
[![Latest Unstable Version](https://poser.pugx.org/motana/multikernel/v/unstable)](https://packagist.org/packages/motana/multikernel)
[![License](https://poser.pugx.org/motana/multikernel/license)](https://packagist.org/packages/motana/multikernel)
[![composer.lock](https://poser.pugx.org/motana/multikernel/composerlock)](https://packagist.org/packages/motana/multikernel)

This bundle extends a Symfony3 project by the ability to use multiple apps in the same project directory,
all running with the same front controller and ***bin/console***.

Routing within the apps will work as usual, which means already existing routes will continue to work.
Each app will be made available with its kernel name as URL prefix by the front controller. The ***bin/console***
replacement is able to run commands like ***cache:clear***, ***cache:pool:clear*** and ***assets:install*** for
all apps in one run, which will make the SensioDistributionBundle run those commands for all apps when running
***composer install*** or ***composer update***.

Since the BootKernel is a modified Symfony Kernel with almost all features disabled, the penalty of having a
prefixed extra kernel is rather small.

## Installation

### Step 1: Download the bundle

Open a command console, enter your project directory and execute the following
command to download the latest stable version of this bundle:

```shell
$ composer require motana/multikernel
```

in a shell.

### Step 2: Enable the bundle

Enable the bundle by adding it to the list of registered bundles in the
***app/AppKernel.php*** file of your project. Make sure it is registered
after the ``SensioGeneratorBundle``:

```php

    // app/AppKernel.php

    // ...
    class AppKernel extends Kernel
    {    
        // ...
        public function registerBundles()
        {
	        // ...
            $bundles[] = new Motana\Bundle\MultikernelBundle\MotanaMultikernelBundle();
            
            return $bundles;
        }
        // ...
    }
```

### Step 3: Use the commands of the bundle to convert your project

Open a command console, enter your project directory and execute the following
command to convert your project:

```shell
$ bin/console multikernel:convert
```

### How the filesystem structure is changed

Running the ``multikernel:convert`` command will make the following changes to the
filesystem structure of the project:

* A boot kernel skeleton will be created into the ``apps/`` subdirectory of your project
* All found apps will be copied to ``apps/<DIR_NAME>``
* The kernel of every app will be modified to run with the BootKernel
* Configuration of the apps are modified to reflect the filesystem structure changes
* The front controller and bin/console are replaced

After all modifications have taken place, the original app directories and also all
files and directories under ``var/cache/``, ``var/logs`` and ``var/sessions`` are
removed. 

The command makes the following changes to each app kernel to make it work in a multikernel
environment:

* Use clauses are replaced to use classes from the MotanaMultikernelBundle
* The methods getCacheDir(), getLogDir() and registerContainerConfiguration() are removed

The command changes the configuration of each app for a changed directory scheme under ``var/``:

* Caches for each kernel are stored in ``var/cache/<KERNEL_NAME>/<ENVIRONMENT_NAME>/``
* Logs for each kernel are stored in ``var/logs/<KERNEL_NAME>/<ENVIRONMENT_NAME>.log``
* Sessions for each kernel are stored in ``var/sessions/<KERNEL_NAME>/``

After running the ``multikernel:convert`` command, run the following commands on a shell:

```shell
    $ composer dump-autoload
```

## Configuration

The following settings can be used in apps/config/config.yml to configure the BootKernel:

```yml
# Default configuration for "MotanaMultikernelBundle"
motana_multikernel:

    # Default kernel the front controller should load when no kernel matches the URL
    default:              null # Example: "app" for the default AppKernel

    # Class cache configuration
    class_cache:

        # Classes to exclude from being cached in classes.php of app kernels
        exclude:              []

    # Console commands configuration
    commands:

        # Commands to add as multi-kernel command, bypassing the requirement of being available for all kernels
        add:                  []

        # Commands that will always be run on the boot kernel and will be hidden in the other kernels
        global:               []

        # Commands that will be hidden in all kernels
        hidden:               []

        # Commands that will be left as-is, overriding all of the above command settings
        ignore:               []

```

## Testing your project

To reflect the changes in the filesystem structure and routing, your ``phpunit.xml`` needs to be updated as follows:

Change the ``KERNEL_DIR`` setting to ``apps/``

```xml

        <server name="KERNEL_DIR" value="apps/" />

```

To select a kernel in your tests extending ``Symfony\Bundle\FrameworkBundle\Test\WebTestCase`` simply prefix the kernel
name to the URL used in the test.

## Credits

Portions of this bundle are based on work of [Fabien Potencier &lt;fabien@symfony.com&gt;](mailto:fabien@symfony.com) and
[Jean-François Simon &lt;contact@jfsimon.fr&gt;](mailto:contact@jfsimon.fr).

## License

This bundle is licensed under the MIT license - see the [LICENSE](LICENSE) file for details.

