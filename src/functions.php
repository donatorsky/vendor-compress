<?php
declare(strict_types=1);

/**
 * @param resource $handle
 * @param string   $format
 * @param mixed    ...$args
 */
function write($handle, string $format, ...$args): void {
	\fwrite($handle, \sprintf($format, ...$args));
}

/**
 * @param resource $handle
 * @param string   $format
 * @param mixed    ...$args
 */
function writeln($handle, string $format, ...$args): void {
	\fwrite($handle, \sprintf($format, ...$args));
	\fwrite($handle, PHP_EOL);
}

/**
 * @param string $format
 * @param mixed  ...$args
 */
function stdout(string $format, ...$args): void {
	write(STDOUT, $format, ...$args);
}

/**
 * @param string $format
 * @param mixed  ...$args
 */
function stdoutln(string $format, ...$args): void {
	writeln(STDOUT, $format, ...$args);
}

/**
 * @param string $format
 * @param mixed  ...$args
 *
 * @see \printf()
 */
function stderrln(string $format, ...$args): void {
	writeln(STDERR, $format, ...$args);
}
