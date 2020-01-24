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
use namespace HH\Lib\{DateTime, Math, Time};

/**
 * Implementation of DateTime\DayInterval. It should never be referenced
 * directly, always reference the interface DateTime\DayInterval instead.
 */
final class DayInterval
  extends DateTime\Interval
  implements DateTime\DayInterval {
  use ComparableInterval<DateTime\DayInterval>;

  const type TWithMonths = DateTime\Interval;
  const type TWithDays = DateTime\DayInterval;
  const type TWithTime = DateTime\Interval;

  <<__Override>>
  public function compare(DateTime\DayInterval $other): int {
    return $this->days <=> $other->getDays();
  }

  public function plus<TOther as DateTime\DayInterval>(
    TOther $other,
  ): DateTime\DayInterval {
    return new self($this->days + $other->getDays());
  }

  public function difference<TOther as DateTime\DayInterval>(
    TOther $other,
  ): DateTime\DayInterval {
    return new self(Math\abs($this->days - $other->getDays()));
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
    return $this->days;
  }

  <<__Override>>
  public function getHours(): int {
    return 0;
  }

  <<__Override>>
  public function getMinutes(): int {
    return 0;
  }

  <<__Override>>
  public function getSeconds(): int {
    return 0;
  }

  <<__Override>>
  public function getNanoseconds(): int {
    return 0;
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
  ): DateTime\DayInterval {
    return $day_interval;
  }

  <<__Override>>
  protected function withTimePart(
    DateTime\TimeInterval $time_interval,
  ): DateTime\Interval {
    return $this->combine($time_interval);
  }

  protected function __construct(private int $days) {}
}
