<?php
declare(strict_types=1);

namespace Donatorsky\VendorCompress\Contracts;

use SplFileInfo;

interface FileFilterInterface {
	/**
	 * @param \SplFileInfo $file
	 *
	 * @return bool
	 */
	public function matches(SplFileInfo $file): bool;
}
