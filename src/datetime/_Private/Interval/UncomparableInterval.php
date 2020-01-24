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
 * The most generic implementation of DateTime\Interval, used for any intervals
 * that don't satisfy the conditions for any other implementation (TimeInterval,
 * DayInterval, MonthInterval, ZeroInterval). Instances of this class cannot be
 * compared to any other intervals, including instances of the same class.
 *
 * This class is considered an implementation detail and should never be
 * referenced directly, always reference DateTime\Interval instead.
 */
final class UncomparableInterval extends DateTime\Interval {

  const type TWithMonths = DateTime\Interval;
  const type TWithDays = DateTime\Interval;
  const type TWithTime = DateTime\Interval;

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
    return $this->days;
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
    return DateTime\Interval::fromParts(
      $month_interval->getYears(),
      $month_interval->getMonths(),
      $this->days,
      $this->hours,
      $this->minutes,
      $this->seconds,
      $this->nanoseconds,
    );
  }

  <<__Override>>
  protected function withDayPart(
    DateTime\DayInterval $day_interval,
  ): DateTime\Interval {
    return DateTime\Interval::fromParts(
      $this->years,
      $this->months,
      $day_interval->getDays(),
      $this->hours,
      $this->minutes,
      $this->seconds,
      $this->nanoseconds,
    );
  }

  <<__Override>>
  protected function withTimePart(
    DateTime\TimeInterval $time_interval,
  ): DateTime\Interval {
    return DateTime\Interval::fromParts(
      $this->years,
      $this->months,
      $this->days,
      $time_interval->getHours(),
      $time_interval->getMinutes(),
      $time_interval->getSeconds(),
      $time_interval->getNanoseconds(),
    );
  }

  protected function __construct(
    private int $years,
    private int $months,
    private int $days,
    private int $hours,
    private int $minutes,
    private int $seconds,
    private int $nanoseconds,
  ) {}
}
