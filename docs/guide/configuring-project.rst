Configuring a project
=====================

Environment configuration
^^^^^^^^^^^^^^^^^^^^^^^^^

By default, Docker Compose reads two files, a ``docker-compose.yml`` and an optional ``docker-compose.override.yml`` file.
By convention, the ``docker-compose.yml`` contains your base configuration and it's provided by default.
The override file, as its name implies, can contain configuration overrides for existing services or entirely new
services.
If a service is defined in both files, Docker Compose merges the configurations.

Find more information on Docker Compose extension mechanism on
`the official Docker Compose documentation <https://docs.docker.com/compose/extends/>`_.

The following configuration parameters are provided as environment variables in the ``./.env`` file:

+-----------------------------------+----------------------------------------------------------+
|Name                               |Description                                               |
+===================================+==========================================================+
|DRUPAL_DATABASE_NAME               |Database name                                             |
+-----------------------------------+----------------------------------------------------------+
|DRUPAL_DATABASE_USERNAME           |Database username                                         |
+-----------------------------------+----------------------------------------------------------+
|DRUPAL_DATABASE_PASSWORD           |Database password                                         |
+-----------------------------------+----------------------------------------------------------+
|DRUPAL_DATABASE_PREFIX             |Database prefix                                           |
+-----------------------------------+----------------------------------------------------------+
|DRUPAL_DATABASE_HOST               |Database host                                             |
+-----------------------------------+----------------------------------------------------------+
|DRUPAL_DATABASE_PORT               |Database port                                             |
+-----------------------------------+----------------------------------------------------------+
|DRUPAL_SPARQL_HOSTNAME             |SPARQL hostname                                           |
+-----------------------------------+----------------------------------------------------------+
|DRUPAL_SPARQL_PORT                 |SPARQL port                                               |
+-----------------------------------+----------------------------------------------------------+
|DRUPAL_CAS_HOSTNAME                |EULogin hostname, use ``ecas.ec.europa.eu`` for production|
+-----------------------------------+----------------------------------------------------------+
|DRUPAL_CAS_PORT                    |EULogin port, use ``443`` for production                  |
+-----------------------------------+----------------------------------------------------------+
|DRUPAL_ACCOUNT_USERNAME            |Drupal admin account, defaults to ``admin`` if empty      |
+-----------------------------------+----------------------------------------------------------+
|DRUPAL_ACCOUNT_PASSWORD            |Drupal admin password, defaults to random string if empty |
+-----------------------------------+----------------------------------------------------------+
|DRUPAL_BASE_URL                    |Drupal site base URL, used to setup Behat tests           |
+-----------------------------------+----------------------------------------------------------+
|DRUPAL_WEBTOOLS_ANALYTICS_SITE_ID  | Drupal site unique identifier                            |
+-----------------------------------+----------------------------------------------------------+
|DRUPAL_WEBTOOLS_ANALYTICS_SITE_PATH|The domain + root path without protocol.                  |
+-----------------------------------+----------------------------------------------------------+

Environment variables will be loaded by Docker Compose when running ``docker-compose up -d``.

Subsite configuration
^^^^^^^^^^^^^^^^^^^^^

By default, subsite configuration go into file ``runner.yml.dist``, see bellow an example.

.. code-block::

   drupal:
     root: "web"
     base_url: ${env.DRUPAL_BASE_URL}
     site:
       profile: "standard"
       name: "Drupal website configuration goes here!"
       generate_db_url: false
     account:
       name: ${env.DRUPAL_ACCOUNT_USERNAME}
       password: ${env.DRUPAL_ACCOUNT_PASSWORD}

   toolkit:
     project_id: 'PROJECTID'

   selenium:
     host: "http://selenium"
     port: "4444"
     browser: "chrome"

Splitting subsite configuration into multiple files
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Each subsite configuration can be splited into independent Yaml files instead of defining everything inside ``runner.yml.dist`` file.

By default, the configuration files directory is defined to be ``PROJECT_ROOT/config/runner``

.. code-block::

   runner:
     config_dir: './config/runner'

To change the directory configuration, copy the above configuration and paste it inside ``runner.yml.dist`` and change ``config_dir`` value.

Every configuration block inside ``runner.yml.dist`` (drupal, toolkit, selenium, etc...) can be moved to ``config_dir`` directory.

As example, taking the subsite configurations:

.. code-block::

   runner.yml.dist file with custom directory defined
   ===================================================

     runner:
       config_dir: './my-custom-dir'


   Config directory
   =================

   ~/PROJECT_ROOT/my-custom-dir $
     drupal.yml
     toolkit.yml
     selenium.yml


   drupal.yml file
   ===============

     drupal:
       root: "web"
       base_url: ${env.DRUPAL_BASE_URL}
       site:
         profile: "standard"
         name: "Drupal website configuration goes here!"
         generate_db_url: false
       account:
         name: ${env.DRUPAL_ACCOUNT_USERNAME}
         password: ${env.DRUPAL_ACCOUNT_PASSWORD}

   toolkit.yml file
   ===============

     toolkit:
       project_id: 'PROJECTID'


   selenium.yml file
   ===============

     selenium:
       host: "http://selenium"
       port: "4444"
       browser: "chrome"
