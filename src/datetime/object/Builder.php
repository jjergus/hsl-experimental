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
final class Builder<T as Base> {
  use _Private\HasParts<T>;

  public function __construct(
    private (function (int, int, int, int, int, int, int): T) $build,
    int $year,
    int $month,
    int $day,
    int $hours,
    int $minutes,
    int $seconds,
    int $nanoseconds,
  ) {
    $this->year = $year;
    $this->month = $month;
    $this->day = $day;
    $this->hours = $hours;
    $this->minutes = $minutes;
    $this->seconds = $seconds;
    $this->nanoseconds = $nanoseconds;
  }

  public function isValid(): bool {
    try {
      $this->getOrThrow();
      return true;
    } catch (\Exception $_) {
      return false;
    }
  }

  /**
   * Returns a DateTime\Zoned or Unzoned object if the current combination of
   * date/time parts is valid. Otherwise, throws a DateTime\Exception.
   */
  public function getOrThrow(): T {
    $build = $this->build;
    return $build(
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
   * 2. If the time is invalid because it falls into a gap created by a DST
   *    change (e.g. 2:30am on a specific day), it is adjusted to the first
   *    valid time after the gap (e.g. 3:00am on the same day).
   *
   * TODO: Figure out what to do if trying to return a DateTime\Zoned object
   * outside of DateTime\Timestamp range. Currently this throws, violating the
   * contract.
   *
   * TODO: Do we want some compromise between getOrThrow() and getClosest()?
   * (i.e., a method that does reasonable adjustments but still throws for
   * completely ridiculous values like negative numbers)
   */
  public function getClosest(): T {
    $year = Math\maxva($this->year, 1);
    $month = self::clamp($this->month, 1, 12);
    $day = self::clamp($this->day, 1, _Private\days_in_month($year, $month));
    $hours = self::clamp($this->hours, 0, 23);
    $minutes = self::clamp($this->minutes, 0, 59);
    $seconds = self::clamp($this->seconds, 0, 59);
    $nanoseconds =
      self::clamp($this->nanoseconds, 0, Time\to_raw_ns(Time\SECOND) - 1);

    $build = $this->build;
    try {
      return $build(
        $year,
        $month,
        $day,
        $hours,
        $minutes,
        $seconds,
        $nanoseconds,
      );
    } catch (\Exception $_) {
      // During DST changes clock moves forward by 1 hour, so one specific
      // $hours value is invalid.
      return $build($year, $month, $day, $hours + 1, 0, 0, 0);
    }
  }

  private static function clamp(int $value, int $min, int $max): int {
    return $value < $min ? $min : ($value > $max ? $max : $value);
  }

  <<__Override>>
  protected function withParts(
    int $year,
    int $month,
    int $day,
    int $hours,
    int $minutes,
    int $seconds,
    int $nanoseconds,
  ): this {
    return new self(
      $this->build,
      $year,
      $month,
      $day,
      $hours,
      $minutes,
      $seconds,
      $nanoseconds,
    );
  }
}
