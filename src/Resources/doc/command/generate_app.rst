Generate a new App skeleton
===========================

Usage
-----

The ``generate:app`` command generates a new app structure and a bundle
for the application.

By default the command is run in the interactive mode and asks questions to
determine the bundle name, location, configuration format and default
structure:

.. code-block:: bash

    $ bin/console generate:app

To deactivate the interactive mode, use the `--no-interaction` option but don't
forget to pass all needed options:

.. code-block:: bash

    $ bin/console generate:app --kernel <KERNEL_NAME>

The name of the bundle generated for the app is generated from the camelized
kernel name as ``<KERNEL_NAME>Bundle``.

Available options
-----------------

``--kernel``
    The kernel name to generate a skeleton for. This option also determines the name
    of the generated kernel, cache and app bundle classes and namespaces. All class
    names are generated from the camelized value of this option as ``<KERNEL_NAME>Kernel``,
    ``<KERNEL_NAME>Cache`` and ``<KERNEL_NAME>Bundle``.
    
    .. code-block:: bash
    
        $ bin/console generate:app --kernel=app

``--dir``
    The directory in which to store the bundle. By convention, the command
    detects and uses the application's ``src/`` folder:

    .. code-block:: bash

        $ php bin/console generate:bundle --dir=/var/www/myproject/src

``--format``
    **allowed values**: ``annotation|php|yml|xml`` **default**: ``annotation``

    Determine the format to use for the generated configuration files (like
    routing). By default, the command uses the ``annotation`` format (choosing
    the ``annotation`` format expects the `SensioFrameworkExtraBundle`_ to
    be installed):

    .. code-block:: bash

        $ php bin/console generate:bundle --format=annotation

``--micro``
    Use this option to generate an app kernel using the ``MicroKernelTrait``.
    Generating a bundle is skipped when this option is specified.

``--no-bundle``
    Use this option to skip generating a bundle for the app.

.. _`SensioFrameworkExtraBundle`: http://symfony.com/doc/master/bundles/SensioFrameworkExtraBundle/index.html
