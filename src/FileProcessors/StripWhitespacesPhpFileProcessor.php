<?php
declare(strict_types=1);

namespace Donatorsky\VendorCompress\FileProcessors;

use Donatorsky\VendorCompress\Contracts\FileProcessorInterface;

class StripWhitespacesPhpFileProcessor implements FileProcessorInterface {
	/**
	 * {@inheritdoc}
	 */
	public function process(string $fileContent): string {
		$tokens = \token_get_all($fileContent);
		$newContent = '';

		foreach ($tokens as $token) {
			if (\is_string($token)) {
				$newContent .= \trim($token);

				continue;
			}

			[$id, $text] = $token;

			switch ($id) {
				// Skip comments
				case T_OPEN_TAG:
					$newContent .= \preg_replace('/[\n\r]+/m', "\n", $text);

				break;

				// Skip comments
				case T_COMMENT:
				case T_DOC_COMMENT:
					//
				break;

				// Strip whitespaces
				case T_WHITESPACE:
					$newContent .= \preg_replace([
						'/[\n\r]+/m',
						'/[ \t]+/',
					], [
						"\n",
						' ',
					], $text);

				break;

				// Save allowed tokens
				default:
					$newContent .= $text;

				break;
			}
		}

		return $newContent;
	}
}
