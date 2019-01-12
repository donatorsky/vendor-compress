<?php
declare(strict_types=1);

namespace Donatorsky\VendorCompress\FileProcessors;

use Donatorsky\VendorCompress\Contracts\FileProcessorInterface;

class MinifyJsonFileProcessor implements FileProcessorInterface {
	/**
	 * {@inheritdoc}
	 */
	public function process(string $fileContent): string {
		$decodedJson = \json_decode($fileContent, true);

		if (JSON_ERROR_NONE !== \json_last_error()) {
			return $fileContent;
		}

		$encodedJson = \json_encode($decodedJson, JSON_PRESERVE_ZERO_FRACTION);

		// @codeCoverageIgnoreStart
		if (false === $encodedJson) {
			return $fileContent;
		}
		// @codeCoverageIgnoreEnd

		return $encodedJson;
	}
}
