<?php
declare(strict_types=1);

namespace Tests\Unit\FileFilters;

use Donatorsky\VendorCompress\FileFilters\BasenameFilter;
use PHPUnit\Framework\TestCase;
use SplFileInfo;
use Tests\Extensions\Traits\FakerTrait;
use Tests\Tests\Extensions\Traits\SplFileInfoAssertsTrait;

/**
 * @coversDefaultClass \Donatorsky\VendorCompress\FileFilters\BasenameFilter
 */
class BasenameFilterTest extends TestCase {
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
	 * {@inheritdoc}
	 */
	protected function setUp(): void {
		$this->splFileInfoProphecy = $this->prophesize(SplFileInfo::class);

		$this->splFileInfoMock = $this->splFileInfoProphecy->reveal();
	}

	public function testMatchNameCaseInsensitive(): void {
		$name = $this->fakeFileName();
		$invertCaseName = $this->invertCase($name);

		$basenameFilter = new BasenameFilter($name, false);

		// Exact name
		$this->splFileInfoProphecy->getBasename()
			->shouldBeCalledTimes(8)
			->willreturn($name);

		$this->assertBasenameMatches($basenameFilter);

		// Same name, but case-inverted letters
		$this->splFileInfoProphecy->getBasename()
			->willreturn($invertCaseName);

		$this->assertBasenameMatches($basenameFilter);

		// Different name
		$this->splFileInfoProphecy->getBasename()
			->willreturn($this->fakeFileName());

		$this->assertBasenameDoesNotMatch($basenameFilter);

		// Middle of name
		$this->splFileInfoProphecy->getBasename()
			->willreturn(\substr($name, (int) (($len = \strlen($name)) / 4), (int) ($len / 2)));

		$this->assertBasenameDoesNotMatch($basenameFilter);
	}

	public function testMatchNameCaseSensitive(): void {
		$name = $this->fakeFileName();
		$invertCaseName = $this->invertCase($name);

		$basenameFilter = new BasenameFilter($name, true);

		// Exact name
		$this->splFileInfoProphecy->getBasename()
			->shouldBeCalledTimes(8)
			->willreturn($name);

		$this->assertBasenameMatches($basenameFilter);

		// Same name, but case-inverted letters
		$this->splFileInfoProphecy->getBasename()
			->willreturn($invertCaseName);

		$this->assertBasenameDoesNotMatch($basenameFilter);

		// Different name
		$this->splFileInfoProphecy->getBasename()
			->willreturn($this->fakeFileName());

		$this->assertBasenameDoesNotMatch($basenameFilter);

		// Middle of name
		$this->splFileInfoProphecy->getBasename()
			->willreturn(\substr($name, (int) (($len = \strlen($name)) / 4), (int) ($len / 2)));

		$this->assertBasenameDoesNotMatch($basenameFilter);
	}

	/**
	 * @param \Donatorsky\VendorCompress\FileFilters\BasenameFilter $basenameFilter
	 */
	private function assertBasenameMatches(BasenameFilter $basenameFilter): void {
		$this->assertSplFileInfoIsDir($this->splFileInfoProphecy);
		self::assertTrue($basenameFilter->matches($this->splFileInfoMock));

		$this->assertSplFileInfoIsFile($this->splFileInfoProphecy);
		self::assertTrue($basenameFilter->matches($this->splFileInfoMock));
	}

	/**
	 * @param \Donatorsky\VendorCompress\FileFilters\BasenameFilter $basenameFilter
	 */
	private function assertBasenameDoesNotMatch(BasenameFilter $basenameFilter): void {
		$this->assertSplFileInfoIsDir($this->splFileInfoProphecy);
		self::assertFalse($basenameFilter->matches($this->splFileInfoMock));

		$this->assertSplFileInfoIsFile($this->splFileInfoProphecy);
		self::assertFalse($basenameFilter->matches($this->splFileInfoMock));
	}

	/**
	 * @return string
	 */
	private function fakeFileName(): string {
		return \sprintf('%s.%s', $this->faker()->unique()->slug, $this->faker()->unique()->fileExtension);
	}

	/**
	 * @param string $string
	 *
	 * @return string
	 */
	private function invertCase(string $string): string {
		return (string) \preg_replace_callback('/(?\'l\'[[:lower:]]+)|(?\'U\'[[:upper:]]+)/', function (array $match): string {
			return \sprintf('%s%s', \strtoupper($match['l']), \strtolower($match['U'] ?? ''));
		}, $string);
	}
}
