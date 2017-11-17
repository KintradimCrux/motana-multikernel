Match a request on an application and route
===========================================

Usage
-----

The ``router:match`` command matches a request on an application and then
on a route within the application.

It has only argument, which is required and specifies the ``path_info`` of
the request.

.. code-block:: bash

    $ bin/console router:match /foo

  or

.. code-block:: bash

    $ bin/console router:match /foo --method POST --scheme https --host symfony.com --verbose


Available options
-----------------

``--method=METHOD``
    Provide this option to set the HTTP method (the default is ``GET``).
    Other valid methods are ``HEAD``, ``POST``, ``PUT``, ``DELETE``,
    ``CONNECT``, ``OPTIONS``, ``TRACE`` and ``PATCH``.

    .. code-block:: bash

        $ php bin/console router:match /foo --method=POST

``--scheme=SCHEME``
    Provide this option to set the URI scheme (usually ``http`` or ``https``).

    .. code-block:: bash

        $ php bin/console router:match /foo --scheme=https

``--host=HOST``
    Provide this option to set the hostname used in the request.

    .. code-block:: bash

        $ php bin/console router:match /foo --host=example.com
