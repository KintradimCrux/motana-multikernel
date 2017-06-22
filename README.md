# Motana Multi-Kernel Bundle

[![Build Status](https://travis-ci.org/KintradimCrux/motana-multikernel.svg?branch=master)](https://travis-ci.org/KintradimCrux/motana-multikernel)
[![Coverage Status](https://coveralls.io/repos/github/KintradimCrux/motana-multikernel/badge.svg?branch=master)](https://coveralls.io/github/KintradimCrux/motana-multikernel?branch=master)
[![Latest Stable Version](https://poser.pugx.org/motana/multikernel/v/stable)](https://packagist.org/packages/motana/multikernel)
[![Total Downloads](https://poser.pugx.org/motana/multikernel/downloads)](https://packagist.org/packages/motana/multikernel)
[![Latest Unstable Version](https://poser.pugx.org/motana/multikernel/v/unstable)](https://packagist.org/packages/motana/multikernel)
[![License](https://poser.pugx.org/motana/multikernel/license)](https://packagist.org/packages/motana/multikernel)
[![composer.lock](https://poser.pugx.org/motana/multikernel/composerlock)](https://packagist.org/packages/motana/multikernel)

MotanaMultikernelBundle extends a Symfony3 project by the ability to use multiple apps in the same project directory.
This is done by adding a BootKernel that makes the front controller delegate requests to the other kernels. Routing within
the apps will work as usual, which means every app has its own profiler (if using the WebProfilerBundle).
The bin/console has been extended to run commands on multiple kernels (i.e. cache:clear, assets:install).

Since the BootKernel is a modified Symfony Kernel with almost all features disabled, the penalty of having an extra kernel is rather small.

## Installation

### Get the bundle

Let composer download and install the bundle by running

```shell
composer require motana/multikernel ~1.0
```

in a shell.

### Enable the bundle

```php
// in app/AppKernel.php
public function registerBundles() {
	$bundles = array(
		// ...
		new Motana\Bundle\MultikernelBundle\MotanaMultikernelBundle(),
	);
	// ...
}
```

### Convert your project

After enabling the bundle, run the following command on the shell to convert your project directory:

```shell
bin/console multikernel:convert
```

This will make the following changes to your project:
* Create the apps/ directory from a skeleton
* Create apps/BootKernel.php
* Move the directory app/ to apps/app/
* Change apps/app/AppKernel.php to run with the BootKernel
* Update settings in apps/app/config/*.yml
* Replace bin/console
* Replace web/app.php and web/app_dev.php
* Remove the app/ directory and also var/cache/, var/logs/ and var/sessions/. 


The following changes will be made to your AppKernel to make it work with the changed directory structure:
* Use clauses are replaced to use classes from the MotanaMultikernelBundle
* The methods getCacheDir(), getLogDir() and registerContainerConfiguration() are removed


The following changes are made to the directory structure in var/:
* Caches for each kernel are stored in var/cache/**kernel**/**environment**/
* Logs for each kernel are stored in var/logs/**kernel**/**environment**.log
* Sessions for each kernel are stored in var/sessions/**kernel**/


When finished, run the following commands on the shell to reset your project directory to a working state:

```shell
composer dump-autoload
composer symfony-scripts
```

The SensioDistributionBundle will run the cache:clear and assets:install commands on both the BootKernel and your AppKernel.


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

```

## Credits

Portions of this bundle are based on work of [Fabien Potencier &lt;fabien@symfony.com&gt;](mailto:fabien@symfony.com) and
[Jean-François Simon &lt;contact@jfsimon.fr&gt;](mailto:contact@jfsimon.fr).

## License

This bundle is licensed under the MIT license - see the [LICENSE](LICENSE) file for details.

