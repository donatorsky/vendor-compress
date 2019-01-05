<?php
declare(strict_types=1);

namespace Tests\Unit\FileFilters;

use Donatorsky\VendorCompress\Contracts\FileFilterInterface;
use Donatorsky\VendorCompress\FileFilters\IsFileFilter;
use PHPUnit\Framework\TestCase;
use SplFileInfo;
use Tests\Extensions\Traits\FakerTrait;
use Tests\Tests\Extensions\Traits\SplFileInfoAssertsTrait;

/**
 * @coversDefaultClass \Donatorsky\VendorCompress\FileFilters\IsFileFilter
 */
class IsFileFilterTest extends TestCase {
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
	 * @var \Donatorsky\VendorCompress\FileFilters\IsFileFilter
	 */
	private $isFileFilter;

	/**
	 * @inheritdoc
	 */
	protected function setUp(): void {
		$this->splFileInfoProphecy = $this->prophesize(SplFileInfo::class);
		$this->fileFilterProphecy = $this->prophesize(FileFilterInterface::class);

		$this->splFileInfoProphecy->isDir()
			->shouldNotBeCalled();

		$this->splFileInfoMock = $this->splFileInfoProphecy->reveal();
		$this->fileFilterMock = $this->fileFilterProphecy->reveal();

		$this->isFileFilter = new IsFileFilter($this->fileFilterMock);
	}

	public function testFileMatches(): void {
		$this->assertSplFileInfoIsFile($this->splFileInfoProphecy);

		$this->splFileInfoProphecy->isFile()
			->shouldBeCalledTimes(1);

		$this->fileFilterProphecy->matches($this->splFileInfoMock)
			->shouldBeCalledTimes(1)
			->willReturn(true);

		self::assertTrue($this->isFileFilter->matches($this->splFileInfoMock));
	}

	public function testFileDoesNotMatch(): void {
		$this->assertSplFileInfoIsFile($this->splFileInfoProphecy);

		$this->splFileInfoProphecy->isFile()
			->shouldBeCalledTimes(1);

		$this->fileFilterProphecy->matches($this->splFileInfoMock)
			->shouldBeCalledTimes(1)
			->willReturn(false);

		self::assertFalse($this->isFileFilter->matches($this->splFileInfoMock));
	}

	public function testDirectoryDoesNotMatch(): void {
		$this->assertSplFileInfoIsDir($this->splFileInfoProphecy);

		$this->splFileInfoProphecy->isFile()
			->shouldBeCalledTimes(1);

		$this->fileFilterProphecy->matches($this->splFileInfoMock)
			->shouldNotBeCalled();

		self::assertFalse($this->isFileFilter->matches($this->splFileInfoMock));
	}
}
