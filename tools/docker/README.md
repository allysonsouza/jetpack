# Docker environment for Jetpack Development

Unified environment for developing Jetpack using Docker containers providing following goodies:

* An Ubuntu base operating system.
* Latest stable version of WordPress.
* All monorepo plugins will be available as plugins within the Docker WP instance.
* PHPUnit setup.
* Xdebug setup.
* WP-CLI installed.
* MailDev to catch all the emails leaving WordPress so that you can observe them from browser.
* phpMyAdmin to aid in viewing the database.
* Handy shorthand commands like `jetpack docker up` and `jetpack docker phpunit` to simplify the usage.

## To get started

_**All commands mentioned in this document should be run from the base Jetpack directory. Not from the `docker` directory!**_

### Prerequisites

* [Docker](https://hub.docker.com/search/?type=edition&offering=community)
* [NodeJS](https://nodejs.org)
* [Pnpm](https://pnpm.io/)
* Optionally [Ngrok](https://ngrok.com) client and account or some other service for creating a local HTTP tunnel. It’s fine to stay on the free pricing tier with Ngrok.

Install prerequisites; you will need to open up Docker to install its dependencies.

Start by cloning the Jetpack repository:

```sh
git clone git@github.com:Automattic/jetpack.git && cd jetpack
```

Optionally, copy settings file to modify it:
```sh
cp tools/docker/default.env tools/docker/.env
```

Anything you put in `.env` overrides values in `default.env`. You should modify all the password fields for security, for example.

Finally, spin up the containers:
```sh
jetpack docker up
```

Non-installed WordPress is running at [http://localhost](http://localhost) now.

You should establish a tunnel to your localhost with Ngrok or [other similar service](https://alternativeto.net/software/ngrok/) to be able to connect Jetpack. You cannot connect Jetpack when running WordPress via `http://localhost`. Read more from ["Using Ngrok with Jetpack"](#using-ngrok-with-jetpack) section below.

_You are now ready to login to your new WordPress install and connect Jetpack, congratulations!_

You should follow [Jetpack’s development documentation](../../docs/development-environment.md) for installing Jetpack’s dependencies and building files. Docker setup does not build these for you.

## Good to know

WordPress’ `WP_SITEURL` and `WP_HOME` constants are configured to be dynamic in `./tools/docker/wordpress/wp-config.php` so you shouldn’t need to change these even if you access the site via different domains.

## Custom mounts, environment Variables, `.env` Files, and Ports

You can control some of the behavior of Jetpack's Docker configuration with environment variables. Note, though, that there are two types of environments:
1. The host environment in which the `jetpack docker *` (`docker-compose`) commands run when creating/managing the containers.
2. The containers' environments.

### Host Environment

You can set the following variables on a per-command basis (`PORT_WORDPRESS=8000 jetpack docker up`) or, preferably, in a `./.env` file in Jetpack's root directory.

* `PORT_WORDPRESS`: (default=`80`) The port on your host machine connected to the WordPress container's HTTP server.
* `PORT_MAILDEV`: (default=`1080`) The port on your host machine connected to the MailDev container's MailDev HTTP server.
* `PORT_SMTP`: (default=`25`) The port on your host machine connected to the MailDev container's SMTP server.
* `PORT_SFTP`: (default=`1022`) The port on your host machine connected to the SFTP container's SFTP server.

### Container Environments

Configurable settings are documented in the [`./tools/docker/default.env` file](https://github.com/Automattic/jetpack/blob/master/docker/default.env).
Customizations should go into a `./tools/docker/.env` file you create, though, not in the `./docker/default.env` file.

### Mounting extra directories into the container

You can use the file `tools/docker/compose-extras.yml` to add mounts or alter the configuration provided by `docker/docker-compose.yml`.

You can use the file `tools/docker/compose-volumes.yml` to add additional mounts for WordPress plugins and themes. Refer to the section [Custom plugins & themes in the container](#custom-plugins--themes-in-the-container) for more details.

### Building on M1 Macs

If you're using the new [M1/Apple Silicon version of Docker](https://docs.docker.com/docker-for-mac/apple-m1/), you will need to add the following to your `tools/docker/compose-extras.yml` file to ensure a successful build:

```
services:
  db:
    platform: linux/x86_64
```

## Working with containers

### Quick install WordPress

You can just quickly install WordPress and activate Jetpack via command line. Ensure you have your domain modified in `.env` file, spin up the containers and then run:

```sh
jetpack docker install
```

This will give you a single site with user/pass `wordpress` (unless you changed these from `./tools/docker/.env` file). You will still have to connect Jetpack to WordPress.com manually.

To convert installed single site into a multisite, run:

```sh
jetpack docker multisite-convert
```

To remove WordPress installation and start over, run:

```sh
jetpack docker uninstall
```

### Start containers

```sh
jetpack docker up
```

Start three containers (WordPress, MySQL and MailDev) defined in `docker-composer.yml`. Wrapper for `docker-composer up`.

This command will rebuild the WordPress container if you made any changes to `docker-composer.yml`.

For running the containers in the background, use:

```sh
jetpack docker up -d
```

### Stop containers

```sh
jetpack docker stop
```

Stops all containers. Wrapper for `docker-compose stop`.

```sh
jetpack docker down
```

Will stop all of the containers created by this docker-compose configuration and remove them, too. It won’t remove the images. Just the containers that have just been stopped.

### Running unit tests

These commands require the WordPress container to be running.

```sh
jetpack docker phpunit
```

This will run unit tests for Jetpack. You can pass arguments to `phpunit` like so:

```sh
jetpack docker phpunit -- --filter=Protect
```

This command runs the tests as a multi site install
```sh
jetpack docker phpunit-multisite -- --filter=Protect
```

To run tests for specific packages, you can run the tests locally, from within the package's directory:
```sh
cd projects/packages/assets
composer phpunit
```

### Starting over

To remove all docker images, all MySQL data, and all docker-related files from your local machine run:

```sh
jetpack docker clean
```

**Note:** this command does not work in Windows.

### Using WP CLI

You can run [WP CLI](https://make.wordpress.org/cli/) commands inside WordPress container:

```sh
jetpack docker wp COMMAND
```

For example run [`cron event list`](https://developer.wordpress.org/cli/commands/cron/event/list/):

```sh
jetpack docker wp cron event list
```

[`shell`](https://developer.wordpress.org/cli/commands/shell/) is a handy WP-CLI command you can use like so:

```bash
jetpack docker wp shell
```

By default it will use rich REPL [`PsySH`](https://psysh.org/), to run the default REPL use `jetpack docker wp shell --basic`

Shell allows you to evaluate PHP code while having your installed WordPress loaded, so you could do things like:

```
wp> get_bloginfo( 'name' );
=> string(6) "WP-CLI"
```

Note that each `wp shell` session counts as a single request, causing unexpected situations with WP cache. You might want to run [`wp_cache_flush()`](https://developer.wordpress.org/reference/functions/wp_cache_flush/) between requests you expect to get cached by WordPress.

## MySQL database

Connecting to your MySQL database from outside the container, use:

- Host: `127.0.0.1`
- Port: `3306`
- User: `wordpress`
- Pass: `wordpress`
- Database: `wordpress`

You can also see your database files via local file system at `./tools/docker/data/mysql`

You can also access it via phpMyAdmin at [http://localhost:8181](http://localhost:8181).

Another way to accessing the database is MySQL client using the following command:
```sh
jetpack docker db
```
This command utilizes credentials from the config file (`~/.my.cnf`) to log you into MySQL without entering any connection information.

## SFTP access

You can access WordPress and Jetpack files via SFTP server container.

- Host: `localhost`
- Port: `1022`
- User: `wordpress`
- Pass: `wordpress`
- WordPress path: `/var/www/html`

You can tunnel to this container using [Ngrok](https://ngrok.com) or [other similar service](https://alternativeto.net/software/ngrok/). If you intend to do so, change the password in the `SFTP_USERS` variable in `./tools/docker/.env`!

Tunnelling makes testing [Jetpack Backup & Scan](https://jetpack.com/support/backup/) possible. Read more from ["Using Ngrok with Jetpack"](#using-ngrok-with-jetpack) section below.

### SFTP keys

To allow SFTP login using a key, place the public key files (e.g. `id_rsa.pub`) in `./tools/docker/data/ssh.keys`.

## Must Use Plugins directory

You can add your own PHP code to `./tools/docker/mu-plugins` directory and they will be loaded by WordPress, in alphabetical order, before normal plugins, meaning API hooks added in an mu-plugin apply to all other plugins even if they run hooked-functions in the global namespace. Read more about [must use plugins](https://codex.wordpress.org/Must_Use_Plugins).

You can add your custom Jetpack constants (such as `JETPACK__SANDBOX_DOMAIN`) to a file under this folder. Automattic engineers can use this to sandbox their environment:

```
define( 'JETPACK__SANDBOX_DOMAIN', '{your sandbox}.wordpress.com' );
```

## Jurassic Tube Tunneling Service
If you are an Automattician, you can use Jurassic Tube tunneling service with functionality similar to Ngrok.

As it is developed internally, you can review the source code and participate in adding new features.

* Start the tunnel: `jetpack docker jt-up your-username your-subdomain`
* Break the connection: `jetpack docker jt-down`

You can also set default values:

```shell script
jetpack docker jt-config username your-username
jetpack docker jt-config subdomain your-subdomain
```
That will let you omit those parameters while initiating the connection:
```shell script
jetpack docker jt-up
```

More information: PCYsg-snO-p2.
## Using Ngrok with Jetpack

Note: While Ngrok is technically supported for everyone, Jurassic Tube should be considered as preferred tunneling solution for Automatticians.

To be able to connect Jetpack you will need a domain - you can use [Ngrok.com](https://ngrok.com/) to assign one.

If you use one-off domains, you'll have to re-install WordPress and re-connect Jetpack each time you close Ngrok (thus losing your randomly assigned domain). That's perfectly fine for quick testing or lightweight development. You can use [other similar services](https://alternativeto.net/software/ngrok/) as well.

If you're developing Jetpack often you'll want to reserve a domain you can keep using.

If you are an Automattician, we no longer have SSO access to Ngrok. To get the ability to re-use domains, reserve your custom domains, and reserve TCP ports, you'll need to sign up for the plan you need and expense the yearly fee.

[Go to this page to reserve a permanent domain](https://dashboard.ngrok.com/reserved).

Once you’ve done that, follow [these steps](https://ngrok.com/download) to download and set up ngrok. However, instead of step four, edit your [config file](https://ngrok.com/docs#default-config-location) as explained below:

```
authtoken: YOUR_AUTH_TOKEN # This should already be here
region: eu # only needed for subdomains in Europe (eu), Asia/Pacific (ap) or Australia (au)
tunnels:
  jetpack:
    subdomain: YOUR_RESERVED_SUBDOMAIN # without the .ngrok.io
    addr: 80
    proto: http
```

ngrok support is integrated into a jetpack cli, so to start a docker container with mapped tunnel, simply run:
```bash
jetpack docker up --ngrok
```

### Ngrok SFTP Tunnel with Jetpack
A sample config for adding an sftp tunnel to your Ngrok setup would look like this:

```
authtoken: YOUR_AUTH_TOKEN
tunnels:
  jetpack:
    subdomain: YOUR_PERMANENT_SUBDOMAIN
    addr: 80
    proto: http
  jetpack-sftp:
    addr: 1022
    proto: tcp
    remote_addr: 0.tcp.ngrok.io:YOUR_RESERVED_PORT
```

See more configuration options from [Ngrok documentation](https://ngrok.com/docs#tunnel-definitions).

You can now start both tunnels:
```bash
jetpack docker up --ngrok sftp
```

You can inspect traffic between your WordPress/Jetpack container and WordPress.com using [the inspector](https://ngrok.com/docs#inspect).

### Configuring Jetpack Backup & Scan with Ngrok tunnel

You should now be able to configure [Jetpack Backup & Scan](https://jetpack.com/support/backup/) credentials point to your Docker container:

- Credential Type: `SSH/SFTP`
- Server Address: `0.tcp.ngrok.io`
- Port Number: `YOUR_RESERVED_PORT`
- Server username: `wordpress`
- Server password: `wordpress`
- WordPress installation path: `/var/www/html`

## Custom plugins & themes in the container

Jetpack Docker environment can be wonderful for developing your own plugins and themes, too.

Since everything under `mu-plugins` and `wordpress/wp-content` is git-ignored, you'll want to keep those folders outside Jetpack repository folder and link them as volumes to your Docker instance.

1. First ensure your containers are stopped (`jetpack docker stop`).
2. Edit `tools/docker/compose-volumes.yml`. This file will be generated when running `jetpack docker up`, containing content from a sample file `tools/docker/compose-volumes.yml.sample`. But you can also copy it by hand. Changes to this file won't be tracked by git.
3. Start containers and include your custom volumes by running:
   ```bash
   jetpack docker up
   ```

Note that any folder within the `projects/plugins` directory will be automatically linked.
If you're starting a new monorepo plugin, you may need to `jetpack docker stop` and `jetpack docker up` to re-run the initial linking step so it can be added.

## Debugging

### Accessing logs

Logs are stored in your file system under `./tools/docker/logs` directory.

#### PHP error log

To `tail -f` the PHP error log, run:

```sh
jetpack docker tail
```

#### MySQL Slow Query Log

The MySQL Server is configured to log any queries that take longer than 0.5 second to execute.

Path to the slow query log: `tools/docker/logs/mysql/slow.log`.

We recommend to regularly review the log to make sure performance issues don't go unnoticed.

### Debugging emails

Emails don’t leave your WordPress and are caught by [MailDev](http://danfarrelly.nyc/MailDev/) SMTP server container instead.

To debug emails via web-interface, open [http://localhost:1080](http://localhost:1080)


### Debugging different WordPress versions

You can use the [WP CLI](https://make.wordpress.org/cli/) to update the version of WordPress running inside the Docker container. Example command:

```
jetpack docker wp core update --version=5.3.4 --force
```

This is useful if you want to check your code is compatible with the minimum version of WP Jetpack supports, which can be found in the [readme.txt](../readme.txt). We always support the latest patched version of the branch we specify as "Requires at least" in the readme file. You can match it with the exact version on the [WordPress Releases page](https://wordpress.org/download/releases/).

### Debugging PHP with Xdebug

The WordPress image is leveraged with Xdebug present as a PHP Extension.

You’ll likely need to install a browser extension like the following:

* [The easiest Xdebug](https://addons.mozilla.org/en-US/firefox/addon/the-easiest-xdebug/) for Mozilla Firefox
* [Xdebug Helper](https://chrome.google.com/webstore/detail/xdebug-helper/eadndfjplgieldjbigjakmdgkmoaaaoc) for Google Chrome

#### Remote debugging with PhpStorm editor

Below are instructions for starting a debug session in PhpStorm that will listen to activity on your Jetpack docker.

1. Configure your browser extension to use 'PHPSTORM' for its session ID.

1. Open your Jetpack project in PhpStorm and chose 'Run -> Edit Configurations' from the main menu.

1. Click the '+' icon, and chose 'PHP Remote Debug' to create a new debug configuration.

1. Name your debug configuration whatever you like.

1. Check the 'Filter debug connection by IDE key', and enter 'PHPSTORM' for 'IDE Key ( Session ID )'.

1. Click the '...' on the 'Server' line to configure your remote server.

1. In the server configuration window, click the '+' icon to create a new server configuration. Name it whatever you like.

1. In the server configuration window, set your host to the URL you use to run Jetpack locally. ( Eg, localhost, or 0.0.0.0, or example.ngrok.io )

1. In the server configuration window, check the 'Use path mappings' check box.

1. In the server configuration window, map the root folder to '/usr/local/src/jetpack-monorepo' and 'tools/docker/wordpress' to '/var/www/html'.

1. In the server configuration window, click 'Apply' then 'Ok'.

1. Back in the main configuration window, click 'Apply' then 'Ok'.

1. You can now start a debug session by clicking 'Run -> Debug' in the main menu

#### Remote Debugging with VSCode

You'll need:

- [PHP Debug](https://marketplace.visualstudio.com/items?itemName=felixfbecker.php-debug) plugin installed in VSCode
- If you use Google Chrome, install the [Xdebug Helper](https://chrome.google.com/webstore/detail/xdebug-helper/eadndfjplgieldjbigjakmdgkmoaaaoc?hl=en) extension.
- If you use Firefox, install [Xdebug Helper](https://addons.mozilla.org/en-GB/firefox/addon/xdebug-helper-for-firefox/) add-on.

##### Set up the Debugging Task

In the debug panel in VSCode, select Add Configuration. Since you have PHP Debug installed, you'll have the option to select "PHP" from the list. This will create a `.vscode` folder in the project root with a `launch.json` file in it.

You will need to supply a pathMappings value to the `launch.json` configuration. This value connects the debugger to the volume in Docker with the Jetpack code. Your `launch.json` file should have this configuration when you're done.

```json
{
    "version": "0.2.0",
    "configurations": [
        {
            "name": "Listen for Xdebug",
            "type": "php",
            "request": "launch",
            "port": 9003,
            "pathMappings": {
                "/usr/local/src/jetpack-monorepo": "${workspaceRoot}",
                "/var/www/html": "${workspaceRoot}/tools/docker/wordpress",
            }
        },
        {
            "name": "Launch currently open script",
            "type": "php",
            "request": "launch",
            "program": "${file}",
            "cwd": "${fileDirname}",
            "port": 9003
        }
    ]
}
```

In your browser's Xdebug Helper preferences, look for the IDE Key setting:

1. Select 'Other'
2. Add `VSCODE` as the key.
3. Save.

##### Run the debugger

- Set a break point in a PHP file, for example in the `init()` function of `class.jetpack.php`.
- Select 'Debug' on the browser extension.
- Click 'play' in VSCode's debug panel
- Refresh the page at localhost

For more context on remote debugging PHP with VSCode, see [this article](https://medium.com/@jasonterando/debugging-with-visual-studio-code-xdebug-and-docker-on-windows-b63a10b0dec).
