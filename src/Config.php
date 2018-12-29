<?php
declare(strict_types=1);

namespace Donatorsky\VendorCompress;

use Donatorsky\VendorCompress\Contracts\FileFilterInterface;
use Donatorsky\VendorCompress\Contracts\FileProcessorInterface;
use Phar;

class Config {
	/**
	 * @var string
	 */
	private $alias = 'vendor.phar';

	/**
	 * @var int
	 */
	private $filesCompressionMethod = Phar::NONE;

	/**
	 * @var int
	 */
	private $archiveCompressionMethod = Phar::NONE;

	/**
	 * @var \Donatorsky\VendorCompress\Contracts\FileFilterInterface[]
	 */
	private $excluded = [];

	/**
	 * @var \Donatorsky\VendorCompress\Contracts\FileFilterInterface[]
	 */
	private $included = [];

	/**
	 * @var array<array>
	 */
	private $fileProcessors = [];

	/**
	 * @var bool
	 */
	private $debug = false;

	/**
	 * @return \Donatorsky\VendorCompress\Config
	 */
	public static function create(): self {
		return new self();
	}

	/**
	 * @return string
	 */
	public function getAlias(): string {
		return $this->alias;
	}

	/**
	 * @param string $alias
	 *
	 * @return Config
	 */
	public function setAlias(string $alias): self {
		$this->alias = $alias;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getFilesCompressionMethod(): int {
		return $this->filesCompressionMethod;
	}

	/**
	 * @param int $filesCompressionMethod
	 *
	 * @return Config
	 */
	public function setFilesCompressionMethod(int $filesCompressionMethod): self {
		$this->filesCompressionMethod = $filesCompressionMethod;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getArchiveCompressionMethod(): int {
		return $this->archiveCompressionMethod;
	}

	/**
	 * @param int $archiveCompressionMethod
	 *
	 * @return Config
	 */
	public function setArchiveCompressionMethod(int $archiveCompressionMethod): self {
		$this->archiveCompressionMethod = $archiveCompressionMethod;

		return $this;
	}

	/**
	 * @param \Donatorsky\VendorCompress\Contracts\FileFilterInterface $fileFilter
	 *
	 * @return \Donatorsky\VendorCompress\Config
	 */
	public function addExcluded(FileFilterInterface $fileFilter): self {
		$this->excluded[] = $fileFilter;

		return $this;
	}

	/**
	 * @param \Donatorsky\VendorCompress\Contracts\FileFilterInterface[] $fileFilters
	 *
	 * @return \Donatorsky\VendorCompress\Config
	 */
	public function setExcluded(array $fileFilters): self {
		$this->excluded = [];

		foreach ($fileFilters as $fileFilter) {
			$this->addExcluded($fileFilter);
		}

		return $this;
	}

	/**
	 * @return \Donatorsky\VendorCompress\Contracts\FileFilterInterface[]
	 */
	public function getExcluded(): array {
		return $this->excluded;
	}

	/**
	 * @param \Donatorsky\VendorCompress\Contracts\FileFilterInterface $fileFilter
	 *
	 * @return \Donatorsky\VendorCompress\Config
	 */
	public function addIncluded(FileFilterInterface $fileFilter): self {
		$this->included[] = $fileFilter;

		return $this;
	}

	/**
	 * @param \Donatorsky\VendorCompress\Contracts\FileFilterInterface[] $fileFilters
	 *
	 * @return \Donatorsky\VendorCompress\Config
	 */
	public function setIncluded(array $fileFilters): self {
		$this->included = [];

		foreach ($fileFilters as $fileFilter) {
			$this->addIncluded($fileFilter);
		}

		return $this;
	}

	/**
	 * @return \Donatorsky\VendorCompress\Contracts\FileFilterInterface[]
	 */
	public function getIncluded(): array {
		return $this->included;
	}

	/**
	 * @param \Donatorsky\VendorCompress\Contracts\FileProcessorInterface $fileProcessor
	 * @param \Donatorsky\VendorCompress\Contracts\FileFilterInterface    $fileFilter
	 * @param \Donatorsky\VendorCompress\Contracts\FileFilterInterface    ...$moreFileFilters
	 *
	 * @return \Donatorsky\VendorCompress\Config
	 */
	public function addFileProcessor(FileProcessorInterface $fileProcessor, FileFilterInterface $fileFilter, FileFilterInterface ...$moreFileFilters): self {
		for ($argNum = 1, $argNumMax = \func_num_args(); $argNum < $argNumMax; ++$argNum) {
			$this->fileProcessors[] = [
				'fileProcessor' => $fileProcessor,
				'fileFilter'    => \func_get_arg($argNum),
			];
		}

		return $this;
	}

	/**
	 * @return array<array>
	 */
	public function getFileProcessors(): array {
		return $this->fileProcessors;
	}

	/**
	 * @return bool
	 */
	public function isDebug(): bool {
		return $this->debug;
	}

	/**
	 * @param bool $debug
	 *
	 * @return Config
	 */
	public function setDebug(bool $debug): self {
		$this->debug = $debug;

		return $this;
	}
}
