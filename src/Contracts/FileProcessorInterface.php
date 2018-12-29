<?php
declare(strict_types=1);

namespace Donatorsky\VendorCompress\Contracts;

interface FileProcessorInterface {
	/**
	 * @param string $fileContent
	 *
	 * @return string
	 */
	public function process(string $fileContent): string;
}
