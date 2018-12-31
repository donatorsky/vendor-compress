<?php
declare(strict_types=1);

namespace Tests\Extensions\Traits;

use Faker\Factory;
use Faker\Generator;

trait FakerTrait {
	/**
	 * @var \Faker\Generator
	 */
	private $faker;

	/**
	 * @return \Faker\Generator
	 */
	protected function faker(): Generator {
		if (null === $this->faker) {
			$this->faker = Factory::create();
		}

		return $this->faker;
	}
}
