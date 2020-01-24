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
<<__ConsistentConstruct>>
abstract class Base extends _Private\HasParts<this> {

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
    $year = $this->year;
    $week = $this->getISOWeekNumberImpl();
    // the current day may be part of the first/last ISO week of the
    // next/previous year
    if ($this->month === 12 && $week === 1) {
      ++$year;
    } else if ($this->month === 1 && $week > 50) {
      --$year;
    }
    return tuple($year, $week);
  }

  final public function getYearShort(): int {
    return $this->year % 100;
  }

  abstract public function getWeekday(): Weekday;

  final public function isLeapYear(): bool {
    return _Private\is_leap_year($this->year);
  }

  abstract protected function getISOWeekNumberImpl(): int;

  //////////////////////////////////////////////////////////////////////////////
  // compare

  final public function compare(this $other): int {
    return compare($this->timestamp, $other->timestamp);
  }

  /**
   * Returns true iff the provided object represents the same point in time
   * (assuming an observer not moving close to the speed of light). The two
   * objects may have different timezones. Implies that the timestamp of the two
   * objects is equal, but some date/time parts may differ.
   *
   * See also isEqualIncludingTimezone().
   *
   * To compare date/time parts ignoring timezones, use:
   *   $a->withoutTimezone()->isAtTheSameTime($b->withoutTimezone())
   */
  final public function isAtTheSameTime(this $other): bool {
    return $this->compare($other) === 0;
  }

  // note: 3pm in some timezone may be later than 2pm in another
  final public function isBefore(this $other): bool {
    return $this->compare($other) < 0;
  }

  final public function isBeforeOrAtTheSameTime(this $other): bool {
    return $this->compare($other) <= 0;
  }

  final public function isAfter(this $other): bool {
    return $this->compare($other) > 0;
  }

  final public function isAfterOrAtTheSameTime(this $other): bool {
    return $this->compare($other) >= 0;
  }

  final public function isBetweenExcl(this $a, this $b): bool {
    $a = $this->compare($a);
    $b = $this->compare($b);
    return $a === 0 || $a !== $b;
  }

  final public function isBetweenIncl(this $a, this $b): bool {
    $a = $this->compare($a);
    $b = $this->compare($b);
    return $a !== 0 && $b !== 0 && $a !== $b;
  }

  //////////////////////////////////////////////////////////////////////////////
  // plus/minus time intervals (result always valid, no Builder needed)

  final public function plusTime(TimeInterval $interval): this {
    return static::fromTimestampImpl(
      $this->timezone,
      plus($this->timestamp, $interval->toScalar()),
    );
  }

  // TODO: allow extra args
  final public function plusHours(int $hours): this {
    return $this->plusTime(Interval::hours($hours));
  }

  final public function plusMinutes(int $minutes): this {
    return $this->plusTime(Interval::minutes($minutes));
  }

  final public function plusSeconds(int $seconds): this {
    return $this->plusTime(Interval::seconds($seconds));
  }

  final public function plusNanoseconds(int $nanoseconds): this {
    return $this->plusTime(Interval::nanoseconds($nanoseconds));
  }

  final public function minusTime(TimeInterval $interval): this {
    return static::fromTimestampImpl(
      $this->timezone,
      minus($this->timestamp, $interval->toScalar()),
    );
  }

  final public function minusHours(int $hours): this {
    return $this->minusTime(Interval::hours(-$hours));
  }

  final public function minusMinutes(int $minutes): this {
    return $this->minusTime(Interval::minutes(-$minutes));
  }

  final public function minusSeconds(int $seconds): this {
    return $this->minusTime(Interval::seconds(-$seconds));
  }

  final public function minusNanoseconds(int $nanoseconds): this {
    return $this->minusTime(Interval::nanoseconds(-$nanoseconds));
  }

  //////////////////////////////////////////////////////////////////////////////
  // plus/minus date intervals (result may be invalid, so returns a Builder)

  // edge case: 29th February + 1 year
  // Zoned only: 2:30am + 1 year falls on DST change day
  final public function plusYears(int $years): Builder<this> {
    return $this->withYear($this->year + $years);
  }

  final public function minusYears(int $years): Builder<this> {
    return $this->withYear($this->year - $years);
  }

  // edge case: 31st Anymonth + 1 month, 29th January + 1 month
  final public function plusMonths(int $months): Builder<this> {
    if ($months < 0) {
      return $this->minusMonths(-$months);
    }
    $new_month_raw = $this->month + $months - 1;
    return $this->withDate(
      $this->year + (int)($new_month_raw / 12),
      $new_month_raw % 12 + 1,
      $this->day,
    );
  }

  final public function minusMonths(int $months): Builder<this> {
    if ($months < 0) {
      return $this->plusMonths(-$months);
    }
    $new_month_raw = $this->month - $months - 12;
    return $this->withDate(
      $this->year + (int)($new_month_raw / 12),
      $new_month_raw % 12 + 12,
      $this->day,
    );
  }

  final public function plusDays(int $days): Builder<this> {
    // Doing this calculation in UTC to avoid DST issues (0:30am on DST change
    // day + 24 hours = 23:30 on the same day)
    return $this->withDate(
      ...Zoned::fromParts(Zone::UTC, $this->year, $this->month, $this->day)
        ->exactX()
        ->plusHours($days * 24)
        ->getDate()
    );
  }

  final public function minusDays(int $days): Builder<this> {
    return $this->plusDays(-$days);
  }

  //////////////////////////////////////////////////////////////////////////////
  // plus/minus date/time intervals

  final public function timeDifference(this $other): TimeInterval {
    return
      Interval::fromScalar(difference($this->timestamp, $other->timestamp));
  }

  /**
   * Returns the difference between the two DateTime instances as a structured
   * date/time interval, e.g. "2 years, 17 days and 3 hours".
   *
   * Note that the result is not unique for each pair of DateTime instances. For
   * example, the difference between "30th January" and "1st March" is "1 month,
   * 1 day", while the difference between "31st January" and "1st March" is also
   * "1 month, 1 day".
   *
   * If you need a value that unambiguously describes the amount of time between
   * two DateTime instances, use timeDifference() instead.
   *
   * - $a->difference($b) is the same as $b->difference($a)
   * - assuming $a->isBefore($b) and $diff = $a->difference($b), it is
   *   guaranteed that $a->plus($diff)->closest() equals $b
   * - however, it is NOT guaranteed that $b->minus($diff) equals $a
   */
  final public function difference(this $other): Interval {
    if ($this->timezone !== $other->timezone) {
      throw new Time\Exception('Expected objects with identical timezones.');
    }
    $cmp = $this->compare($other);
    if ($cmp === 0) {
      return Interval::zero();
    }
    if ($cmp === 1) {
      return $other->difference($this);
    }
    // Now we know $this is before $other.

    // Figure out if the returned number of years + months + days should be
    // between $this and the exact day of $other or the day before that (if the
    // former would have resulted in a negative time difference).
    // TODO: get rid of "as this"
    $target = $this->withDate(...$other->getDate())->closest() as this;
    if ($target->isAfter($other)) {
      $target = $this
        ->withDate(...$other->minusDays(1)->closest()->getDate())
        ->closest();
    }
    $time_diff = difference($target->timestamp, $this->timestamp);

    // Figure out if the returned number of years + months should be between
    // $this and the exact month of $other or the month before that (if the
    // former would have resulted in a negative number of days).
    $day_diff = $target->day - $this->day;
    if ($day_diff < 0) {
      $target = $target->minusMonths(1)->closest();
      $day_diff += $target->getDaysInMonth();
    }

    return Interval::fromParts(
      0,
      // will be normalized into years + months
      12 * ($target->year - $this->year) + $target->month - $this->month,
      $day_diff,
      0,
      0,
      ...Time\to_raw($time_diff),
    );
  }

  final public function plus(Interval $interval): Builder<this> {
    return $this->plusImpl(...$interval->getParts());
  }

  final public function minus(Interval $interval): Builder<this> {
    list($years, $months, $days, $hours, $minutes, $seconds, $nanoseconds) =
      $interval->getParts();
    return $this->plusImpl(
      -$years,
      -$months,
      -$days,
      -$hours,
      -$minutes,
      -$seconds,
      -$nanoseconds,
    );
  }

  private function plusImpl(
    int $years,
    int $months,
    int $days,
    int $hours,
    int $minutes,
    int $seconds,
    int $nanoseconds,
  ): Builder<this> {
    $builder = $this->plusMonths(12 * $years + $months);
    if ($days !== 0) {
      $builder = $builder->closest()->plusDays($days);
    }
    if (
      $hours !== 0 || $minutes !== 0 || $seconds !== 0 || $nanoseconds !== 0
    ) {
      $builder = $builder->closest()
        ->plusHours($hours)
        ->plusMinutes($minutes)
        ->plusSeconds($seconds)
        ->plusNanoseconds($nanoseconds)
        |> static::builderFromParts($$->timezone, ...$$->getParts());
    }
    return $builder;
  }

  //////////////////////////////////////////////////////////////////////////////
  // internals

  <<__Override>>
  final protected static function instanceFromPartsX(
    Zone $timezone,
    int $year,
    int $month,
    int $day,
    int $hours,
    int $minutes,
    int $seconds,
    int $nanoseconds,
  ): this {
    using new _Private\ZoneOverride($timezone);
    $timestamp = from_raw(
      \mktime($hours, $minutes, $seconds, $month, $day, $year),
      $nanoseconds,
    );
    $ret = new static(
      $timezone,
      $timestamp,
      $year,
      $month,
      $day,
      $hours,
      $minutes,
      $seconds,
      $nanoseconds,
    );
    // mktime() doesn't throw on invalid date/time, but silently returns a
    // timestamp that doesn't match the input; so we check for that here.
    // TODO: This check should probably be done by \mktime().
    if (
      $ret->getParts() !==
        Zoned::fromTimestamp($timezone, $timestamp)->getParts()
    ) {
      throw new Time\Exception('Date/time is not valid in this timezone.');
    }
    return $ret;
  }

  final protected static function fromTimestampImpl(
    Zone $timezone,
    Timestamp $timestamp,
  ): this {
    using new _Private\ZoneOverride($timezone);
    list($s, $ns) = to_raw($timestamp);
    $parts = \getdate($s);
    return new static(
      $timezone,
      $timestamp,
      $parts['year'],
      $parts['mon'],
      $parts['mday'],
      $parts['hours'],
      $parts['minutes'],
      $parts['seconds'],
      $ns,
    );
  }

  <<__Override>>
  final protected function __construct(
    Zone $timezone,
    protected Timestamp $timestamp,
    int $year,
    int $month,
    int $day,
    int $hours,
    int $minutes,
    int $seconds,
    int $nanoseconds,
  ) {
    if (!(
      $month >= 1 && $month <= 12 &&
      $day >= 1 && $day <= _Private\days_in_month($year, $month) &&
      $hours >= 0 && $hours < 24 &&
      $minutes >= 0 && $minutes < 60 &&
      $seconds >= 0 && $seconds < 60 &&  // leap seconds not supported
      $nanoseconds >= 0 && $nanoseconds < 1000000000
    )) {
      throw new Time\Exception('Invalid date/time.');
    }
    parent::__construct(
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
}
