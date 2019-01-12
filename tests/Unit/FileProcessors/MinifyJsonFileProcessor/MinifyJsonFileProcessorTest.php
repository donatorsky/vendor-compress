<?php
declare(strict_types=1);

namespace Tests\Unit\FileProcessors\MinifyJsonFileProcessor;

use Donatorsky\VendorCompress\FileProcessors\MinifyJsonFileProcessor;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Donatorsky\VendorCompress\FileProcessors\MinifyJsonFileProcessor
 */
class MinifyJsonFileProcessorTest extends TestCase {
	/**
	 * @var \Donatorsky\VendorCompress\FileProcessors\MinifyJsonFileProcessor
	 */
	private $minifyJsonFileProcessor;

	/**
	 * {@inheritdoc}
	 */
	protected function setUp(): void {
		$this->minifyJsonFileProcessor = new MinifyJsonFileProcessor();
	}

	/**
	 * Provides a set of valid JSON strings.
	 *
	 * @return array<array>
	 */
	public function validJsonDataProvider(): array {
		return [
			[
				'input'    => $this->getAsset('input0.json'),
				'expected' => $this->getAsset('expected0.json'),
			],
			[
				'input'    => $this->getAsset('input1.json'),
				'expected' => $this->getAsset('expected1.json'),
			],
			[
				'input'    => $this->getAsset('input2.json'),
				'expected' => $this->getAsset('expected2.json'),
			],
		];
	}

	public function testMinifyMalformedJson(): void {
		$json = "{'foo':bar}";

		self::assertEquals($json, $this->minifyJsonFileProcessor->process($json));
	}

	/**
	 * @dataProvider validJsonDataProvider
	 *
	 * @param string $input
	 * @param string $expected
	 */
	public function testProcessValidJson(string $input, string $expected): void {
		self::assertEquals($expected, $this->minifyJsonFileProcessor->process($input));
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
