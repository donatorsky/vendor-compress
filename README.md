# vendor-compress
Compress vendor directory into one vendor.phar file.

This is a project completely for fun and as a PoC. It is rather unusual to have to compress the vendor directory to save a few megabytes.

The idea for this project was born recently, when I had to work in a very restricted environment (server with 10 MiB disk space for a visit card page) and every byte counted. I decided to share this solution, maybe it will be useful to someone.

The package is created as minimal as possible.

[![Build](https://travis-ci.com/donatorsky/vendor-compress.svg?branch=master)](https://travis-ci.com/donatorsky/vendor-compress)

## What does it do?
Basically, it packs the content of `vendor/` directory into single [PHAR file](https://secure.php.net/manual/en/book.phar.php), optionally compressing it and performing other disk-space optimizations. And tries to make all of it working :)

The original `vendor/` directory is not removed nor modified!

## How to use
Generate vendor.phar (or vendor.phar.gz or vendor.phar.bz2 depending on configuration; see below) file:
```
bin/vendor-compress [options] [<path>]
```

Arguments:

Argument | Description
-------- | -----------
`path` | Path to the project or vendor directory from which generate PHAR. If not provided, current project is used.

Options:

Option | Description
------ | -----------
`-c, --configuration` | Path to configuration file.
`    --memory-limit` | Set PHP memory limit for current run.
`-o, --override` | Defines to override vendor.phar file if it already exists. Without that, if file exists, the generation will not be performed.
`-h, --help` | Display the help message
`-q, --quiet` | Do not output any message

Then use it:
```php
// Replace
require __DIR__ . 'vendor/autoload.php';

// With (add .gz/.bz2 if archive-compressed)
require __DIR__ . 'vendor.phar';

// Or replace with auto-detection of proper vendor source
is_file(__DIR__ . 'vendor.phar') ? require __DIR__ . 'vendor.phar' : require __DIR__ . 'vendor/autoload.php';
```

## Configuration
For easier configuration managing, You can create dedicated file that will be loaded. It must return an instance of `\Donatorsky\VendorCompress\Config` class. There is one here (see `.vendor_compress.dist`) that works as a fallback if no configuration was provided:
```php
<?php
declare(strict_types=1);

use Donatorsky\VendorCompress\Config;
use Donatorsky\VendorCompress\FileFilters\BasenameFilter;
use Donatorsky\VendorCompress\FileFilters\BasenameRegexFilter;
use Donatorsky\VendorCompress\FileFilters\ExtensionFilter;
use Donatorsky\VendorCompress\FileFilters\IsDirectoryFilter;
use Donatorsky\VendorCompress\FileFilters\IsFileFilter;
use Donatorsky\VendorCompress\FileFilters\VendorPackageFilter;
use Donatorsky\VendorCompress\FileProcessors\MinifyJsonFileProcessor;
use Donatorsky\VendorCompress\FileProcessors\StripWhitespacesPhpFileProcessor;

return Config::create()
	->setFilesCompressionMethod(Phar::NONE) // It is also default
	->setArchiveCompressionMethod(Phar::NONE) // It is also default
	->setExcluded([
		new VendorPackageFilter('donatorsky', 'vendor-compress'),
		new IsDirectoryFilter(new BasenameFilter('docs')),
		new IsDirectoryFilter(new BasenameFilter('tests')),
		new IsFileFilter(new BasenameFilter('composer.json')),
		new IsFileFilter(new BasenameFilter('composer.lock')),
		new IsFileFilter(new BasenameFilter('.gitignore')),
		new IsFileFilter(new BasenameRegexFilter('/^(?:README|CHANGELOG|phpunit.*\.xml.*$)/i')),
	])
	->addFileProcessor(new MinifyJsonFileProcessor(), new ExtensionFilter('json'))
	->addFileProcessor(new StripWhitespacesPhpFileProcessor(), new ExtensionFilter('php'));
```

You can create your own `.vendor_compress` or `.vendor_compress.dist` configuraton file in working directory (usually project's root directory) or specify path to the configuration file using `--configuration` option. Tthe order of loading configurationis:
- One specified via `--configuration`. If does no exist, command will fail.
- `Current Working Directory (CWD)/.vendor_compress`,
- `CWD/.vendor_compress.dist`,
- `vendor/donatorsky/vendor-compress/.vendor_compress.dist`).

### Available configuration options
Below is the list of all available options.

#### setFilesCompressionMethod(int $filesCompressionMethod)
Allows to set compression method that will be used for compressing each file individually. `$filesCompressionMethod` should be one of supported compressions available via `\Phar::CONSTANT` constant.

#### setArchiveCompressionMethod(int $archiveCompressionMethod)
Allows to set compression method that will be used for compressing whole file. The difference between `setFilesCompressionMethod()` is that with this option, the whole archive will be compressed at once at the end. It may be more efficient to do this in that way (see comparison below). `$archiveCompressionMethod` should be one of supported compressions available via `\Phar::CONSTANT` constant.

#### setAlias(string $alias)
Allows to alias with which generated Phar archive should be referred to in calls to stream functionality. See more at: https://secure.php.net/manual/en/phar.construct.php.

#### addExcluded(\Donatorsky\VendorCompress\Contracts\FileFilterInterface $fileFilter)
Allows to add file/directory exclusion rule used for vendor files list generation. There are already some rules included (see `src/FileFilters/`) that You can use, or write Your own.

#### setExcluded(\Donatorsky\VendorCompress\Contracts\FileFilterInterface[] $fileFilters)
Allows to set multiple file/directory exclusion rules at once. This overrides existing list of rules.

#### addIncluded(\Donatorsky\VendorCompress\Contracts\FileFilterInterface $fileFilter)
Allows to add file/directory inclusion rule used for vendor files list generation. There are already some rules included (see `src/FileFilters/`) that You can use, or write Your own. Inclusion rules have higher priority than exclusions. However there is something tricky worth mentioning:
- By default, all content of the `vendor/` directory is included.
- Excluding eg. path `vendor-name/package-name` will exclude content of this directory.
- Including `vendor-name` directory will override exclusion from above.
- Including `vendor-name/package-name/subdirectory` will not work, as `vendor-name/package-name` is already excluded (it is due to how [RecursiveDirectoryIterator](https://secure.php.net/manual/en/class.recursivedirectoryiterator.php) with [RecursiveCallbackFilterIterator](https://secure.php.net/manual/en/class.recursivecallbackfilteriterator.php) works). TODO for fixing in future :)
- Let's assume, that there is `vendor-name/package-name/important_file.php`. Including it will not work either.

#### setIncluded(\Donatorsky\VendorCompress\Contracts\FileFilterInterface[] $fileFilters)
Allows to set multiple file/directory inclusion rules at once. This overrides existing list of rules.

#### addFileProcessor(\Donatorsky\VendorCompress\Contracts\FileProcessorInterface $fileProcessor, \Donatorsky\VendorCompress\Contracts\FileFilterInterface $fileFilter, ...$moreFileFilters)
There are also file processors. They define some file content manipulations that should be performed before it is added to the archive. Eg. You can minify PHP code. It is possible to define multiple file rules to which content manipulation should be applied to.

## Compression ratio comparison
Initial `vendor/` size: 43.348 MiB

Number of files: 9664

Number of files after exclusions: 6992

Files \ Archive compression | NONE | GZ | BZ2
--------------------------- | ---- | -- | ---
NONE | 26.731 MiB (61.7%) | 6.914 MiB (15.9%) | **5.885 MiB (13.6%)**
GZ   | 8.788 MiB (20.3%) | 8.313 MiB (19.2%) | 8.356 MiB (19.3%)
BZ2  | 8.517 MiB (19.6%) | 7.947 MiB (18.3%) | 7.957 MiB (18.4%)

_Note: Only by removing docs and tests (mainly) the size of vendor decreases by ~38%._

_TODO: Add some generation times to comparison above._

## Exit codes
All exit codes constants can be found in `bin/constants.php` file.

Exit code | Name | Description
--------- | ---- | -----------
`0`  | `VENDOR_COMPRESS_ERR_OK` | No error.
`1`  | `VENDOR_COMPRESS_ERR_UNSUPPORTED_PHP_VERSION` | Returned when minimum PHP version requirement is not fulfilled.
`2`  | `VENDOR_COMPRESS_ERR_MISSING_PHAR_CLASS` | Returned when \Phar class is missing.
`3`  | `VENDOR_COMPRESS_ERR_PROJECT_NOT_SET_UP` | Returned when vendor directory could not be found or `composer install` is not performed.
`4`  | `VENDOR_COMPRESS_ERR_CANNOT_WRITE_PHAR_FILES` | Returned when PHAR files writing is not allowed (according to https://secure.php.net/manual/en/phar.canwrite.php).
`5`  | `VENDOR_COMPRESS_ERR_MISSING_REQUIRED_OPTION_VALUE` | Returned when command option is passed without its required value.
`6`  | `VENDOR_COMPRESS_ERR_UNKNOWN_OPTION` | Returned when unknown command option is passed.
`7`  | `VENDOR_COMPRESS_ERR_UNKNOWN_ARGUMENT` | Returned when unknown command argument is passed.
`8`  | `VENDOR_COMPRESS_ERR_MISSING_REQUIRED_ARGUMENT_VALUE` | Returned when command argument is passed without its required value.
`9`  | `VENDOR_COMPRESS_ERR_COULD_NOT_DELETE_VENDOR_PHAR` | Returned when vendor.phar.[.*] file could not be removed.
`10` | `VENDOR_COMPRESS_ERR_COULD_NOT_CREATE_VENDOR_PHAR` | Retuend when there is an error during Phar generation. The error message is sent to stderr.
`11` | `VENDOR_COMPRESS_ERR_INACCESSIBLE_VENDOR_PATH` | Returned when path to vendor directory is invalid or inaccessible.
`12` | `VENDOR_COMPRESS_ERR_INACCESSIBLE_CONFIGURATION_FILE` | Returned when specified configuration file could not be found or accessed.
`13` | `VENDOR_COMPRESS_ERR_INVALID_CONFIGURATION_FILE` | Returned when configuration file is invalid (i.e. it is not an instance of `\Donatorsky\VendorCompress\Config` class).

## Tips for use
- The PHAR file tries to [mount](https://secure.php.net/manual/en/phar.mount.php) directories outside of the `vendor/` directory and is currently only tested for paths directly one level above the `vendor/` directory. If you rely in some way on paths from outside of the root directory of the project, autoload may not work.
- I think that the most useful would be to generate autoloading with the option `--classmap-authoritative` for production. To make autoloading from a PHAR file work, I rewrite the generated file indexes, so it may not work for dynamic file guessing and searching.

## Plans for future
- Currently, `vendor.phar` file is generated out of `vendor/` directory that project is installed in. It might be handy to add `path` option to compress any vendor directory.
- Maybe it is good idea to use [symfony/console](https://packagist.org/packages/symfony/console) (originally it was used)?
- Remove unnecessary whitespaces (a.k.a finish `\Donatorsky\VendorCompress\FileProcessors\StripWhitespacesPhpFileProcessor`)
