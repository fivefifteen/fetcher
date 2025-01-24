<div align="center">

  ![Fetcher](./assets/fetcher.png)

  # Fetcher

  A package manager written in PHP that supports installing dependencies from [GitHub](https://github.org), [npm](https://npmjs.com), custom URLs, and local file paths.

  [![packagist package version](https://img.shields.io/packagist/v/fivefifteen/fetcher.svg?style=flat-square)](https://packagist.org/packages/fivefifteen/fetcher)
  [![packagist package downloads](https://img.shields.io/packagist/dt/fivefifteen/fetcher.svg?style=flat-square)](https://packagist.org/packages/fivefifteen/fetcher)
  [![GitHub code size in bytes](https://img.shields.io/github/languages/code-size/fivefifteen/fetcher?style=flat-square)](https://github.com/fivefifteen/fetcher)
  [![license](https://img.shields.io/github/license/fivefifteen/fetcher.svg?style=flat-square)](https://github.com/fivefifteen/fetcher/blob/main/license.md)

  <a href="https://asciinema.org/a/693164" target="_blank"><img src="https://asciinema.org/a/693164.svg" width="75%" /></a>

  <a href="https://fivefifteen.com" target="_blank"><img src="./assets/fivefifteen.png" /><br /><b>A Five Fifteen Project</b></a>

</div>


## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)
    - [Commands](#commands)
        - [Install](#install)
        - [Uninstall](#uninstall)
        - [Info](#info)
        - [Versions](#versions)
    - [Global Options](#global-options)
    - [Package & Version Parsing](#package--version-parsing)
        - [Package Aliases](#package-aliases)
- [Configuration](#configuration)
    - [Configuration via Composer](#configuration-via-composer)
- [Related Projects](#related-projects)
- [License Information](#license-information)


## Requirements

- PHP 8.1 or above
- Composer


## Installation

### To a package (local)

```
composer require-dev fivefifteen/fetcher
./vendor/bin/fetcher --version
```

### To your system (global)

```sh
composer global require fivefifteen/fetcher
fetcher --version
```


## Usage

### Commands

#### Install

```sh
fetcher install [packages...]
fetcher i [packages...]
```

Installs the defined packages. If no packages are defined, fetcher will attempt to locate a `fetch.json` file and install packages located in it's `dependencies` section under the `fetcher` key.

*Note: Fetcher only installs defined dependencies. Child dependencies such as ones found in `composer.json` or `package.json` are ignored.*


##### Options

 - `[-c|--config]` - Path to the config file [default: `fetch.json`]
 - `[-d|--install-directory]` - Path where packages should be installed to [default: `fetched`]
 - `[-e|--extensions]` - A comma seperated list of extensions to extract from the packages
 - `[-f|--fresh-start]` - Deletes the entire fetched directory before running installation
 - `[-i|--ignore-errors]` - Ignore any errors that occur and continue installing the rest of the packages
 - `[-p|--providers]` - The providers to search for packages from and their order [default: `github,npm`]
 - `[-q|--quiet]` - Run but don't output anything in the terminal
 - `[-s|--save]` - Save the newly installed packages to the config file's `dependencies` section (config file will be created if it doesn't exist)
 - `[-w|--working-directory]` - Sets the working directory that all paths will be relative to [default: `.`]
 - `[-x|--no-extract]` - Don't extract packages after they are downloaded


##### Examples

```sh
# Install packages defined in a custom config file
fetcher install --config content/themes/my-theme/compile.json

# Install the latest version of the `include-media` package from npm
fetcher install --providers npm include-media
fetcher install npm:include-media # same as above
fetcher install npm:include-media@latest # same as above

# Install a specific version of the `include-media` package from npm
fetcher install --providers npm include-media@1.4.10
fetcher install npm:include-media@1.4.10 # same as above

# Install the latest version of the `include-media` package from GitHub
fetcher install --providers github eduardoboucas/include-media
fetcher install github:eduardoboucas/include-media # same as above
fetcher install github:eduardoboucas/include-media@latest # same as above

# Install the `include-media` package from a specific commit on GitHub but don't extract it, just download it save it to a custom config file
fetcher i -c content/themes/my-theme/compile.json -s -p github -x eduardoboucas/include-media@"#fb3ab8e"
```

##### Directory Structure

Inside of the `fetched` directory, packages with an author in the name (such as GitHub packages and scoped npm packages) will be installed in `fetched/author/package`. If a package has no author in it's name (such as non-scoped npm packages), it will be installed in `fetched/package`.

When installing a package via a URL or local file path, the basename of the filename will be used as the package name. For example, if I install `package.zip`, it will be installed to `fetched/package`. The exception to this rule is if the package being installed contains a single file with the same extension as the package name. For example if I install `package.css.zip` and that zip file contains a single css file, you will end up with a single file at `fetched/package.css`.

See the [Package Aliases](#package-aliases) section for info about how to customize package names.


#### Uninstall

```sh
fetcher uninstall [packages...]
fetcher u [packages...]
```

Uninstalls the defined packages. If no packages are defined, a confirmation message will ask you if you wish to delete all packages inside the `fetched` directory (and the directory itself).


##### Options

 - `[-c|--config]` - Path to the config file [default: `fetch.json`]
 - `[-d|--install-directory]` - Path where packages are installed to [default: `fetched`]
 - `[-f|--fresh-start]` - Deletes the entire fetched directory and optionally deletes all dependencies from fetch.json
 - `[-q|--quiet]` - Run but don't output anything in the terminal (implies `--skip-prompts`)
 - `[-s|--save]` - Remove the uninstalled packages from the config file's `dependencies` section
 - `[-w|--working-directory]` - Sets the working directory that all paths will be relative to [default: `.`]
 - `[-y|--skip-prompts]` - Skips the confirmation prompt and continues with deletion


##### Examples

```sh
# Uninstall the `include-media` package
fetcher uninstall include-media

# Uninstall the `include-media` package and remove it from a custom config file
fetcher u -c content/themes/my-theme/compile.json -s include-media
```


#### Info

```sh
fetcher info <package>
fetcher n <package>
```

Displays information about a package such as author, homepage, latest version, time of last update, and more.


##### Options

- `[-p|--providers]` - The providers to search for packages from and their order [default: `github,npm`]
- `[-y|--skip-prompts]` - Skips any prompts and displays information for the first available package found


##### Examples

```sh
# Get info about the `include-media` package
fetcher info include-media

# Get info about the `include-media` package from GitHub
fetcher n -p github eduardoboucas/include-media
```


#### Versions

```sh
fetcher versions <package>
fetcher v <package>
```

Displays the available versions of a package.


##### Options

- `[-l|--limit]` - The maximum number of package versions to show (newest versions are display first) [default: `20`]
- `[-p|--providers]` - The providers to search for packages from and their order [default: `github,npm`]


##### Examples

```sh
# Get the latest 20 versions of the `include-media` package
fetcher versions include-media

# Get the latest 5 versions of the `include-media` package available on GitHub (this includes non-tagged commits)
fetcher v -l 5 -p github eduardoboucas/include-media
```


### Global Options

These options can be used with all commands:

 - `[-h|--help]` - Displays helpful information about a command
 - `[-v|--verbosity]` - Sets the verbosity level [default: `0`]
 - `[-V|--version]` - Displays the current Fetcher version


### Package & Version Parsing

Because Fetcher supports multiple package providers and those providers have their own unique ways of naming and structuring packages, Fetcher has it's own unique but familiar syntax for package names and versions.

Fetcher uses [Composer's semver module](https://getcomposer.org/doc/articles/versions.md#writing-version-constraints) for pasing version constraints.

##### Examples

```sh
# latest version of include-media package from no specific provider (will end up being npm because GitHub requires an author)
include-media
include-media@latest # same as above

# latest version of eduardoboucas/include-media package from no specific provider (will end up being GitHub because the include-media package is not scoped on npm [it's name on npm is just include-media rather than @eduardoboucas/include-media])
eduardoboucas/include-media
eduardoboucas/include-media@latest # same as above

# any version of the include-media package specifically from npm that is v1.3.2 or above and below v1.4.0
npm:include-media@~1.3.2

# latest version of the include-media package from npm but give it an alias so it's folder structure is like GitHub's
[eduardoboucas/include-media]npm:include-media

# whatever is currently in the master branch of the eduardoboucas/include-media repository on GitHub
github:eduardoboucas/include-media@dev-master

# a specific commit from the eduardoboucas/include-media repository on GitHub
github:eduardoboucas/include-media@#e620564

# a specific tag from the eduardoboucas/include-media repository on GitHub
github:eduardoboucas/include-media@feature-test
github:eduardoboucas/include-media@tag-feature-test # same as above

# a package from a URL that has been given an alias (in this case we know the archive has a single css file in it so that file will be named wordpress.css)
[wordpress/wordpress.css]https://gist.github.com/kodie/d7da9f3db934adea8e44ee38d1885bf8/archive/aaf369827720c564ec3b6c43cba8b00748dbd73d.zip

# a package from a local file that has been given an alias
[some-package]file:/home/user/Downloads/my-package.zip
```

#### Package Aliases

A package can be given an alias name and alias author. An alias author is only used if the alias name is used. This makes it possible to install multiple versions of the same package as well as name packages that are downloaded via URL and have a hash for a name.

For example, running `fetcher i [scss-helpers/media-queries]npm:include-media` will install the `include-media` package to `fetched/scss-helpers/media-queries`.

Keys in the `dependencies` section are considered aliases if the package name is defined in the version string (see the `scss-helpers/media-queries` example below).


## Configuration

While Fetcher can be used out of the box without any configuration, a config file allows for better customization and easier package management.


##### Example

```json
{
  "fetcher": {
    "config": {
      "extensions": ["js", "css", "scss", "md"],
      "working_directory": "content/themes/my-theme"
    },
    "dependencies": {
      "kenwheeler/slick": "github:~1.8.0",
      "scss-helpers/media-queries": "npm:include-media@^2.0",
      "wordpress/wordpress-core.css": "https://gist.github.com/kodie/d7da9f3db934adea8e44ee38d1885bf8/archive/aaf369827720c564ec3b6c43cba8b00748dbd73d.zip"
    }
  }
}
```


### Configuration via Composer

Fetcher also supports loading configuration options from a `composer.json` file, except for in this case Fetcher checks for it's key under the `extra` section like so:

```json
{
  "name": "username/package",
  "version": "0.0.1",
  "require-dev": {
    "fivefifteen/fetcher": "*"
  },
  "scripts": {
    "fetch": "./vendor/bin/fetcher install"
  },
  "extra": {
    "fetcher": {
      "config": {
        "extensions": ["js", "css", "scss", "md"],
        "working_directory": "content/themes/my-theme"
      },
      "dependencies": {
        "kenwheeler/slick": "github:~1.8.0",
        "scss-helpers/media-queries": "npm:include-media@^2.0",
        "wordpress/wordpress-core.css": "https://gist.github.com/kodie/d7da9f3db934adea8e44ee38d1885bf8/archive/aaf369827720c564ec3b6c43cba8b00748dbd73d.zip"
      }
    }
  }
}
```


## Related Projects

 - [Piler](https://github.com/fivefifteen/piler) - A CLI tool written in PHP that compiles and minifies JavaScript and CSS/SCSS files.


## License Information

MIT. See the [license.md file](license.md) for more info.