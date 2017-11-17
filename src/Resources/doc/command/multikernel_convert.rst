Convert a Symfony3 project to a multikernel project
===================================================

Usage
-----

The ``multikernel:convert`` command changes the filesystem structure of a Symfony3
project to support multiple apps in the same project directory, all running with
the same front controller and **bin/console**.

The command does not require any arguments or options.

.. code-block:: bash

    $ bin/console multikernel:convert

The command will search for subdirectories containing a symfony kernel class before
any changes to the filesystem are made. No changes are made when no kernels are
found.

How the filesystem structure is changed
---------------------------------------

Running the ``multikernel:convert`` command will make the following changes to the
filesystem structure of the project:

* A boot kernel skeleton will be created into the ``apps/`` subdirectory of your project
* All found apps will be copied to ``apps/<DIR_NAME>``
* The kernel of every app are be modified to run with the BootKernel
* Configuration of the apps are modified to reflect the filesystem structure changes
* The front controller and bin/console are replaced

After all modifications have taken place, the original app directories and also all
files and directories under ``var/cache/``, ``var/logs`` and ``var/sessions`` are
removed. 

The command makes the following changes to each app kernel to make it work in a multikernel
environment:

* Use clauses are replaced to use classes from the MotanaMultikernelBundle
* The methods getCacheDir(), getLogDir() and registerContainerConfiguration are removed

The command changes the configuration of each app for a changed directory scheme under ``var/``:

* Caches for each kernel are stored in ``var/cache/<KERNEL_NAME>/<ENVIRONMENT_NAME>/``
* Logs for each kernel are stored in ``var/logs/<KERNEL_NAME>/<ENVIRONMENT_NAME>.log``
* Sessions for each kernel are stored in ``var/sessions/<KERNEL_NAME>/``

After running the ``multikernel:convert`` command, run the following commands on a shell:

.. code-block:: bash

    $ composer dump-autoload

