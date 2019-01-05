<?php
declare(strict_types=1);

namespace Tests\Unit\FileProcessors\StripWhitespacesPhpFileProcessor;

use Donatorsky\VendorCompress\FileProcessors\StripWhitespacesPhpFileProcessor;
use PHPUnit\Framework\TestCase;

class StripWhitespacesPhpFileProcessorTest extends TestCase {
	/**
	 * @var \Donatorsky\VendorCompress\FileProcessors\StripWhitespacesPhpFileProcessor
	 */
	private $stripWhitespacesPhpFileProcessor;

	/**
	 * @inheritdoc
	 */
	protected function setUp(): void {
		$this->stripWhitespacesPhpFileProcessor = new StripWhitespacesPhpFileProcessor();
	}

	/**
	 * Provides a set of assets data.
	 *
	 * @return array<array>
	 */
	public function assetsDataProvider(): array {
		return [
			[
				'input'    => $this->getAsset('input0.php.txt'),
				'expected' => $this->getAsset('expected0.php.txt'),
			],
		];
	}

	/**
	 * @dataProvider assetsDataProvider
	 *
	 * @param string $input
	 * @param string $expected
	 */
	public function testProcessed(string $input, string $expected): void {
		self::assertEquals($expected, $this->stripWhitespacesPhpFileProcessor->process($input));
	}

	/**
	 * @param string $file
	 *
	 * @return string
	 */
	private function getAsset(string $file): string {
		return (string) \file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . $file);
	}
}
