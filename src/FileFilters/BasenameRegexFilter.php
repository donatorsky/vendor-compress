<?php
declare(strict_types=1);

namespace Donatorsky\VendorCompress\FileFilters;

use Donatorsky\VendorCompress\Contracts\FileFilterInterface;
use SplFileInfo;

class BasenameRegexFilter implements FileFilterInterface {
	/**
	 * @var string
	 */
	private $pattern;

	/**
	 * @param string $pattern
	 */
	public function __construct(string $pattern) {
		$this->pattern = $pattern;
	}

	/**
	 * @inheritdoc
	 */
	public function matches(SplFileInfo $file): bool {
		return \preg_match($this->pattern, $file->getBasename()) > 0;
	}
}
