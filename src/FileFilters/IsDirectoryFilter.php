<?php
declare(strict_types=1);

namespace Donatorsky\VendorCompress\FileFilters;

use Donatorsky\VendorCompress\Contracts\FileFilterInterface;
use SplFileInfo;

class IsDirectoryFilter implements FileFilterInterface {
	/**
	 * @var \Donatorsky\VendorCompress\Contracts\FileFilterInterface
	 */
	private $decorated;

	/**
	 * @param \Donatorsky\VendorCompress\Contracts\FileFilterInterface $decorated
	 */
	public function __construct(FileFilterInterface $decorated) {
		$this->decorated = $decorated;
	}

	/**
	 * @inheritdoc
	 */
	public function matches(SplFileInfo $file): bool {
		return $file->isDir() && $this->decorated->matches($file);
	}
}
