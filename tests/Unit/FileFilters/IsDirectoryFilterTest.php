<?php
declare(strict_types=1);

namespace Tests\Unit\FileFilters;

use Donatorsky\VendorCompress\Contracts\FileFilterInterface;
use Donatorsky\VendorCompress\FileFilters\IsDirectoryFilter;
use PHPUnit\Framework\TestCase;
use SplFileInfo;
use Tests\Extensions\Traits\FakerTrait;
use Tests\Tests\Extensions\Traits\SplFileInfoAssertsTrait;

/**
 * @coversDefaultClass \Donatorsky\VendorCompress\FileFilters\IsDirectoryFilter
 */
class IsDirectoryFilterTest extends TestCase {
	use FakerTrait, SplFileInfoAssertsTrait;

	/**
	 * @var \Prophecy\Prophecy\ObjectProphecy|\SplFileInfo
	 */
	private $splFileInfoProphecy;

	/**
	 * @var \Donatorsky\VendorCompress\Contracts\FileFilterInterface|\Prophecy\Prophecy\ObjectProphecy
	 */
	private $fileFilterProphecy;

	/**
	 * @var \SplFileInfo
	 */
	private $splFileInfoMock;

	/**
	 * @var \Donatorsky\VendorCompress\Contracts\FileFilterInterface
	 */
	private $fileFilterMock;

	/**
	 * @var \Donatorsky\VendorCompress\FileFilters\IsDirectoryFilter
	 */
	private $isDirectoryFilter;

	/**
	 * @inheritdoc
	 */
	protected function setUp(): void {
		$this->splFileInfoProphecy = $this->prophesize(SplFileInfo::class);
		$this->fileFilterProphecy = $this->prophesize(FileFilterInterface::class);

		$this->splFileInfoProphecy->isFile()
			->shouldNotBeCalled();

		$this->splFileInfoMock = $this->splFileInfoProphecy->reveal();
		$this->fileFilterMock = $this->fileFilterProphecy->reveal();

		$this->isDirectoryFilter = new IsDirectoryFilter($this->fileFilterMock);
	}

	public function testDirectoryMatches(): void {
		$this->assertSplFileInfoIsDir($this->splFileInfoProphecy);

		$this->splFileInfoProphecy->isDir()
			->shouldBeCalledTimes(1);

		$this->fileFilterProphecy->matches($this->splFileInfoMock)
			->shouldBeCalledTimes(1)
			->willReturn(true);

		self::assertTrue($this->isDirectoryFilter->matches($this->splFileInfoMock));
	}

	public function testDirectoryDoesNotMatch(): void {
		$this->assertSplFileInfoIsDir($this->splFileInfoProphecy);

		$this->splFileInfoProphecy->isDir()
			->shouldBeCalledTimes(1);

		$this->fileFilterProphecy->matches($this->splFileInfoMock)
			->shouldBeCalledTimes(1)
			->willReturn(false);

		self::assertFalse($this->isDirectoryFilter->matches($this->splFileInfoMock));
	}

	public function testFileDoesNotMatch(): void {
		$this->assertSplFileInfoIsFile($this->splFileInfoProphecy);

		$this->splFileInfoProphecy->isDir()
			->shouldBeCalledTimes(1);

		$this->fileFilterProphecy->matches($this->splFileInfoMock)
			->shouldNotBeCalled();

		self::assertFalse($this->isDirectoryFilter->matches($this->splFileInfoMock));
	}
}
