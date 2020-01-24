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
use namespace HH\Lib\Time;

/**
 * An interval that can be compared to other intervals of a compatible type.
 */
interface ComparableInterval<TComparableTo as Interval> {
  require extends Interval;

  public function compare(TComparableTo $other): int;
  public function isEqual(TComparableTo $other): bool;
  public function isShorter(TComparableTo $other): bool;
  public function isShorterOrEqual(TComparableTo $other): bool;
  public function isLonger(TComparableTo $other): bool;
  public function isLongerOrEqual(TComparableTo $other): bool;
  public function isBetweenIncl(TComparableTo $a, TComparableTo $b): bool;
  public function isBetweenExcl(TComparableTo $a, TComparableTo $b): bool;

  public function plus<TOther as TComparableTo>(TOther $other): TComparableTo;
  public function difference<TOther as TComparableTo>(
    TOther $other,
  ): TComparableTo;
}

/**
 * An interval that only specifies time parts (hours, minutes, seconds,
 * nanoseconds) and no date parts (days, months, years).
 *
 * Use DateTime\Interval::hours(), minutes(), seconds() or nanoseconds() to get
 * an instance of TimeInterval.
 */
interface TimeInterval extends ComparableInterval<TimeInterval> {
  abstract const type TWithTime as TimeInterval;
  public function toScalar(): Time\Interval;
}

/**
 * An interval that only specifies a number of days.
 *
 * Use DateTime\Interval::days() to get an instance of DayInterval.
 */
interface DayInterval extends ComparableInterval<DayInterval> {
  abstract const type TWithDays as DayInterval;
}

/**
 * An interval that only specifies a number of months and/or years.
 *
 * Use DateTime\Interval::years() or months() to get an instance of
 * MonthInterval.
 */
interface MonthInterval extends ComparableInterval<MonthInterval> {
  abstract const type TWithMonths as MonthInterval;
}

/**
 * A zero-length interval. Not very useful by itself, it is usually only
 * encountered as a result of calculating the difference between two equal
 * Interval or DateTime objects. ZeroInterval is the only DateTime\Interval type
 * that is comparable to any other Interval object.
 *
 * Use DateTime\Interval::zero() in the rare case you need an explicit instance
 * of ZeroInterval.
 */
interface ZeroInterval extends TimeInterval, DayInterval, MonthInterval {
  const type TWithTime = TimeInterval;
  const type TWithDays = DayInterval;
  const type TWithMonths = MonthInterval;
  public function compare(Interval $other): int;
  public function isEqual(Interval $other): bool;
  public function isShorter(Interval $other): bool;
  public function isShorterOrEqual(Interval $other): bool;
  public function isLonger(Interval $other): bool;
  public function isLongerOrEqual(Interval $other): bool;
  public function isBetweenIncl(Interval $a, Interval $b): bool;
  public function isBetweenExcl(Interval $a, Interval $b): bool;
  public function plus<TOther as Interval>(TOther $other): TOther;
  public function difference<TOther as Interval>(TOther $other): TOther;
}
