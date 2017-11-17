MotanaMultikernelBundle
=======================

This bundle extends a Symfony3 project by the ability to use multiple apps in the same project directory,
all running with the same front controller and **bin/console**.

Routing within the apps will work as usual, which means already existing routes will continue to work.
Each app will be made available with its kernel name as URL prefix by the front controller. The **bin/console**
replacement is able to run commands like **cache:clear**, **cache:pool:clear** and **assets:install** for
all apps in one run, which will make the SensioDistributionBundle run those commands for all apps when running
**composer install** or **composer update**.

Since the BootKernel is a modified Symfony Kernel with almost all features disabled, the penalty of having a
prefixed extra kernel is rather small.

Installation
------------

Step 1: Download the Bundle
~~~~~~~~~~~~~~~~~~~~~~~~~~~

Open a command console, enter your project directory and execute the following
command to download the latest stable version of this bundle:

.. code-block:: bash

    $ composer require motana/multikernel

Step 2: Enable the bundle
~~~~~~~~~~~~~~~~~~~~~~~~~

Enable the bundle by adding it to the list of registered bundles in the
``app/AppKernel.php`` file of your project. Make sure it is registered
after the ``SensioGeneratorBundle``:

.. code-block:: php
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

Step 3: Use the commands of the bundle to convert your project
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Open a command console, enter your project directory and execute the following
command to convert your project:

.. code-block: bash

    $ bin/console multikernel:convert

This will make the following changes to the filesystem structure of your project:

* A boot kernel skeleton will be created into ./apps/
* All apps found in the project directory will be moved to ./apps/<KERNEL_NAME>/
* The kernels of all apps will be modified to run with the BootKernel
* Configuration of the apps will be updated to reflect the filesystem structure changes
* The front controller and bin/console will be replaced
* The original app directories will be removed
* Files and directories in ./var/cache/, ./var/logs/ and ./var/sessions/ will be removed

The following changes will be made to each app kernel to make it work with the ``BootKernel``:

* Use clauses are replaced to use classes from the MotanaMultikernelBundle
* The methods getCacheDir(), getLogDir() and registerContainerConfiguration are removed

The following changes are made to the directory structure in ``./var/``:

* Caches for each kernel are stored in ``./var/cache/<KERNEL_NAME>/<ENVIRONMENT_NAME>/``
* Logs for each kernel are stored in ``./var/logs/<KERNEL_NAME>/<ENVIRONMENT_NAME>.log``
* Sessions for each kernel are stored in ``./var/sessions/<KERNEL_NAME>/``

After running the ``multikernel:convert`` command, run the following commands:

.. code-block: bash

    $ composer dump-autoload
    $ composer symfony-scripts

The SensioDistributionBundle will run the ``cache:clear`` and ``assets:install`` commands for all
available apps.

Configuration
-------------

The following settings can be used in ``./apps/config/config.yml`` to configure the ``BootKernel``:

.. code-block: yaml

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

Testing your project
--------------------

To reflect the changes in the filesystem structure and routing, your ``phpunit.xml`` needs to be updated as follows:

Change the ``KERNEL_DIR`` setting to ``apps/``

.. code-block: xml

        <server name="KERNEL_DIR" value="apps/" />

To select a kernel in your tests extending ``Symfony\Bundle\FrameworkBundle\Test\WebTestCase`` simply prefix the kernel
name to any URL used in the test.

List of available commands:
---------------------------

The bundle replaces the ``help`` and ``list`` commands of Symfony to enhance display of
commmand usage and command lists.

A modified version of the ``generate:bundle`` from the ``SensioGeneratorBundle`` is
provided for correct placement of generated bundle files, until the issue is resolved
(see https://github.com/sensiolabs/SensioGeneratorBundle/issues/568).

A wrapper for the ``router:match`` command is provided, which an app first and then
the route within the app.

All of the commands listed below can run in interactive or non-interactive mode.
The interactive mode asks you some questions to configure the command parameters.

Read the following articles to learn how to use the new commands:

.. toctree::
   :maxdepth: 1
    
   commands/generate_app
   commands/generate_bundle
   commands/multikernel_convert
   commands/router_match

Overriding skeleton templates
-----------------------------

All generators use a template skeleton to generate files. By default, the
commands use templates provided by the bundle under its ``Resources/fixtures`` 
and ``Resources/skeleton`` directories, aswell as the ``Resources/skeleton``
directory of the SensioGeneratorBundle.

You can define custom skeleton templates by creating the same directory and
file structure in the following locations (displayed from highest to lowest
priority):

* ``<BUNDLE_PATH>/Resources/MotanaMultikernelBundle/skeleton/``
* ``<APP_PATH>/Resources/MotanaMultikernelBundle/skeleton/``
* ``<BUNDLE_PATH>/Resources/SensioGeneratorBundle/skeleton/``
* ``<APP_PATH>/Resources/SensioGeneratorBundle/skeleton/``

The ``<BUNDLE_PATH>`` value refers to the base path of the bundle where you are
scaffolding a controller, a form or a CRUD backend.

The ``<APP_PATH>`` value refers to the base path of an app where the ``AppKernel.php`` is.

For instance, if you want to override the ``config.yml`` template for the ``generate:app`` command,
create the ``app/config.yml.twig`` file under ``apps/app/Resources/MotanaMultikernelBundle/skeleton``.

When overriding a template, have a look at the default templates to learn more about the available
templates, their paths and the variables they access.

Instead of copy/pasting the original template to create your own, you can also extend it and only
override the relevant parts:

.. code-block: jinja

  {# app/Resources/MotanaMultikernelBundle/skeleton/app/config.yml.twig #}
  
  {% extends "skeleton/app/config.yml.twig" %}
  
  {% block swiftmailer %}
      {{ parent() }}
      
      # This is going to be inserted after the parent template
      # content
  {% endblock swiftmailer %}

Complex templates in the default skeleton are split into twig blocks to allow
easy inheritance and avoid copy/pasting a large junk of code.

Credits
-------

Portions of this bundle are based on work of `Fabien Potencier <mailto:fabien@symfony.com>` and
`Jean-Francois Simon <mailto:contact@jfsimon.fr>`. 

License
-------

This bundle is licensed under the MIT license, see the `LICENSE <../../../LICENSE>`_ file shipped with this source.
