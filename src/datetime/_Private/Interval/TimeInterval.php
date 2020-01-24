<?hh
/*
 *  Copyright (c) 2004-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace HH\Lib\DateTime\_Private;
use namespace HH\Lib\{DateTime, Time};

/**
 * Implementation of DateTime\TimeInterval. It should never be referenced
 * directly, always reference the interface DateTime\TimeInterval instead.
 */
final class TimeInterval
  extends DateTime\Interval
  implements DateTime\TimeInterval {
  use ComparableInterval<DateTime\TimeInterval>;

  const type TWithMonths = DateTime\Interval;
  const type TWithDays = DateTime\Interval;
  const type TWithTime = DateTime\TimeInterval;

  <<__Override>>
  public function compare(DateTime\TimeInterval $other): int {
    return Time\compare($this->toScalar(), $other->toScalar());
  }

  public function plus<TOther as DateTime\TimeInterval>(
    TOther $other,
  ): DateTime\TimeInterval {
    return DateTime\Interval::hours(
      $this->hours + $other->getHours(),
      $this->minutes + $other->getMinutes(),
      $this->seconds + $other->getSeconds(),
      $this->nanoseconds + $other->getNanoseconds(),
    );
  }

  public function difference<TOther as DateTime\TimeInterval>(
    TOther $other,
  ): DateTime\TimeInterval {
    return Time\difference($this->toScalar(), $other->toScalar())
      |> DateTime\Interval::fromScalar($$);
  }

  public function toScalar(): Time\Interval {
    return Time\hours(
      $this->hours,
      $this->minutes,
      $this->seconds,
      $this->nanoseconds,
    );
  }

  <<__Override>>
  public function getYears(): int {
    return 0;
  }

  <<__Override>>
  public function getMonths(): int {
    return 0;
  }

  <<__Override>>
  public function getDays(): int {
    return 0;
  }

  <<__Override>>
  public function getHours(): int {
    return $this->hours;
  }

  <<__Override>>
  public function getMinutes(): int {
    return $this->minutes;
  }

  <<__Override>>
  public function getSeconds(): int {
    return $this->seconds;
  }

  <<__Override>>
  public function getNanoseconds(): int {
    return $this->nanoseconds;
  }

  <<__Override>>
  protected function withMonthPart(
    DateTime\MonthInterval $month_interval,
  ): DateTime\Interval {
    return $this->combine($month_interval);
  }

  <<__Override>>
  protected function withDayPart(
    DateTime\DayInterval $day_interval,
  ): DateTime\Interval {
    return $this->combine($day_interval);
  }

  <<__Override>>
  protected function withTimePart(
    DateTime\TimeInterval $time_interval,
  ): DateTime\TimeInterval {
    return $time_interval;
  }

  protected function __construct(
    private int $hours,
    private int $minutes,
    private int $seconds,
    private int $nanoseconds,
  ) {}
}
