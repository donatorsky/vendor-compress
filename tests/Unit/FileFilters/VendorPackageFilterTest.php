<?php
declare(strict_types=1);

namespace Tests\Unit\FileFilters;

use Donatorsky\VendorCompress\FileFilters\VendorPackageFilter;
use PHPUnit\Framework\TestCase;
use SplFileInfo;
use Tests\Extensions\Traits\FakerTrait;
use Tests\Tests\Extensions\Traits\SplFileInfoAssertsTrait;

class VendorPackageFilterTest extends TestCase {
	use FakerTrait, SplFileInfoAssertsTrait;

	private const DIR = '/var/www';

	/**
	 * @var \Prophecy\Prophecy\ObjectProphecy|\SplFileInfo
	 */
	private $splFileInfoProphecy;

	/**
	 * @var \SplFileInfo
	 */
	private $splFileInfoMock;

	/**
	 * {@inheritdoc}
	 */
	protected function setUp(): void {
		$this->splFileInfoProphecy = $this->prophesize(SplFileInfo::class);

		$this->splFileInfoProphecy->isFile()
			->shouldNotBeCalled();

		$this->splFileInfoMock = $this->splFileInfoProphecy->reveal();
	}

	/**
	 * @return array<array>
	 */
	public function validVendorAndPackageNamesDataProvider(): array {
		return [
			'All random' => [
				'vendorName'       => $this->faker()->unique()->slug(3),
				'packageName'      => $this->faker()->unique()->slug(2),
				'otherPackageName' => $this->faker()->unique()->slug(2),
			],

			'vendor as vendorName' => [
				'vendorName'       => 'vendor',
				'packageName'      => $this->faker()->unique()->slug(2),
				'otherPackageName' => $this->faker()->unique()->slug(2),
			],

			'vendor as packageName' => [
				'vendorName'       => $this->faker()->unique()->slug(3),
				'packageName'      => 'vendor',
				'otherPackageName' => $this->faker()->unique()->slug(2),
			],

			'vendor as both vendorName and packageName' => [
				'vendorName'       => 'vendor',
				'packageName'      => 'vendor',
				'otherPackageName' => $this->faker()->unique()->slug(2),
			],
		];
	}

	/**
	 * @dataProvider validVendorAndPackageNamesDataProvider
	 *
	 * @param string $vendorName
	 */
	public function testVendorNameMatchesWithoutPackageName(string $vendorName): void {
		$vendorPackageFilter = new VendorPackageFilter($vendorName);

		// Path with vendor name only
		$this->splFileInfoProphecy->getPathname()
			->shouldBeCalledTimes(1)
			->willreturn($this->vendorPath($vendorName));

		self::assertTrue($vendorPackageFilter->matches($this->splFileInfoMock));
	}

	/**
	 * @dataProvider validVendorAndPackageNamesDataProvider
	 *
	 * @param string $vendorName
	 * @param string $packageName
	 */
	public function testVendorNameMatchesWithPackageName(string $vendorName, string $packageName): void {
		$vendorPackageFilter = new VendorPackageFilter($vendorName);

		// Path with both vendor and package name
		$this->splFileInfoProphecy->getPathname()
			->shouldBeCalledTimes(1)
			->willreturn($this->packagePath($vendorName, $packageName));

		self::assertTrue($vendorPackageFilter->matches($this->splFileInfoMock));
	}

	/**
	 * @dataProvider validVendorAndPackageNamesDataProvider
	 *
	 * @param string $vendorName
	 * @param string $packageName
	 * @param string $otherPackageName
	 */
	public function testVendorNameAndPackageNameMatches(string $vendorName, string $packageName, string $otherPackageName): void {
		$vendorPackageFilter = new VendorPackageFilter($vendorName, $packageName);

		// Path with vendor name only
		$this->splFileInfoProphecy->getPathname()
			->shouldBeCalledTimes(3)
			->willreturn($this->vendorPath($vendorName));

		self::assertFalse($vendorPackageFilter->matches($this->splFileInfoMock));

		// Path with both vendor and package name
		$this->splFileInfoProphecy->getPathname()
			->willreturn($this->packagePath($vendorName, $packageName));

		self::assertTrue($vendorPackageFilter->matches($this->splFileInfoMock));

		// Path with both vendor and package name
		$this->splFileInfoProphecy->getPathname()
			->willreturn($this->packagePath($vendorName, $otherPackageName));

		self::assertFalse($vendorPackageFilter->matches($this->splFileInfoMock));
	}

	/**
	 * Tests against path without '/vendor/' part.
	 *
	 * @depends      testVendorNameMatchesWithoutPackageName
	 * @depends      testVendorNameMatchesWithPackageName
	 * @depends      testVendorNameAndPackageNameMatches
	 *
	 * @dataProvider validVendorAndPackageNamesDataProvider
	 *
	 * @param string $vendorName
	 */
	public function testVendorNameMatchesButPathIsInvalid(string $vendorName): void {
		$vendorPackageFilter = new VendorPackageFilter($vendorName);

		$this->splFileInfoProphecy->getPathname()
			->shouldBeCalledTimes(1)
			->willreturn(\sprintf(
				'%s\\%s%s',
				self::DIR,
				$vendorName,
				DIRECTORY_SEPARATOR
			));

		self::assertFalse($vendorPackageFilter->matches($this->splFileInfoMock));
	}

	/**
	 * Tests against path without '/vendor/' part.
	 *
	 * @depends      testVendorNameMatchesWithoutPackageName
	 * @depends      testVendorNameMatchesWithPackageName
	 * @depends      testVendorNameAndPackageNameMatches
	 *
	 * @dataProvider validVendorAndPackageNamesDataProvider
	 *
	 * @param string $vendorName
	 * @param string $packageName
	 */
	public function testVendorNameAndPackageNameMatchesButPathIsInvalid(string $vendorName, string $packageName): void {
		$vendorPackageFilter = new VendorPackageFilter($vendorName, $packageName);

		$this->splFileInfoProphecy->getPathname()
			->shouldBeCalledTimes(1)
			->willreturn(\sprintf(
				'%s\\%s/%s%s',
				self::DIR,
				$vendorName,
				$packageName,
				DIRECTORY_SEPARATOR
			));

		self::assertFalse($vendorPackageFilter->matches($this->splFileInfoMock));
	}

	/**
	 * @param string $vendorName
	 *
	 * @return string
	 */
	private function vendorPath(string $vendorName): string {
		return \sprintf(
			'%s\\vendor/%s%s',
			self::DIR,
			$vendorName,
			DIRECTORY_SEPARATOR
		);
	}

	/**
	 * @param string $vendorName
	 * @param string $packageName
	 *
	 * @return string
	 */
	private function packagePath(string $vendorName, string $packageName): string {
		return \sprintf(
			'%s%s%s',
			$this->vendorPath($vendorName),
			$packageName,
			DIRECTORY_SEPARATOR
		);
	}
}
