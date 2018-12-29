<?php
declare(strict_types=1);

namespace Donatorsky\VendorCompress\FileFilters;

use Donatorsky\VendorCompress\Contracts\FileFilterInterface;
use SplFileInfo;

class ExtensionFilter implements FileFilterInterface {
	/**
	 * @var string
	 */
	private $extension;

	/**
	 * @param string $extension
	 */
	public function __construct(string $extension) {
		$this->extension = $extension;
	}

	/**
	 * @inheritdoc
	 */
	public function matches(SplFileInfo $file): bool {
		return \preg_match(\sprintf(
				'/\\.%s$/i',
				\preg_quote($this->extension, '/')
			), $file->getBasename()) > 0;
	}
}
