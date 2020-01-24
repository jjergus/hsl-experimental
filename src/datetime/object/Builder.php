<?hh
/*
 *  Copyright (c) 2004-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace HH\Lib\DateTime;
use namespace HH\Lib\{Math, Time};

/**
 * An intermediate state representing a possibly-invalid DateTime object. Can be
 * transformed to a valid DateTime object by calling getOrThrow() or
 * getClosest().
 *
 * Instances of this are returned by all DateTime methods that aren't guaranteed
 * to produce a valid result, e.g. fromParts(), withDay(), nextMonth(), but not
 * e.g. fromTimestamp() or plusHours().
 */
<<__ConsistentConstruct>>
abstract class Builder<T as Base> extends _Private\HasParts<T> {

  <<__Override>>
  final protected static function builderFromParts(
    Zone $timezone,
    int $year,
    int $month,
    int $day,
    int $hours,
    int $minutes,
    int $seconds,
    int $nanoseconds,
  ): Builder<T> {
    return new static(
      $timezone,
      $year,
      $month,
      $day,
      $hours,
      $minutes,
      $seconds,
      $nanoseconds,
    );
  }

  final public function isValid(): bool {
    try {
      $this->exactX();
      return true;
    } catch (\Exception $_) {
      return false;
    }
  }

  /**
   * Returns a DateTime\Zoned or Unzoned object if the current combination of
   * date/time parts is valid. Otherwise, throws a DateTime\Exception.
   */
  final public function exactX(): T {
    return static::instanceFromPartsX(
      $this->timezone,
      $this->year,
      $this->month,
      $this->day,
      $this->hours,
      $this->minutes,
      $this->seconds,
      $this->nanoseconds,
    );
  }

  /**
   * Does any necessary adjustments for the current combination of date/time
   * parts to be valid and returns the resulting DateTime\Zoned or Unzoned
   * object. Possible adjustments are:
   *
   * 1. Any part that is out of its valid range is adjusted to the minimum or
   *    maximum allowed value. If both month and day need to be adjusted, the
   *    month is adjusted first, then the day is adjusted based on the valid
   *    range for the adjusted month.
   * 2. If the time is ambiguous or invalid due to a DST transition, the
   *    returned time will be exactly 24 hours after the same time on the
   *    previous day (e.g. 2:30am is adjusted to 3:30am if it falls into a gap
   *    created by the DST transition).
   *
   * TODO: Figure out what to do if trying to return a DateTime\Zoned object
   * outside of DateTime\Timestamp range. Currently this throws, violating the
   * contract.
   *
   * TODO: Do we want some compromise between exactX() and closest()?
   * (i.e., a method that does reasonable adjustments but still throws for
   * completely ridiculous values like negative numbers)
   */
  final public function closest(): T {
    $month = self::clamp($this->month, 1, 12);
    $day =
      self::clamp($this->day, 1, _Private\days_in_month($this->year, $month));
    $hours = self::clamp($this->hours, 0, 23);
    $minutes = self::clamp($this->minutes, 0, 59);
    $seconds = self::clamp($this->seconds, 0, 59);
    $nanoseconds = self::clamp($this->nanoseconds, 0, 999999999);

    try {
      return static::instanceFromPartsX(
        $this->timezone,
        $this->year,
        $month,
        $day,
        $hours,
        $minutes,
        $seconds,
        $nanoseconds,
      );
    } catch (Time\Exception $_) {
      // During DST changes clock moves forward by 1 hour, so one specific
      // $hours value is invalid.
      return static::instanceFromPartsX(
        $this->timezone,
        $this->year,
        $month,
        $day,
        $hours + 1,
        $minutes,
        $seconds,
        $nanoseconds,
      );
    }
  }

  private static function clamp(int $value, int $min, int $max): int {
    return $value < $min ? $min : ($value > $max ? $max : $value);
  }
}
