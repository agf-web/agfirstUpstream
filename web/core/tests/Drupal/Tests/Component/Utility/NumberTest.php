<?php

namespace Drupal\Tests\Component\Utility;

use Drupal\Component\Utility\Number;
use PHPUnit\Framework\TestCase;

/**
 * Tests number manipulation utilities.
 *
 * @group Utility
 *
 * @coversDefaultClass \Drupal\Component\Utility\Number
 *
 * @see \Drupal\Component\Utility\Number
 */
class NumberTest extends TestCase {

  /**
   * Tests Number::validStep() without offset.
   *
   * @dataProvider providerTestValidStep
   * @covers ::validStep
   *
   * @param int|float|string $value
   *   The value argument for Number::validStep().
   * @param int|float|string $step
   *   The step argument for Number::validStep().
   * @param bool $expected
   *   Expected return value from Number::validStep().
   */
  public function testValidStep($value, $step, $expected) {
    $return = Number::validStep($value, $step);
    $this->assertEquals($expected, $return);
  }

  /**
   * @covers \Drupal\Component\Utility\Number::normalize
   *
   * @param int|float|string $number
   *   The number to test the count on.
   * @param int $expected
   *   The expected number of significant decimals.
   *
   * @dataProvider providerNormalize
   */
  public function testNormalize($number, $expected) {
    $this->assertEquals($expected, Number::normalize($number));
  }

  /**
   * Provides data to self::testNormalize().
   */
  public function providerNormalize() {
    return [
      ['', ''],
      [0, 0],
      [123456, 123456],
      [-123456, -123456],
      [0.0, 0.0],
      [0.12300000000000000001, 0.123],
      [1 / 3, 0.33333333333333],
      [10.00000000000000000999, 10],
      [1234.1234567800000000000000001, 1234.12345678],
      ['1234.1234567800000000000000001', '1234.1234567800000000000000001'],
    ];
  }

  /**
   * @covers ::countSignificantDecimals
   *
   * @dataProvider provideCountSignificantDecimals
   *
   * @param int $expected
   *   The expected number of significant decimals.
   * @param int|float|string $number
   *   The number to test the count on.
   */
  public function testCountSignificantDecimals($expected, $number) {
    $this->assertEquals($expected, Number::countSignificantDecimals($number));
  }

  /**
   * Provides data to self::testCountSignificantDecimals().
   */
  public function provideCountSignificantDecimals() {
    return [
      [0, 0],
      [0, '0'],
      [0, 9],
      [0, '9'],
      [0, -9],
      [0, '-9'],
      [0, 999999999],
      [0, '999999999'],
      [0, -999999999],
      [0, '-999999999'],
      [0, 0.0],
      [1, '0.0'],
      [0, -0.0],
      [1, '-0.0'],
      // The maximum supported number of significant float decimals is 15.
      [0, 0.0000000000000],
      [0, -0.0000000000000],
      [9, -0.0000000090000],
      [9, -0.0000000090000],
      [9, -0.00000000900000],
      [9, -0.000000009000000000],
      [15, -0.0000000090000009],
      [15, -0.00000000900000099],
      [15, -0.000000009000000900009],
      // Numeric strings do not suffer from the system-specific limitations to
      // float precision, so they can contain many more significant decimals.
      // This is especially useful when working with solutions such as BCMath
      // (https://secure.php.net/manual/en/book.bc.php)
      [15, '0.000000000000000'],
      [15, '-0.000000000000000'],
      [15, '-0.000000009000000'],
      [16, '-0.0000000090000000'],
      [20, '-0.00000000900000000000'],
    ];
  }

  /**
   * Tests Number::validStep() with offset.
   *
   * @dataProvider providerTestValidStepOffset
   * @covers ::validStep
   *
   * @param int|float|string $value
   *   The value argument for Number::validStep().
   * @param int|float|string $step
   *   The step argument for Number::validStep().
   * @param float $offset
   *   The offset argument for Number::validStep().
   * @param bool $expected
   *   Expected return value from Number::validStep().
   */
  public function testValidStepOffset($value, $step, $offset, $expected) {
    $return = Number::validStep($value, $step, $offset);
    $this->assertEquals($expected, $return);
  }

  /**
   * Provides data for self::testNumberStep().
   *
   * @see \Drupal\Tests\Component\Utility\Number::testValidStep
   */
  public static function providerTestValidStep() {
    return [
      // Value and step equal.
      [10.3, 10.3, TRUE],

      // Valid integer steps.
      [42, 21, TRUE],
      [42, 3, TRUE],

      // Valid float steps.
      [42, 10.5, TRUE],
      [1, 1 / 3, TRUE],
      [-100, 100 / 7, TRUE],
      [1000, -10, TRUE],

      // Valid and very small float steps.
      [1000.12345, 1e-10, TRUE],
      [3.9999999999999, 1e-13, TRUE],

      // Invalid integer steps.
      [100, 30, FALSE],
      [-10, 4, FALSE],

      // Invalid float steps.
      [6, 5 / 7, FALSE],
      [10.3, 10.25, FALSE],

      // Step mismatches very close to being valid.
      [70 + 9e-7, 10 + 9e-7, FALSE],
      [1936.5, 3e-8, FALSE],
    ];
  }

  /**
   * Data provider for \Drupal\Tests\Component\Utility\NumberTest::testValidStepOffset().
   *
   * @see \Drupal\Tests\Component\Utility\NumberTest::testValidStepOffset()
   */
  public static function providerTestValidStepOffset() {
    return [
      // Try obvious fits.
      [11.3, 10.3, 1, TRUE],
      [100, 10, 50, TRUE],
      [-100, 90 / 7, -10, TRUE],
      [2 / 7 + 5 / 9, 1 / 7, 5 / 9, TRUE],

      // Ensure a small offset is still invalid.
      [10.3, 10.3, 0.0001, FALSE],
      [1 / 5, 1 / 7, 1 / 11, FALSE],

      // Try negative values and offsets.
      [1000, 10, -5, FALSE],
      [-10, 4, 0, FALSE],
      [-10, 4, -4, FALSE],
    ];
  }

  /**
   * Tests the alphadecimal conversion functions.
   *
   * @dataProvider providerTestConversions
   * @covers ::intToAlphadecimal
   * @covers ::alphadecimalToInt
   *
   * @param int $value
   *   The integer value.
   * @param string $expected
   *   The expected alphadecimal value.
   */
  public function testConversions($value, $expected) {
    $this->assertSame(Number::intToAlphadecimal($value), $expected);
    $this->assertSame($value, Number::alphadecimalToInt($expected));
  }

  /**
   * Data provider for testConversions().
   *
   * @see testConversions()
   *
   * @return array
   *   An array containing:
   *     - The integer value.
   *     - The alphadecimal value.
   */
  public function providerTestConversions() {
    return [
      [0, '00'],
      [1, '01'],
      [10, '0a'],
      [20, '0k'],
      [35, '0z'],
      [36, '110'],
      [100, '12s'],
    ];
  }

}
