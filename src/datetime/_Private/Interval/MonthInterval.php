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
use namespace HH\Lib\{Math, DateTime};

/**
 * Implementation of DateTime\MonthInterval. It should never be referenced
 * directly, always reference the interface DateTime\MonthInterval instead.
 */
final class MonthInterval
  extends DateTime\Interval
  implements DateTime\MonthInterval {
  use ComparableInterval<DateTime\MonthInterval>;

  const type TWithMonths = DateTime\MonthInterval;
  const type TWithDays = DateTime\Interval;
  const type TWithTime = DateTime\Interval;

  <<__Override>>
  public function compare(DateTime\MonthInterval $other): int {
    return 12 * $this->getYears() + $this->getMonths() <=>
      12 * $other->getYears() + $other->getMonths();
  }

  public function plus<TOther as DateTime\MonthInterval>(
    TOther $other,
  ): DateTime\MonthInterval {
    return DateTime\Interval::years(
      $this->years + $other->getYears(),
      $this->months + $other->getMonths(),
    );
  }

  public function difference<TOther as DateTime\MonthInterval>(
    TOther $other,
  ): DateTime\MonthInterval {
    return DateTime\Interval::months(
      Math\abs(
        12 * ($this->getYears() - $other->getYears()) +
          $this->getMonths() -
          $other->getMonths(),
      ),
    );
  }

  <<__Override>>
  public function getYears(): int {
    return $this->years;
  }

  <<__Override>>
  public function getMonths(): int {
    return $this->months;
  }

  <<__Override>>
  public function getDays(): int {
    return 0;
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
  ): DateTime\MonthInterval {
    return $month_interval;
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
  ): DateTime\Interval {
    return $this->combine($time_interval);
  }

  protected function __construct(
    private int $years,
    private int $months,
  ) {}
}
