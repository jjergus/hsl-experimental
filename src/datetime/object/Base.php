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
 * Base class for DateTime objects, DateTime\Zoned and DateTime\Unzoned.
 */
abstract class Base {
  use _Private\HasParts<this>;

  //////////////////////////////////////////////////////////////////////////////
  // from this to X

  abstract public function format(string $format): string;

  //////////////////////////////////////////////////////////////////////////////
  // getters

  final public function getYear(): int {
    return $this->year;
  }

  final public function getMonth(): int {
    return $this->month;
  }

  final public function getDay(): int {
    return $this->day;
  }

  final public function getHours(): int {
    return $this->hours;
  }

  final public function getMinutes(): int {
    return $this->minutes;
  }

  final public function getSeconds(): int {
    return $this->seconds;
  }

  final public function getNanoseconds(): int {
    return $this->nanoseconds;
  }

  // multigetters
  final public function getDate(): (int, int, int) {
    return tuple($this->year, $this->month, $this->day);
  }

  final public function getTime(): (int, int, int, int) {
    return tuple(
      $this->hours,
      $this->minutes,
      $this->seconds,
      $this->nanoseconds,
    );
  }

  final public function getParts(): (int, int, int, int, int, int, int) {
    return tuple(
      $this->year,
      $this->month,
      $this->day,
      $this->hours,
      $this->minutes,
      $this->seconds,
      $this->nanoseconds,
    );
  }

  //////////////////////////////////////////////////////////////////////////////
  // fancy getters

  // I expect a common use case might be constructing a DateTime object just to
  // call one of these: DateTime\Unzoned::fromParts($y, $m, $d)->getWeekday()

  final public function getCentury(): int {
    return (int)($this->year / 100) + 1;
  }

  final public function getDaysInMonth(): int {
    return _Private\days_in_month($this->year, $this->month);
  }

  final public function getHoursAmPm(): (int, AmPm) {
    return tuple(
      $this->hours % 12 ?: 12,
      $this->hours < 12 ? AmPm::AM : AmPm::PM,
    );
  }

  final public function getISOWeekNumber(): (int, int) {  // year, week number
    return tuple((int)$this->format('o'), (int)$this->format('W'));
  }

  final public function getWeekday(): Weekday {
    return (int)$this->format('w') as Weekday;
  }

  final public function getYearShort(): int {
    return $this->year % 100;
  }

  final public function isLeapYear(): bool {
    return _Private\is_leap_year($this->year);
  }

  //////////////////////////////////////////////////////////////////////////////
  // plus/minus

  abstract public function plus(Time\Interval $interval): this;

  final public function plusHours(int $hours): this {
    return $this->plus(Time\hours($hours));
  }

  final public function plusMinutes(int $minutes): this {
    return $this->plus(Time\minutes($minutes));
  }

  final public function plusSeconds(int $seconds): this {
    return $this->plus(Time\seconds($seconds));
  }

  final public function plusNanoseconds(int $nanoseconds): this {
    return $this->plus(Time\nanoseconds($nanoseconds));
  }

  final public function minus(Time\Interval $interval): this {
    return $this->plus(Time\invert($interval));
  }

  final public function minusHours(int $hours): this {
    return $this->plus(Time\hours(-$hours));
  }

  final public function minusMinutes(int $minutes): this {
    return $this->plus(Time\minutes(-$minutes));
  }

  final public function minusSeconds(int $seconds): this {
    return $this->plus(Time\seconds(-$seconds));
  }

  final public function minusNanoseconds(int $nanoseconds): this {
    return $this->plus(Time\nanoseconds(-$nanoseconds));
  }

  //////////////////////////////////////////////////////////////////////////////
  // next

  // edge case: 29th February + 1 year
  // Zoned only: 2:30am + 1 year falls on DST change day
  final public function nextYear(int $years = 1): Builder<this> {
    return $this->withYear($this->year + $years);
  }

  final public function previousYear(int $years = 1): Builder<this> {
    return $this->withYear($this->year - $years);
  }

  // edge case: 31st Anymonth + 1 month, 29th January + 1 month
  final public function nextMonth(int $months = 1): Builder<this> {
    if ($months < 0) {
      return $this->previousMonth(-$months);
    }
    $new_month_raw = $this->month + $months - 1;
    return $this->withDate(
      $this->year + (int)($new_month_raw / 12),
      $new_month_raw % 12 + 1,
      $this->day,
    );
  }

  final public function previousMonth(int $months = 1): Builder<this> {
    if ($months < 0) {
      return $this->nextMonth(-$months);
    }
    $new_month_raw = $this->month - $months - 12;
    return $this->withDate(
      $this->year + (int)($new_month_raw / 12),
      $new_month_raw % 12 + 12,
      $this->day,
    );
  }

  final public function nextDay(int $days = 1): Builder<this> {
    // Doing this calculation in UTC to avoid DST issues (0:30am on DST change
    // day + 24 hours = 23:30 on the same day)
    return $this->withDate(
      ...Zoned::fromParts(Zone::UTC, $this->year, $this->month, $this->day)
        ->getOrThrow()
        ->plusHours($days * 24)
        ->getDate()
    );
  }

  final public function previousDay(int $days = 1): Builder<this> {
    return $this->nextDay(-$days);
  }

  //////////////////////////////////////////////////////////////////////////////
  // internals

  protected function __construct(
    int $year,
    int $month,
    int $day,
    int $hours,
    int $minutes,
    int $seconds,
    int $nanoseconds,
  ) {
    if (!(
      $year > 0 &&
      $month >= 1 && $month <= 12 &&
      $day >= 1 && $day <= _Private\days_in_month($year, $month) &&
      $hours >= 0 && $hours < 24 &&
      $minutes >= 0 && $minutes < 60 &&
      $seconds >= 0 && $seconds < 60 &&  // leap seconds not supported
      $nanoseconds >= 0 && $nanoseconds < Time\to_raw_ns(Time\SECOND)
    )) {
      throw new Exception('Invalid date/time.');
    }
    $this->year = $year;
    $this->month = $month;
    $this->day = $day;
    $this->hours = $hours;
    $this->minutes = $minutes;
    $this->seconds = $seconds;
    $this->nanoseconds = $nanoseconds;
  }
}
