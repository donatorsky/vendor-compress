<?php
declare(strict_types=1);

namespace Tests\Unit;

use Donatorsky\VendorCompress\Config;
use Donatorsky\VendorCompress\Contracts\FileFilterInterface;
use Donatorsky\VendorCompress\Contracts\FileProcessorInterface;
use InvalidArgumentException;
use Phar;
use PHPUnit\Framework\TestCase;
use Tests\Extensions\Traits\FakerTrait;

/**
 * @coversDefaultClass \Donatorsky\VendorCompress\Config
 */
class ConfigTest extends TestCase {
	use FakerTrait;

	/**
	 * @var \Donatorsky\VendorCompress\Config
	 */
	private $config;

	/**
	 * @inheritdoc
	 */
	protected function setUp(): void {
		$this->config = new Config();
	}

	public function testCreate(): void {
		$config = Config::create();

		self::assertEquals($this->config, $config);
		self::assertNotSame($this->config, $config);
	}

	public function testAlias(): void {
		$alias = $this->faker()->slug;

		$this->config->setAlias($alias);

		self::assertSame($alias, $this->config->getAlias());
	}

	public function testFilesCompressionMethod(): void {
		$filesCompressionMethod = $this->faker()->randomElement([Phar::GZ, Phar::BZ2]);

		self::assertSame(Phar::NONE, $this->config->getFilesCompressionMethod());

		$this->config->setFilesCompressionMethod($filesCompressionMethod);

		self::assertSame($filesCompressionMethod, $this->config->getFilesCompressionMethod());
	}

	public function testArchiveCompressionMethod(): void {
		$archiveCompressionMethod = $this->faker()->randomElement([Phar::GZ, Phar::BZ2]);

		self::assertSame(Phar::NONE, $this->config->getArchiveCompressionMethod());

		$this->config->setArchiveCompressionMethod($archiveCompressionMethod);

		self::assertSame($archiveCompressionMethod, $this->config->getArchiveCompressionMethod());
	}

	public function testExcluded(): void {
		self::assertSame([], $this->config->getExcluded());

		$fileFilterDummy = $this->createFileFilterDummy();
		$this->config->addExcluded($fileFilterDummy);

		self::assertSame([$fileFilterDummy], $this->config->getExcluded());

		$fileFilterDummiesCollection = $this->createFileFilterDummiesCollection(3);
		$this->config->setExcluded($fileFilterDummiesCollection);

		self::assertNotContains($fileFilterDummy, $fileFilterDummiesCollection);
		self::assertSame($fileFilterDummiesCollection, $this->config->getExcluded());
	}

	public function testIncluded(): void {
		self::assertSame([], $this->config->getIncluded());

		$fileFilterDummy = $this->createFileFilterDummy();
		$this->config->addIncluded($fileFilterDummy);

		self::assertSame([$fileFilterDummy], $this->config->getIncluded());

		$fileFilterDummiesCollection = $this->createFileFilterDummiesCollection(3);
		$this->config->setIncluded($fileFilterDummiesCollection);

		self::assertNotContains($fileFilterDummy, $fileFilterDummiesCollection);
		self::assertSame($fileFilterDummiesCollection, $this->config->getIncluded());
	}

	public function testFileProcessor(): void {
		self::assertSame([], $this->config->getFileProcessors());

		$fileProcessorDummy = $this->createFileProcessorDummy();
		$fileFilterDummy = $this->createFileFilterDummy();

		$this->config->addFileProcessor($fileProcessorDummy, $fileFilterDummy);

		self::assertSame([
			[
				'fileProcessor' => $fileProcessorDummy,
				'fileFilter'    => $fileFilterDummy,
			],
		], $this->config->getFileProcessors());

		$this->config->addFileProcessor($this->createFileProcessorDummy(), ...$this->createFileFilterDummiesCollection(3));

		self::assertCount(4, $this->config->getFileProcessors());
	}

	public function testDebug(): void {
		self::assertFalse($this->config->isDebug());

		$this->config->setDebug(true);
		self::assertTrue($this->config->isDebug());
	}

	/**
	 * @return \Donatorsky\VendorCompress\Contracts\FileFilterInterface
	 */
	private function createFileFilterDummy(): FileFilterInterface {
		/**
		 * @var \Donatorsky\VendorCompress\Contracts\FileFilterInterface&\Prophecy\Prophecy\ObjectProphecy $fileFilterProphecy
		 */
		$fileFilterProphecy = $this->prophesize(FileFilterInterface::class);

		return $fileFilterProphecy->reveal();
	}

	/**
	 * @param int $amount
	 *
	 * @throws \InvalidArgumentException
	 *
	 * @return array<\Donatorsky\VendorCompress\Contracts\FileFilterInterface>
	 */
	private function createFileFilterDummiesCollection(int $amount): array {
		if ($amount <= 0) {
			throw new InvalidArgumentException(\sprintf('Expected a value to be greater than 0, got: %d', $amount));
		}

		$dummies = [];

		do {
			$dummies[] = $this->createFileFilterDummy();

			--$amount;
		} while ($amount > 0);

		return $dummies;
	}

	/**
	 * @return \Donatorsky\VendorCompress\Contracts\FileProcessorInterface
	 */
	private function createFileProcessorDummy(): FileProcessorInterface {
		/**
		 * @var \Donatorsky\VendorCompress\Contracts\FileProcessorInterface&\Prophecy\Prophecy\ObjectProphecy $fileFilterProphecy
		 */
		$fileFilterProphecy = $this->prophesize(FileProcessorInterface::class);

		return $fileFilterProphecy->reveal();
	}
}
