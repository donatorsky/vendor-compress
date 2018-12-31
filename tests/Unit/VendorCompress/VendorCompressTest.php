<?php
declare(strict_types=1);

namespace Tests\Unit\VendorCompress;

use Donatorsky\VendorCompress\Config;
use Donatorsky\VendorCompress\Contracts\FileProcessorInterface;
use Donatorsky\VendorCompress\FileFilters\ExtensionFilter;
use Donatorsky\VendorCompress\FileFilters\VendorPackageFilter;
use Donatorsky\VendorCompress\VendorCompress;
use Phar;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Donatorsky\VendorCompress\VendorCompress
 */
class VendorCompressTest extends TestCase {
	private const VENDOR_DIRECTORY_PATH = __DIR__ . DIRECTORY_SEPARATOR . 'vendor';

	/**
	 * @var \Donatorsky\VendorCompress\Config|\Prophecy\Prophecy\ObjectProphecy
	 */
	private $configProphecy;

	/**
	 * @var \Donatorsky\VendorCompress\Config
	 */
	private $configMock;

	/**
	 * @inheritdoc
	 */
	protected function setUp(): void {
		$this->configProphecy = $this->prophesize(Config::class);

		$this->configProphecy->isDebug()
			->willReturn(false);

		$this->configProphecy->getAlias()
			->willReturn('vendor.phar');

		$this->configProphecy->getExcluded()
			->willReturn([]);

		$this->configProphecy->getIncluded()
			->willReturn([]);

		$this->configProphecy->getFileProcessors()
			->willReturn([]);

		$this->configProphecy->getFilesCompressionMethod()
			->willReturn(Phar::NONE);

		$this->configProphecy->getArchiveCompressionMethod()
			->willReturn(Phar::NONE);

		$this->configMock = $this->configProphecy->reveal();
	}

	public function testPassInvalidVendorDirectoryPath(): void {
		$vendorDirectoryPath = __DIR__ . DIRECTORY_SEPARATOR . 'i-do-not-exist';

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('The vendor path does not exist or is inaccessible.');

		new VendorCompress(
			$vendorDirectoryPath,
			'vendor.phar',
			$this->configMock
		);
	}

	public function testPassPharNameWithoutPharExtension(): void {
		$this->expectException(\UnexpectedValueException::class);
		$this->expectExceptionMessageRegExp('/^Cannot create phar \'[^\']+vendor\.zip\', file extension \(or combination\) not recognised or the directory does not exist$/');

		$vC = new VendorCompress(
			self::VENDOR_DIRECTORY_PATH,
			'vendor.zip',
			$this->configMock
		);

		$this->configProphecy->getAlias()
			->shouldBeCalledTimes(1)
			->willReturn('vendor.phar');

		$vC->create();
	}

	public function testPassNonExistentPharPath(): void {
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('The path for PHAR file does not exist or is inaccessible.');

		new VendorCompress(
			self::VENDOR_DIRECTORY_PATH,
			__DIR__ . DIRECTORY_SEPARATOR . 'i/do/not/exist/vendor.phar',
			$this->configMock
		);
	}

	public function testSuccessfullyCreateNewInstance(): void {
		$vendorCompress = new VendorCompress(
			self::VENDOR_DIRECTORY_PATH,
			'vendor.phar',
			$this->configMock
		);

		self::assertSame($this->configMock, $vendorCompress->getConfig());
		self::assertEquals(self::VENDOR_DIRECTORY_PATH, $vendorCompress->getVendorDirectoryPath());
		self::assertEquals(__DIR__ . DIRECTORY_SEPARATOR . 'vendor.phar', $vendorCompress->getVendorPharPath());
	}

	/**
	 * @depends testSuccessfullyCreateNewInstance
	 */
	public function testCreatePharFileWithDefaultConfiguration(): void {
		$vendorCompress = new VendorCompress(
			self::VENDOR_DIRECTORY_PATH,
			$this->getTemporaryVendorPharPath(),
			$this->configMock
		);

		$this->assertConfigurationWasRead();

		$phar = $vendorCompress->create();

		self::assertEquals(16, $phar->count());
		self::assertFileExists($vendorCompress->getVendorPharPath());
	}

	/**
	 * @depends testSuccessfullyCreateNewInstance
	 */
	public function testCreatePharFileWithExclusionsAndInclusions(): void {
		$this->assertConfigurationWasRead();

		$this->configProphecy->getExcluded()
			->willReturn([
				new ExtensionFilter('xml'),
				new ExtensionFilter('php'),
			]);

		$this->configProphecy->getIncluded()
			->willReturn([
				new VendorPackageFilter('vendor-name-1', 'package-1-1'),
			]);

		$vendorCompress = new VendorCompress(
			self::VENDOR_DIRECTORY_PATH,
			$this->getTemporaryVendorPharPath(),
			$this->configMock
		);

		$phar = $vendorCompress->create();

		self::assertEquals(7, $phar->count());
		self::assertFileExists($vendorCompress->getVendorPharPath());
	}

	/**
	 * @depends testSuccessfullyCreateNewInstance
	 */
	public function testCreatePharFileWithFilesProcessors(): void {
		$this->assertConfigurationWasRead();

		/**
		 * @var \Prophecy\Prophecy\ObjectProphecy&\Donatorsky\VendorCompress\Contracts\FileProcessorInterface $fileProcessorProphecy
		 */
		$fileProcessorProphecy = $this->prophesize(FileProcessorInterface::class);

		$fileProcessorProphecy->process(Argument::type('string'))
			->shouldBeCalledTimes(3)// 3 "*.json" files
			->willReturn('');

		$this->configProphecy->getFileProcessors()
			->willReturn([
				[
					'fileProcessor' => $fileProcessorProphecy->reveal(),
					'fileFilter'    => new ExtensionFilter('json'),
				],
			]);

		$vendorCompress = new VendorCompress(
			self::VENDOR_DIRECTORY_PATH,
			$this->getTemporaryVendorPharPath(),
			$this->configMock
		);

		$phar = $vendorCompress->create();

		self::assertEquals(16, $phar->count());
		self::assertFileExists($vendorCompress->getVendorPharPath());
	}

	/**
	 * @depends testSuccessfullyCreateNewInstance
	 */
	public function testCreatePharFileWithFilesCompression(): void {
		$compressionMethod = $this->getSupportedCompressionMethod();

		if (Phar::NONE === $compressionMethod) {
			$this->markTestSkipped('No supported compression methods found.');
		}

		$this->assertConfigurationWasRead();

		$this->configProphecy->getFilesCompressionMethod()
			->shouldBeCalledTimes(2)
			->willReturn($compressionMethod);

		$vendorCompress = new VendorCompress(
			self::VENDOR_DIRECTORY_PATH,
			$this->getTemporaryVendorPharPath(),
			$this->configMock
		);

		$phar = $vendorCompress->create();

		self::assertEquals(16, $phar->count());
		self::assertFileExists($vendorCompress->getVendorPharPath());
	}

	/**
	 * @depends testSuccessfullyCreateNewInstance
	 */
	public function testCreatePharFileWithArchiveCompression(): void {
		$compressionMethod = $this->getSupportedCompressionMethod();

		if (Phar::NONE === $compressionMethod) {
			$this->markTestSkipped('No supported compression methods found.');
		}

		$this->assertConfigurationWasRead();

		$this->configProphecy->getArchiveCompressionMethod()
			->shouldBeCalledTimes(2)
			->willReturn($compressionMethod);

		$vendorCompress = new VendorCompress(
			self::VENDOR_DIRECTORY_PATH,
			$this->getTemporaryVendorPharPath(),
			$this->configMock
		);

		$extension = $this->getExtensionBasedOnCompressionMethod($compressionMethod);
		$compressedVendorPharPath = \dirname($vendorCompress->getVendorPharPath()) . DIRECTORY_SEPARATOR . 'vendor.phar.' . $extension;

		$phar = $vendorCompress->create();

		self::assertEquals(16, $phar->count());
		self::assertFileExists($vendorCompress->getVendorPharPath());
		self::assertFileExists($compressedVendorPharPath);
	}

	/**
	 * @return string
	 */
	private function getTemporaryVendorPharPath(): string {
		$tmpPath = \sys_get_temp_dir() . DIRECTORY_SEPARATOR . \uniqid('vendor', true) . DIRECTORY_SEPARATOR;

		\mkdir($tmpPath);

		return $tmpPath . 'vendor.phar';
	}

	private function assertConfigurationWasRead(): void {
		$this->configProphecy->isDebug()
			->shouldBeCalled();

		$this->configProphecy->getAlias()
			->shouldBeCalledTimes(1);

		$this->configProphecy->getExcluded()
			->shouldBeCalledTimes(1);

		$this->configProphecy->getIncluded()
			->shouldBeCalledTimes(1);

		$this->configProphecy->getFileProcessors()
			->shouldBeCalledTimes(1);

		$this->configProphecy->getFilesCompressionMethod()
			->shouldBeCalledTimes(1);

		$this->configProphecy->getArchiveCompressionMethod()
			->shouldBeCalledTimes(1);
	}

	/**
	 * @return int
	 */
	private function getSupportedCompressionMethod(): int {
		if (\defined('\\Phar::GZ')) {
			return Phar::GZ;
		}

		if (\defined('\\Phar::BZ2')) {
			return Phar::BZ2;
		}

		return Phar::NONE;
	}

	/**
	 * @param int $compressionMethod
	 *
	 * @return string
	 */
	private function getExtensionBasedOnCompressionMethod(int $compressionMethod): string {
		switch ($compressionMethod) {
			case Phar::GZ:
				return 'gz';
			case Phar::BZ2:
				return 'bz2';
		}

		throw new \RuntimeException(\sprintf('Unknown compression method provided: %d', $compressionMethod));
	}
}
