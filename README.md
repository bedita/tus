# BEdita/Tus plugin for CakePHP

This plugin enable BEdita API to use [tus](https://tus.io/) protocol to upload files and create associated BEdita media object types.

## Installation

You can install this plugin into your CakePHP application using [composer](https://getcomposer.org).

The recommended way to install composer packages is:

```
composer require bedita/tus
```

## Configuration

The `config/config.php` contains the configurations needed.

## Usage

By default the plugin exposes a route `/tus` (configurable via `endpoint` key`) on which the tus server will respond.
The client must send a tus request to `/tus/{type}` where `{type}` is the object type that you want
associate to the file uploaded.
The upload request must contain a bearer authorization header as expected from BEdita API.

At the end of the upload a BEdita object `{type}`will be created and the tus response will be decorated
with the headers

```
BEdita-Object-Id: <id>
BEdita-Object-Type: <type>
```

containing the BEdita object id and type.
