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
 * Implementation of DateTime\ZeroInterval. It should never be referenced
 * directly, always reference the interface DateTime\ZeroInterval instead.
 */
final class ZeroInterval
  extends DateTime\Interval
  implements DateTime\ZeroInterval {
  use ComparableInterval<DateTime\Interval>;

  <<__Override>>
  public function compare(DateTime\Interval $other): int {
    return $other is ZeroInterval ? 0 : -1;
  }

  public function plus<TOther as DateTime\Interval>(TOther $other): TOther {
    return $other;
  }

  public function difference<TOther as DateTime\Interval>(
    TOther $other,
  ): TOther {
    return $other;
  }

  public function toScalar(): Time\Interval {
    return Time\nanoseconds(0);
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
  ): DateTime\DayInterval {
    return $day_interval;
  }

  <<__Override>>
  protected function withTimePart(
    DateTime\TimeInterval $time_interval,
  ): DateTime\TimeInterval {
    return $time_interval;
  }

  protected function __construct() {}
}
