<?php
declare(strict_types=1);

namespace Donatorsky\VendorCompress;

use Generator;
use InvalidArgumentException;
use Iterator;
use Phar;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

class VendorCompress {
	public const VERSION = '2.0.0';

	/**
	 * @var \Donatorsky\VendorCompress\Config
	 */
	private $config;

	/**
	 * @var string
	 */
	private $vendorDirectoryPath;

	/**
	 * @var string
	 */
	private $vendorPharPath;

	/**
	 * @param string                            $vendorDirectoryPath Path to vendor directory based on which PHAR file will be created
	 * @param string                            $vendorPharPath      Path in which vendor.phar file will be generated
	 * @param \Donatorsky\VendorCompress\Config $config              The configuration
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct(string $vendorDirectoryPath, string $vendorPharPath, Config $config) {
		$vendorDirectoryPath = \realpath($vendorDirectoryPath);

		if (false === $vendorDirectoryPath) {
			throw new InvalidArgumentException('The vendor path does not exist or is inaccessible.');
		}

		$pathInfo = \pathinfo($vendorPharPath);
		$realVendorPharPath = ('.' === $pathInfo['dirname']) ? \dirname($vendorDirectoryPath) : $pathInfo['dirname'];

		$cwd = \getcwd();
		\chdir(\dirname($vendorDirectoryPath));

		if (false === ($realVendorPharPath = \realpath($realVendorPharPath))) {
			throw new InvalidArgumentException('The path for PHAR file does not exist or is inaccessible.');
		}

		\chdir((false === $cwd) ? __DIR__ : $cwd);

		$this->vendorDirectoryPath = $vendorDirectoryPath;
		$this->vendorPharPath = $realVendorPharPath . DIRECTORY_SEPARATOR . $pathInfo['basename'];
		$this->config = $config;
	}

	/**
	 * @return string
	 */
	public function getVendorDirectoryPath(): string {
		return $this->vendorDirectoryPath;
	}

	/**
	 * @return string
	 */
	public function getVendorPharPath(): string {
		return $this->vendorPharPath;
	}

	/**
	 * @return \Donatorsky\VendorCompress\Config
	 */
	public function getConfig(): Config {
		return $this->config;
	}

	/**
	 * @return \Phar
	 */
	public function create(): Phar {
		/**
		 * @var array<string, \PharFileInfo>&\Phar
		 */
		$phar = new Phar($this->vendorPharPath, Phar::KEY_AS_PATHNAME | Phar::CURRENT_AS_FILEINFO, $this->config->getAlias());

		$phar->startBuffering();

		$TStart = 0.0;
		$filesIterator = $this->getFilesIterator();
		$processedFilesIterator = $this->getProcessedFilesIterator($filesIterator);

		if ($this->config->isDebug()) {
			// @codeCoverageIgnoreStart
			\printf(
				"\nusage = %.2f MiB, usage(true) = %.2f MiB, peak_usage(true) = %.2f MiB, peak_usage(true) = %.2f MiB\n",
				\memory_get_usage() / 1024 ** 2,
				\memory_get_usage(true) / 1024 ** 2,
				\memory_get_peak_usage() / 1024 ** 2,
				\memory_get_peak_usage(true) / 1024 ** 2
			);

			$TStart = \microtime(true);
			\iterator_to_array($filesIterator);
			\printf("getFilesIterator(): %.6f s\n", \microtime(true) - $TStart);

			$TStart = \microtime(true);
			// @codeCoverageIgnoreEnd
		}

		$tmpRootPath = $this->createTemporaryVendorDirectory($processedFilesIterator);

		if ($this->config->isDebug()) {
			// @codeCoverageIgnoreStart
			echo $tmpRootPath . PHP_EOL;

			\printf("Generate files in tmp dir: %.6f s\n", \microtime(true) - $TStart);

			$TStart = \microtime(true);
			// @codeCoverageIgnoreEnd
		}

		$phar->buildFromDirectory($tmpRootPath);

		if ($this->config->isDebug()) {
			// @codeCoverageIgnoreStart
			\printf("buildFromDirectory(): %.6f s\n", \microtime(true) - $TStart);
			// @codeCoverageIgnoreEnd
		}

		if (isset($phar['composer/autoload_classmap.php'])) {
			$this->updateBaseDir($phar, 'composer/autoload_classmap.php');
		}

		if (isset($phar['composer/autoload_files.php'])) {
			$this->updateBaseDir($phar, 'composer/autoload_files.php');
		}

		if (isset($phar['composer/autoload_namespaces.php'])) {
			$this->updateBaseDir($phar, 'composer/autoload_namespaces.php');
		}

		if (isset($phar['composer/autoload_psr4.php'])) {
			$this->updateBaseDir($phar, 'composer/autoload_psr4.php');
		}

		if (isset($phar['composer/autoload_static.php'])) {
			$content = \file_get_contents($phar['composer/autoload_static.php']->getPathname());

			if (false === $content) {
				// @codeCoverageIgnoreStart
				throw new RuntimeException('Could not read the content of "composer/autoload_classmap.php" file.');
				// @codeCoverageIgnoreEnd
			}

			/**
			 * @var string $autoloadStaticContent
			 */
			$autoloadStaticContent = \preg_replace(
				'/__DIR__\s*\.\s*\'\/..\/..\'\s*\.\s*/m',
				'PHAR_RUNNING . ',
				$content,
				-1,
				$replaced
			);

			if ($replaced > 0) {
				$autoloadStaticContent = \str_replace(
					'namespace Composer\Autoload;',
					'namespace Composer\Autoload;' . PHP_EOL . PHP_EOL . "define('PHAR_RUNNING',\\Phar::running(true).'/.mount');",
					$autoloadStaticContent
				);
			}

			$phar['composer/autoload_static.php'] = $autoloadStaticContent;
		}

		$phar->setStub(\sprintf(
				<<<'STUB'
<?php
declare(strict_types=1);

/**
 * Generated by vendor-compress.
 *
 * @version %s
 *
 * @see https://github.com/donatorsky/vendor-compress
 */

\Phar::interceptFileFuncs();
\Phar::mount(\Phar::running(true) . '/.mount/', __DIR__ . DIRECTORY_SEPARATOR);

return require_once 'phar://' . __FILE__ . DIRECTORY_SEPARATOR . 'autoload.php';

__HALT_COMPILER();
STUB
				,
				self::VERSION
			)
		);

		if (Phar::NONE !== $this->config->getFilesCompressionMethod()) {
			$TStart = \microtime(true);

			$phar->compressFiles($this->config->getFilesCompressionMethod());

			if ($this->config->isDebug()) {
				// @codeCoverageIgnoreStart
				\printf("compressFiles(): %.6f s\n", \microtime(true) - $TStart);
				// @codeCoverageIgnoreEnd
			}
		}

		$phar->stopBuffering();

		if (Phar::NONE !== $this->config->getArchiveCompressionMethod()) {
			$TStart = \microtime(true);

			$phar->compress($this->config->getArchiveCompressionMethod());

			if ($this->config->isDebug()) {
				// @codeCoverageIgnoreStart
				\printf("compress(): %.6f s\n", \microtime(true) - $TStart);
				// @codeCoverageIgnoreEnd
			}
		}

		if ($this->config->isDebug()) {
			// @codeCoverageIgnoreStart
			\printf(
				"usage = %.2f MiB, usage(true) = %.2f MiB, peak_usage(true) = %.2f MiB, peak_usage(true) = %.2f MiB\n",
				\memory_get_usage() / 1024 ** 2,
				\memory_get_usage(true) / 1024 ** 2,
				\memory_get_peak_usage() / 1024 ** 2,
				\memory_get_peak_usage(true) / 1024 ** 2
			);
			// @codeCoverageIgnoreEnd
		}

		return $phar;
	}

	/**
	 * @param \Iterator $filesIterator
	 *
	 * @return \Generator
	 */
	private function getProcessedFilesIterator(Iterator $filesIterator): Generator {
		$fileProcessors = $this->config->getFileProcessors();
		$rootPathLength = \strlen($this->vendorDirectoryPath);

		/**
		 * @var \SplFileInfo $file
		 */
		foreach ($filesIterator as $absolutePath => $file) {
			$realPath = $file->getRealPath();

			if (false === $realPath) {
				// @codeCoverageIgnoreStart
				throw new RuntimeException(\sprintf('Could not access "%s" file.', $absolutePath));
				// @codeCoverageIgnoreEnd
			}

			$fileContent = \file_get_contents($realPath);

			if (false === $fileContent) {
				// @codeCoverageIgnoreStart
				throw new RuntimeException(\sprintf('Could not read the content of "%s" file.', $realPath));
				// @codeCoverageIgnoreEnd
			}

			/**
			 * @var \Donatorsky\VendorCompress\Contracts\FileFilterInterface    $fileFilter
			 * @var \Donatorsky\VendorCompress\Contracts\FileProcessorInterface $fileProcessor
			 */
			foreach ($fileProcessors as ['fileFilter' => $fileFilter, 'fileProcessor' => $fileProcessor]) {
				if ($fileFilter->matches($file)) {
					$fileContent = $fileProcessor->process($fileContent);
				}
			}

			yield \substr($absolutePath, $rootPathLength) => $fileContent;
		}
	}

	/**
	 * @return \RecursiveIteratorIterator<string, \SplFileInfo>
	 */
	private function getFilesIterator(): RecursiveIteratorIterator {
		$excluded = $this->config->getExcluded();
		$included = $this->config->getIncluded();

		return new RecursiveIteratorIterator(
			new RecursiveCallbackFilterIterator(
				new RecursiveDirectoryIterator(
					$this->vendorDirectoryPath,
					RecursiveDirectoryIterator::SKIP_DOTS
				),
				function (SplFileInfo $current) use (&$excluded, &$included): bool {
					foreach ($excluded as $excludeFileFilter) {
						if ($excludeFileFilter->matches($current)) {
							foreach ($included as $includeFileFilter) {
								if ($includeFileFilter->matches($current)) {
									return true;
								}
							}

							return false;
						}
					}

					return true;
				}
			),
			RecursiveIteratorIterator::LEAVES_ONLY
		);
	}

	/**
	 * @param \Iterator $processedFilesIterator
	 *
	 * @throws \RuntimeException
	 *
	 * @return string
	 *
	 *
	 * @codeCoverageIgnore
	 */
	private function createTemporaryVendorDirectory(Iterator $processedFilesIterator): string {
		$tmpRootPath = \sys_get_temp_dir() . DIRECTORY_SEPARATOR . \uniqid('vendor', true) . DIRECTORY_SEPARATOR;

		$this->createTemporaryDirectoryOrFail($tmpRootPath);

		foreach ($processedFilesIterator as $relativePath => $content) {
			$this->createTemporaryDirectoryOrFail($tmpRootPath . \dirname($relativePath), 0777, true);

			\file_put_contents($tmpRootPath . $relativePath, $content);
		}

		return $tmpRootPath;
	}

	/**
	 * @param string $pathname
	 * @param int    $mode
	 * @param bool   $recursive
	 *
	 * @see \mkdir()
	 *
	 * @codeCoverageIgnore
	 */
	private function createTemporaryDirectoryOrFail(string $pathname, int $mode = 0777, $recursive = false): void {
		if (!\is_dir($pathname) && !\mkdir($pathname, $mode, $recursive) && !\is_dir($pathname)) {
			throw new RuntimeException('Could not create temporary directory');
		}
	}

	/**
	 * @param array<string, \PharFileInfo>&\Phar $phar
	 * @param string                             $file
	 *
	 * @throws \RuntimeException
	 */
	private function updateBaseDir(Phar $phar, string $file): void {
		$content = \file_get_contents($phar[$file]->getPathname());

		if (false === $content) {
			// @codeCoverageIgnoreStart
			throw new RuntimeException(\sprintf('Could not read the content of "%s" file.', $file));
			// @codeCoverageIgnoreEnd
		}

		$phar[$file] = \preg_replace(
			'/\$baseDir\s*=\s*dirname\(\$vendorDir\);/m',
			'$baseDir=\\\\Phar::running(true).\'/.mount\';',
			$content
		);
	}
}
