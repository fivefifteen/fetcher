<div align="center">

  ![Fetcher](./assets/fetcher.png)

  # Fetcher

  A package manager written in PHP that supports installing dependencies from [GitHub](https://github.org), [npm](https://npmjs.com), custom URLs, and local file paths. 🐶

  [![packagist package version](https://img.shields.io/packagist/v/fivefifteen/fetcher.svg?style=flat-square)](https://packagist.org/packages/fivefifteen/fetcher)
  [![packagist package downloads](https://img.shields.io/packagist/dt/fivefifteen/fetcher.svg?style=flat-square)](https://packagist.org/packages/fivefifteen/fetcher)
  [![GitHub code size in bytes](https://img.shields.io/github/languages/code-size/fivefifteen/fetcher?style=flat-square)](https://github.com/fivefifteen/fetcher)
  [![license](https://img.shields.io/github/license/fivefifteen/fetcher.svg?style=flat-square)](https://github.com/fivefifteen/fetcher/blob/main/license.md)

  <a href="https://asciinema.org/a/690098" target="_blank"><img src="https://asciinema.org/a/690098.svg" width="75%" /></a>

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
- [Configuration](#configuration)
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

Installs the defined packages. If no packages are defined, fetcher will attempt to locate a `fetch.json` file and install packages located in it's `dependencies` section.

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


## Configuration

While Fetcher can be used out of the box without any configuration, a config file allows for better customization and easier package management.


##### Example

```json
{
  "dependencies": {
    "eduardoboucas/include-media": "npm:include-media@^2.0",
    "kenwheeler/slick": "github:~1.8.0",
    "wordpress/wordpress-core.css": "https://gist.github.com/kodie/d7da9f3db934adea8e44ee38d1885bf8/archive/aaf369827720c564ec3b6c43cba8b00748dbd73d.zip"
  },
  "settings": {
    "fetcher": {
      "extensions": ["js", "css", "scss", "md"],
      "working_directory": "content/themes/my-theme"
    }
  }
}
```


## License Information

MIT. See the [license.md file](license.md) for more info.