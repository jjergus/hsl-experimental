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
use namespace HH\Lib\{C, Dict, Str, Time};

abstract class Interval {

  //////////////////////////////////////////////////////////////////////////////
  // constructors

  final public static function since(Zoned $datetime): Interval {
    $now = Zoned::now($datetime->getTimezone());
    if ($datetime->isAfter($now)) {
      throw new Time\Exception('Expected a DateTime\Zoned in the past.');
    }
    return $datetime->difference($now);
  }

  final public static function until(Zoned $datetime): Interval {
    $now = Zoned::now($datetime->getTimezone());
    if ($datetime->isBefore($now)) {
      throw new Time\Exception('Expected a DateTime\Zoned in the future.');
    }
    return $datetime->difference($now);
  }

  final public static function years(int $years, int $months = 0): MonthInterval {
    list($years, $months) = self::normalizeMonths($years, $months);
    return $years === 0 && $months === 0
      ? new _Private\ZeroInterval()
      : new _Private\MonthInterval($years, $months);
  }

  final public static function months(int $months): MonthInterval {
    return self::years(0, $months);
  }

  final public static function days(int $days): DayInterval {
    self::assertNonNegative($days);
    return $days === 0
      ? new _Private\ZeroInterval()
      : new _Private\DayInterval($days);
  }

  final public static function hours(
    int $hours,
    int $minutes = 0,
    int $seconds = 0,
    int $nanoseconds = 0,
  ): TimeInterval {
    list($hours, $minutes, $seconds, $nanoseconds) =
      self::normalizeTime($hours, $minutes, $seconds, $nanoseconds);
    return
      $hours === 0 && $minutes === 0 && $seconds === 0 && $nanoseconds === 0
        ? new _Private\ZeroInterval()
        : new _Private\TimeInterval($hours, $minutes, $seconds, $nanoseconds);
  }

  final public static function minutes(
    int $minutes,
    int $seconds = 0,
    int $nanoseconds = 0,
  ): TimeInterval {
    return self::hours(0, $minutes, $seconds, $nanoseconds);
  }

  final public static function seconds(
    int $seconds,
    int $nanoseconds = 0,
  ): TimeInterval {
    return self::hours(0, 0, $seconds, $nanoseconds);
  }

  final public static function milliseconds(int $milliseconds): TimeInterval {
    return self::hours(0, 0, 0, 1000000 * $milliseconds);
  }

  final public static function microseconds(int $microseconds): TimeInterval {
    return self::hours(0, 0, 0, 1000 * $microseconds);
  }

  final public static function nanoseconds(int $nanoseconds): TimeInterval {
    return self::hours(0, 0, 0, $nanoseconds);
  }

  final public static function zero(): ZeroInterval {
    return new _Private\ZeroInterval();
  }

  final public static function fromParts(
    int $years,
    int $months,
    int $days,
    int $hours,
    int $minutes,
    int $seconds,
    int $nanoseconds,
  ): Interval {
    list($years, $months) = self::normalizeMonths($years, $months);
    self::assertNonNegative($days);
    list($hours, $minutes, $seconds, $nanoseconds) =
      self::normalizeTime($hours, $minutes, $seconds, $nanoseconds);

    $has_months = $years !== 0 || $months !== 0;
    $has_days = $days !== 0;
    $has_time =
      $hours !== 0 || $minutes !== 0 || $seconds !== 0 || $nanoseconds !== 0;

    if (!$has_months && !$has_days && !$has_time) {
      return new _Private\ZeroInterval();
    } else if (!$has_days && !$has_time) {
      return new _Private\MonthInterval($years, $months);
    } else if (!$has_months && !$has_time) {
      return new _Private\DayInterval($days);
    } else if (!$has_months && !$has_days) {
      return
        new _Private\TimeInterval($hours, $minutes, $seconds, $nanoseconds);
    } else {
      return new _Private\UncomparableInterval(
        $years,
        $months,
        $days,
        $hours,
        $minutes,
        $seconds,
        $nanoseconds,
      );
    }
  }

  final public static function fromScalar(
    Time\Interval $interval,
  ): TimeInterval {
    return self::seconds(...Time\to_raw($interval));
  }

  //////////////////////////////////////////////////////////////////////////////
  // getters

  abstract public function getYears(): int;
  abstract public function getMonths(): int;
  abstract public function getDays(): int;
  abstract public function getHours(): int;
  abstract public function getMinutes(): int;
  abstract public function getSeconds(): int;
  abstract public function getNanoseconds(): int;

  final public function getTime(): (int, int, int, int) {
    return tuple(
      $this->getHours(),
      $this->getMinutes(),
      $this->getSeconds(),
      $this->getNanoseconds(),
    );
  }

  final public function getParts(): (int, int, int, int, int, int, int) {
    return tuple(
      $this->getYears(),
      $this->getMonths(),
      $this->getDays(),
      $this->getHours(),
      $this->getMinutes(),
      $this->getSeconds(),
      $this->getNanoseconds(),
    );
  }

  //////////////////////////////////////////////////////////////////////////////
  // with

  abstract const type TWithMonths as Interval;
  abstract const type TWithDays as Interval;
  abstract const type TWithTime as Interval;

  abstract protected function withMonthPart(
    MonthInterval $month_interval,
  ): this::TWithMonths;

  abstract protected function withDayPart(
    DayInterval $day_interval,
  ): this::TWithDays;

  abstract protected function withTimePart(
    TimeInterval $time_interval,
  ): this::TWithTime;

  final public function withYears(int $years): this::TWithMonths {
    return $this->withMonthPart(self::years($years, $this->getMonths()));
  }

  final public function withMonths(int $months): this::TWithMonths {
    return $this->withMonthPart(self::years($this->getYears(), $months));
  }

  final public function withDays(int $days): this::TWithDays {
    return $this->withDayPart(self::days($days));
  }

  final public function withHours(int $hours): this::TWithTime {
    return $this->withTimePart(
      self::hours(
        $hours,
        $this->getMinutes(),
        $this->getSeconds(),
        $this->getNanoseconds(),
      )
    );
  }

  final public function withMinutes(int $minutes): this::TWithTime {
    return $this->withTimePart(
      self::hours(
        $this->getHours(),
        $minutes,
        $this->getSeconds(),
        $this->getNanoseconds(),
      )
    );
  }

  final public function withSeconds(int $seconds): this::TWithTime {
    return $this->withTimePart(
      self::hours(
        $this->getHours(),
        $this->getMinutes(),
        $seconds,
        $this->getNanoseconds(),
      )
    );
  }

  final public function withNanoseconds(int $nanoseconds): this::TWithTime {
    return $this->withTimePart(
      self::hours(
        $this->getHours(),
        $this->getMinutes(),
        $this->getSeconds(),
        $nanoseconds,
      )
    );
  }

  final public function withTime(
    int $hours,
    int $minutes,
    int $seconds = 0,
    int $nanoseconds = 0,
  ): this::TWithTime {
    return $this->withTimePart(
      self::hours($hours, $minutes, $seconds, $nanoseconds),
    );
  }

  //////////////////////////////////////////////////////////////////////////////
  // operations

  /**
   * TimeInterval + TimeInterval = TimeInterval
   * TimeInterval + ZeroInterval = TimeInterval
   * TimeInterval + DayInterval = GenericInterval
   */
  final public function combine(Interval $other): Interval {
    return self::fromParts(
      $this->getYears() + $other->getYears(),
      $this->getMonths() + $other->getMonths(),
      $this->getDays() + $other->getDays(),
      $this->getHours() + $other->getHours(),
      $this->getMinutes() + $other->getMinutes(),
      $this->getSeconds() + $other->getSeconds(),
      $this->getNanoseconds() + $other->getNanoseconds(),
    );
  }

  final public function differenceX(Interval $other): Interval {
    if ($this is TimeInterval && $other is TimeInterval) {
      return $this->difference($other);
    }
    if ($this is DayInterval && $other is DayInterval) {
      return $this->difference($other);
    }
    if ($this is MonthInterval && $other is MonthInterval) {
      return $this->difference($other);
    }
    if ($this is _Private\ZeroInterval) {
      return $this->difference($other);
    }
    if ($other is _Private\ZeroInterval) {
      return $other->difference($this);
    }
    throw new Time\Exception('Intervals are not mutually comparable.');
  }

  //////////////////////////////////////////////////////////////////////////////
  // comparisons

  /**
   * Throws if not mutually comparable.
   */
  final public function compareX(Interval $other): int {
    if ($this is TimeInterval && $other is TimeInterval) {
      return $this->compare($other);
    }
    if ($this is DayInterval && $other is DayInterval) {
      return $this->compare($other);
    }
    if ($this is MonthInterval && $other is MonthInterval) {
      return $this->compare($other);
    }
    if ($this is _Private\ZeroInterval) {
      return $this->compare($other);
    }
    if ($other is _Private\ZeroInterval) {
      return -$other->compare($this);
    }
    throw new Time\Exception('Intervals are not mutually comparable.');
  }

  final public function isComparable(this $other): bool {
    try {
      $this->compareX($other);
      return true;
    } catch (Time\Exception $_) {
      return false;
    }
  }

  /**
   * Throws if not mutually comparable, e.g. "1 month" may or may not be equal
   * to "30 days".
   */
  final public function isEqualX(Interval $other): bool {
    return $this->compareX($other) === 0;
  }

  final public function isShorterX(Interval $other): bool {
    return $this->compareX($other) === -1;
  }

  final public function isShorterOrEqualX(Interval $other): bool {
    return $this->compareX($other) <= 0;
  }

  final public function isLongerX(Interval $other): bool {
    return $this->compareX($other) === 1;
  }

  final public function isLongerOrEqualX(Interval $other): bool {
    return $this->compareX($other) >= 0;
  }

  final public function isBetweenInclX(Interval $a, Interval $b): bool {
    $a = $this->compareX($a);
    $b = $this->compareX($b);
    return $a === 0 || $a !== $b;
  }

  final public function isBetweenExclX(Interval $a, Interval $b): bool {
    $a = $this->compareX($a);
    $b = $this->compareX($b);
    return $a !== 0 && $b !== 0 && $a !== $b;
  }

  //////////////////////////////////////////////////////////////////////////////
  // output

  final public function toString(int $max_decimals = 3): string {
    invariant(
      $max_decimals >= 0,
      'Expected a non-negative number of decimals.',
    );
    $decimal_part = '';
    if ($max_decimals > 0) {
      $decimal_part = (string)$this->getNanoseconds()
        |> Str\pad_left($$, 9, '0')
        |> Str\slice($$, 0, $max_decimals)
        |> Str\trim_right($$, '0');
    }
    if ($decimal_part !== '') {
      $decimal_part = '.'.$decimal_part;
    }

    $values = vec[
      tuple((string)$this->getYears(), 'yr'),
      tuple((string)$this->getMonths(), 'mo'),
      tuple((string)$this->getDays(), 'd'),
      tuple((string)$this->getHours(), 'hr'),
      tuple((string)$this->getMinutes(), 'min'),
      tuple($this->getSeconds().$decimal_part, 'sec'),
    ];
    for (
      $end = C\count($values);
      $end > 0 && $values[$end - 1][0] === '0';
      --$end
    ) {}
    for (
      $start = 0;
      $start < $end && $values[$start][0] === '0';
      ++$start
    ) {}
    $output = vec[];
    for ($i = $start; $i < $end; ++$i) {
      $output[] = $values[$i][0].$values[$i][1];
    }
    return C\is_empty($output) ? '0sec' : Str\join($output, ' ');
  }

  //////////////////////////////////////////////////////////////////////////////
  // internals

  private static function assertNonNegative(int ...$parts): void {
    foreach ($parts as $part) {
      if ($part < 0) {
        throw
          new Time\Exception('Negative date/time intervals are not supported.');
      }
    }
  }

  private static function normalizeMonths(int $years, int $months): (int, int) {
    self::assertNonNegative($years, $months);
    return tuple(
      $years + (int)($months / 12),
      $months % 12,
    );
  }

  private static function normalizeTime(
    int $hours,
    int $minutes,
    int $seconds,
    int $nanoseconds,
  ): (int, int, int, int) {
    self::assertNonNegative($hours, $minutes, $seconds, $nanoseconds);
    $seconds += (int)($nanoseconds / 1000000000);
    $nanoseconds %= 1000000000;
    $minutes += (int)($seconds / 60);
    $seconds %= 60;
    $hours += (int)($minutes / 60);
    $minutes %= 60;
    return tuple($hours, $minutes, $seconds, $nanoseconds);
  }
}
