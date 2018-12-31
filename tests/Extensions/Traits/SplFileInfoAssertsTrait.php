<?php
declare(strict_types=1);

namespace Tests\Tests\Extensions\Traits;

trait SplFileInfoAssertsTrait {
	/**
	 * @param \Prophecy\Prophecy\ObjectProphecy&\SplFileInfo $splFileInfoProphecy
	 */
	private function assertSplFileInfoIsDir($splFileInfoProphecy): void {
		$splFileInfoProphecy->isDir()->willreturn(true);
		$splFileInfoProphecy->isFile()->willreturn(false);
	}

	/**
	 * @param \Prophecy\Prophecy\ObjectProphecy&\SplFileInfo $splFileInfoProphecy
	 */
	private function assertSplFileInfoIsFile($splFileInfoProphecy): void {
		$splFileInfoProphecy->isDir()->willreturn(false);
		$splFileInfoProphecy->isFile()->willreturn(true);
	}
}
