<?php
declare(strict_types=1);

namespace Donatorsky\VendorCompress\FileFilters;

use Donatorsky\VendorCompress\Contracts\FileFilterInterface;
use SplFileInfo;

class VendorPackageFilter implements FileFilterInterface {
	/**
	 * @var string
	 */
	private $vendorName;

	/**
	 * @var string|null
	 */
	private $packageName;

	/**
	 * @param string      $vendorName
	 * @param string|null $packageName
	 */
	public function __construct(string $vendorName, ?string $packageName = null) {
		$this->vendorName = $vendorName;
		$this->packageName = $packageName;
	}

	/**
	 * @inheritdoc
	 */
	public function matches(SplFileInfo $file): bool {
		return $file->isDir() &&
			\preg_match(\sprintf(
				'/(?(DEFINE)(?\'DS\'[\/\\\\]))vendor(?P>DS)%s%s(?:$|(?P>DS))/i',
				$this->vendorName,
				(null !== $this->packageName) ? \sprintf('(?P>DS)%s', $this->packageName) : ''
			), $file->getPathname());
	}
}
