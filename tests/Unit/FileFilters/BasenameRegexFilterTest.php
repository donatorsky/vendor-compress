<?php
declare(strict_types=1);

namespace Tests\Unit\FileFilters;

use Donatorsky\VendorCompress\FileFilters\BasenameRegexFilter;
use PHPUnit\Framework\TestCase;
use SplFileInfo;
use Tests\Extensions\Traits\FakerTrait;
use Tests\Tests\Extensions\Traits\SplFileInfoAssertsTrait;

/**
 * @coversDefaultClass \Donatorsky\VendorCompress\FileFilters\BasenameRegexFilter
 */
class BasenameRegexFilterTest extends TestCase {
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
	 * @var \Donatorsky\VendorCompress\FileFilters\BasenameRegexFilter
	 */
	private $basenameRegexFilter;

	/**
	 * @inheritdoc
	 */
	protected function setUp(): void {
		$this->splFileInfoProphecy = $this->prophesize(SplFileInfo::class);

		$this->splFileInfoMock = $this->splFileInfoProphecy->reveal();

		$this->basenameRegexFilter = new BasenameRegexFilter('/^\d{6}-\w+\.\w+$/');
	}

	public function testPatternMatches(): void {
		$name = $this->fakeFileName();

		$this->splFileInfoProphecy->getBasename()
			->shouldBeCalledTimes(2)
			->willreturn($name);

		$this->assertSplFileInfoIsDir($this->splFileInfoProphecy);
		self::assertTrue($this->basenameRegexFilter->matches($this->splFileInfoMock));

		$this->assertSplFileInfoIsFile($this->splFileInfoProphecy);
		self::assertTrue($this->basenameRegexFilter->matches($this->splFileInfoMock));
	}

	public function testPatternDoesNotMatch(): void {
		$name = $this->fakeFileName();

		$this->splFileInfoProphecy->getBasename()
			->shouldBeCalledTimes(2)
			->willreturn(\sprintf('%02d%s', $this->faker()->unique()->numberBetween(0, 99), $name));

		$this->assertSplFileInfoIsDir($this->splFileInfoProphecy);
		self::assertFalse($this->basenameRegexFilter->matches($this->splFileInfoMock));

		$this->assertSplFileInfoIsFile($this->splFileInfoProphecy);
		self::assertFalse($this->basenameRegexFilter->matches($this->splFileInfoMock));
	}

	/**
	 * @return string
	 */
	private function fakeFileName(): string {
		return \sprintf('%06d-%s.%s', $this->faker()->unique()->numberBetween(0, 999999), $this->faker()->unique()->word, $this->faker()->unique()->fileExtension);
	}
}
