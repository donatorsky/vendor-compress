<?php
declare(strict_types=1);

namespace Tests\Unit\FileFilters;

use Donatorsky\VendorCompress\FileFilters\ExtensionFilter;
use PHPUnit\Framework\TestCase;
use SplFileInfo;
use Tests\Extensions\Traits\FakerTrait;
use Tests\Tests\Extensions\Traits\SplFileInfoAssertsTrait;

/**
 * @coversDefaultClass \Donatorsky\VendorCompress\FileFilters\ExtensionFilter
 */
class ExtensionFilterTest extends TestCase {
	use FakerTrait, SplFileInfoAssertsTrait;

	/**
	 * @var \Prophecy\Prophecy\ObjectProphecy&\SplFileInfo
	 */
	private $splFileInfoProphecy;

	/**
	 * @var \SplFileInfo
	 */
	private $splFileInfoMock;

	/**
	 * @var \Donatorsky\VendorCompress\FileFilters\ExtensionFilter
	 */
	private $extensionFilter;

	/**
	 * {@inheritdoc}
	 */
	protected function setUp(): void {
		$this->splFileInfoProphecy = $this->prophesize(SplFileInfo::class);

		$this->splFileInfoMock = $this->splFileInfoProphecy->reveal();

		$this->extensionFilter = new ExtensionFilter('phar.gz');
	}

	/**
	 * Provides a set of matching names.
	 *
	 * @return array<array>
	 */
	public function matchingNamesDataProvider(): array {
		return [
			[
				'name' => 'vendor.phar.gz',
			],
			[
				'name' => 'another-name.extension.phar.gz',
			],
		];
	}

	/**
	 * Provides a set of mismatching names.
	 *
	 * @return array<array>
	 */
	public function mismatchingNamesDataProvider(): array {
		return [
			[
				'name' => 'vendor.phar.bz2',
			],
			[
				'name' => 'vendor.phar.gz.bz2',
			],
		];
	}

	/**
	 * @dataProvider matchingNamesDataProvider
	 *
	 * @param string $name
	 */
	public function testExtensionMatches(string $name): void {
		$this->assertSplFileInfoIsFile($this->splFileInfoProphecy);

		$this->splFileInfoProphecy->isFile()
			->shouldBeCalledTimes(1);

		$this->splFileInfoProphecy->getBasename()
			->shouldBeCalledTimes(1)
			->willreturn($name);

		self::assertTrue($this->extensionFilter->matches($this->splFileInfoMock));
	}

	/**
	 * @dataProvider mismatchingNamesDataProvider
	 *
	 * @param string $name
	 */
	public function testExtensionDoesNotMatch(string $name): void {
		$this->assertSplFileInfoIsFile($this->splFileInfoProphecy);

		$this->splFileInfoProphecy->isFile()
			->shouldBeCalledTimes(1);

		$this->splFileInfoProphecy->getBasename()
			->shouldBeCalledTimes(1)
			->willreturn($name);

		self::assertFalse($this->extensionFilter->matches($this->splFileInfoMock));
	}

	/**
	 * @dataProvider matchingNamesDataProvider
	 * @dataProvider mismatchingNamesDataProvider
	 *
	 * @param string $name
	 */
	public function testDoNotMatchDirectory(string $name): void {
		$this->assertSplFileInfoIsDir($this->splFileInfoProphecy);

		$this->splFileInfoProphecy->isFile()
			->shouldBeCalledTimes(1);

		$this->splFileInfoProphecy->getBasename()
			->shouldNotBeCalled()
			->willreturn($name);

		self::assertFalse($this->extensionFilter->matches($this->splFileInfoMock));
	}
}
