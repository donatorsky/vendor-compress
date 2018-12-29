<?php
declare(strict_types=1);

namespace Donatorsky\VendorCompress\FileFilters;

use Donatorsky\VendorCompress\Contracts\FileFilterInterface;
use SplFileInfo;

class BasenameFilter implements FileFilterInterface {
	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var bool
	 */
	private $caseSensitive;

	/**
	 * @param string $name
	 * @param bool   $caseSensitive
	 */
	public function __construct(string $name, bool $caseSensitive = false) {
		$this->name = $name;
		$this->caseSensitive = $caseSensitive;
	}

	/**
	 * @inheritdoc
	 */
	public function matches(SplFileInfo $file): bool {
		return \preg_match(\sprintf(
				'/^%s$/%s',
				\preg_quote($this->name, '/'),
				$this->caseSensitive ? '' : 'i'
			), $file->getBasename()) > 0;
	}
}
