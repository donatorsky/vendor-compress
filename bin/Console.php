<?php
declare(strict_types=1);

class Console {
	public const VALUE_OPTIONAL = 1;

	public const VALUE_REQUIRED = 2;

	public const VALUE_BOOLEAN = 4;

	/**
	 * @var array<string, array>
	 */
	private $arguments = [];

	/**
	 * @var array<string, array>
	 */
	private $options = [];

	/**
	 * @var array<string, string>
	 */
	private $shortcuts = [];

	/**
	 * @var array<string, string|null>
	 */
	private $argumentsData = [];

	/**
	 * @var array<string, bool|string|null>
	 */
	private $optionsData = [];

	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var string
	 */
	private $description;

	/**
	 * @param string $name
	 * @param string $description
	 */
	public function __construct(string $name, string $description) {
		$this->name = $name;
		$this->description = $description;
	}

	/**
	 * @return string
	 */
	public function __toString(): string {
		$hasOptions = !empty($this->options);
		$hasArguments = !empty($this->arguments);

		$help = [
			\sprintf('Description:%s  %s', PHP_EOL, $this->description),
			\sprintf(
				'Usage:%s  %s%s%s',
				PHP_EOL,
				$this->name,
				$hasOptions ? ' [options]' : '',
				$hasArguments ? \sprintf(' %s', \implode(', ', \array_map(function (array $argument): string {
					return $this->argumentIs($argument['name'], self::VALUE_REQUIRED) ?
						\sprintf('<%s>', $argument['name']) :
						\sprintf('[<%s>]', $argument['name']);
				}, $this->arguments))) : ''
			),
		];

		$longestLine = 0;

		foreach ($this->arguments as ['name' => $name]) {
			$longestLine = \max($longestLine, \strlen($name));
		}

		foreach ($this->options as ['name' => $name]) {
			$longestLine = \max($longestLine, \strlen($name));
		}

		if ($hasArguments) {
			$arguments = [];

			foreach ($this->arguments as $argument) {
				$arguments[] = \rtrim(\sprintf(
					'  %s        %s %s',
					\str_pad($argument['name'], $longestLine),
					$argument['description'],
					(null !== $argument['default']) ? \sprintf('[default: %s]', $argument['default']) : ''
				));
			}

			$help[] = \sprintf('Arguments:%s%s', PHP_EOL, \implode(PHP_EOL, $arguments));
		}

		if ($hasOptions) {
			$options = [];

			foreach ($this->options as $option) {
				$options[] = \rtrim(\sprintf(
					'% 6s--%s  %s %s',
					$option['shortcut'] ? \sprintf('-%s, ', $option['shortcut']) : ' ',
					\str_pad($option['name'], $longestLine),
					$option['description'],
					(null !== $option['default'] && false !== $option['default']) ? \sprintf('[default: %s]', $option['default']) : ''
				));
			}

			$help[] = \sprintf('Options:%s%s', PHP_EOL, \implode(PHP_EOL, $options));
		}

		return \implode(PHP_EOL . PHP_EOL, $help);
	}

	/**
	 * @return string
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getDescription(): string {
		return $this->description;
	}

	/**
	 * @param string      $name
	 * @param int         $mode
	 * @param string      $description
	 * @param string|null $default
	 *
	 * @throws \InvalidArgumentException
	 *
	 * @return $this
	 */
	public function addArgument(string $name, int $mode = self::VALUE_OPTIONAL, string $description = '', ?string $default = null): self {
		$name = \strtolower($name);

		$this->arguments[$name] = \compact('name', 'mode', 'description', 'default');

		$this->argumentsData[$name] = $this->argumentIs($name, self::VALUE_REQUIRED) ? null : $default;

		return $this;
	}

	/**
	 * @param string $name
	 *
	 * @return string|null
	 */
	public function getArgument(string $name): ?string {
		return $this->argumentsData[$name];
	}

	/**
	 * @return array<string, bool|string|null>
	 */
	public function getArguments(): array {
		return $this->argumentsData;
	}

	/**
	 * @param string           $name
	 * @param string|null      $shortcut
	 * @param int              $mode
	 * @param string           $description
	 * @param bool|string|null $default
	 *
	 * @throws \InvalidArgumentException
	 *
	 * @return $this
	 */
	public function addOption(string $name, ?string $shortcut = null, int $mode = self::VALUE_REQUIRED, string $description = '', $default = null): self {
		$name = \strtolower($name);

		$this->options[\sprintf('--%s', $name)] = \compact('name', 'shortcut', 'mode', 'description', 'default');

		if (null !== $shortcut) {
			$this->shortcuts[\sprintf('-%s', $shortcut)] = $name;
		}

		$this->optionsData[$name] = $default;

		return $this;
	}

	/**
	 * @param string $name
	 *
	 * @return bool|string|null
	 */
	public function getOption(string $name) {
		return $this->optionsData[$name];
	}

	/**
	 * @return array<string, bool|string|null>
	 */
	public function getOptions(): array {
		return $this->optionsData;
	}

	public function validateInput(): void {
		$argv = [];

		for ($index = 1; $index < $_SERVER['argc']; ++$index) {
			if (0 === \strpos($_SERVER['argv'][$index], '--')) {
				$pos = \strpos($_SERVER['argv'][$index], '=');

				if (false !== $pos) {
					$argv[] = \strtolower(\substr($_SERVER['argv'][$index], 0, $pos));
					$argv[] = \substr($_SERVER['argv'][$index], $pos + 1);

					continue;
				}

				$_SERVER['argv'][$index] = \strtolower($_SERVER['argv'][$index]);
			} elseif (0 === \strpos($_SERVER['argv'][$index], '-') && isset($_SERVER['argv'][$index]{2})) {
				$argv[] = \substr($_SERVER['argv'][$index], 0, 2);
				$argv[] = \substr($_SERVER['argv'][$index], 2);

				continue;
			}

			$argv[] = $_SERVER['argv'][$index];
		}

		\reset($this->argumentsData);

		for ($index = 0, $argc = \count($argv); $index < $argc; ++$index) {
			$arg = $argv[$index];

			if (isset($this->shortcuts[$arg])) {
				$arg = \sprintf('--%s', $this->shortcuts[$arg]);
			}

			if (isset($this->options[$arg])) {
				if ($this->optionIs($arg, self::VALUE_BOOLEAN)) {
					$this->optionsData[\substr($arg, 2)] = true;
				} else {
					if (($index + 1) >= $argc) {
						throw new RuntimeException(\sprintf('%s option is expected to have value.', $argv[$index]), VENDOR_COMPRESS_ERR_MISSING_REQUIRED_OPTION_VALUE);
					}

					$this->optionsData[\substr($arg, 2)] = $argv[++$index];
				}

				continue;
			}

			if (0 === \strpos($arg, '-')) {
				throw new RuntimeException(\sprintf('Unknown option: %s.', $argv[$index]), VENDOR_COMPRESS_ERR_UNKNOWN_OPTION);
			}

			$argumentKey = \key($this->argumentsData);

			if (null === $argumentKey) {
				throw new RuntimeException(\sprintf('No arguments associated with value: %s', $argv[$index]), VENDOR_COMPRESS_ERR_UNKNOWN_ARGUMENT);
			}

			$this->argumentsData[(string) $argumentKey] = $arg;

			\next($this->argumentsData);
		}

		// Check required arguments
		foreach ($this->argumentsData as $argument => $data) {
			if (null === $data && $this->argumentIs($argument, self::VALUE_REQUIRED)) {
				throw new RuntimeException(\sprintf('%s argument is expected to have value.', $argument), VENDOR_COMPRESS_ERR_MISSING_REQUIRED_ARGUMENT_VALUE);
			}
		}
	}

	/**
	 * @param string $name
	 * @param int    $flag
	 *
	 * @return bool
	 */
	private function argumentIs(string $name, int $flag): bool {
		return $flag === ($this->arguments[$name]['mode'] & $flag);
	}

	/**
	 * @param string $name
	 * @param int    $flag
	 *
	 * @return bool
	 */
	private function optionIs(string $name, int $flag): bool {
		return $flag === ($this->options[$name]['mode'] & $flag);
	}
}
