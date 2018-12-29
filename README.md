# vendor-compress
Compress vendor directory into one vendor.phar file.

This is a project completely for fun and as a PoC. It is rather unusual to have to compress the vendor directory to save a few megabytes.

The idea for this project was born recently, when I had to work in a very restricted environment (server with 10 MiB disk space for a visit card page) and every byte counted. I decided to share this solution, maybe it will be useful to someone.

The package is created as minimal as possible.

## What does it do?
Basically, it packs the content of `vendor/` directory into single [PHAR file](https://secure.php.net/manual/en/book.phar.php), optionally compressing it and performing other disk-space optimizations. And tries to make all of it working :)

The original `vendor/` directory is not removed nor modified!

## How to use
Generate vendor.phar file:
```
bin/vendor-compress [options]
```

Then use it:
```php
// Replace
require __DIR__ . 'vendor/autoload.php';

// With
require __DIR__ . 'vendor.phar';

// Or replace with auto-detection of proper vendor source
is_file(__DIR__ . 'vendor.phar') ? require __DIR__ . 'vendor.phar' : require __DIR__ . 'vendor/autoload.php';
```

Options:

Option | Description
------ | -----------
`-c, --compression-method=COMPRESSION-METHOD` | Defines the compression method to use when generating PHAR file. It comes with two pre-built options: NONE (no compression) and FIRST (use first supported compression method; default). Besides that, You can use GZ or BZIP2 (available methods will be listed here) depending on [supported compression methods](https://secure.php.net/manual/en/phar.getsupportedcompression.php).
`-o, --override` | Defines to override vendor.phar file if it already exists. Without that, if file exists, the generation will not be performed.
`-h, --help` | Display the help message
`-q, --quiet` | Do not output any message

## Tips for use
- The PHAR file tries to [mount](https://secure.php.net/manual/en/phar.mount.php) directories outside of the `vendor/` directory and is currently only tested for paths directly one level above the `vendor/` directory. If you rely in some way on paths from outside of the root directory of the project, autoload may not work.
- I think that the most useful would be to generate autoloading with the option `--classmap-authoritative` for production. To make autoloading from a PHAR file work, I rewrite the generated file indexes, so it may not work for dynamic file guessing and searching.

## Plans for future
- Currently, `vendor.phar` file is generated out of `vendor/` directory that project is installed in. It might be handy to add `path` option to compress any vendor directory.
- Maybe it is good idea to use [symfony/console](https://packagist.org/packages/symfony/console) (originally it was used)?
- Remove unnecessary whitespaces
