<?php
declare(strict_types=1);

namespace Donatorsky\VendorCompress\FileProcessors;

use Donatorsky\VendorCompress\Contracts\FileProcessorInterface;

class StripWhitespacesPhpFileProcessor implements FileProcessorInterface {
	/**
	 * @inheritdoc
	 */
	public function process(string $fileContent): string {
		$tokens = \token_get_all($fileContent);
		$newContent = '';

		for ($x = 0, $xMax = \count($tokens); $x < $xMax; ++$x) {
			if (\is_string($tokens[$x])) {
				$newContent .= \trim($tokens[$x]);

				continue;
			}

			[$id, $text] = $tokens[$x];

			switch ($id) {
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
