<?hh
/*
 *  Copyright (c) 2004-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

// In this file: Operations with scalar values (Timestamp and Interval).
// Cannot be split into separate files because of opaque type aliases.

namespace HH\Lib\Time\_Private {
  // a type-checker-only concept to prevent users from comparing incompatible
  // timestamps or mixing up timestamps with time intervals
  newtype Type = nothing;
  newtype IntervalType as Type = nothing;
  newtype TimestampType as Type = nothing;
  newtype MonoTimestampType as TimestampType = nothing;
  newtype UnixTimestampType as TimestampType = nothing;

  newtype Value<T as Type> = int;
  newtype BaseTimestamp<T as TimestampType> as Value<T> = int;

  function assert_in_range<T as Type>(
    int $count,
    \HH\Lib\Time\Interval $unit,
  ): void {
    // Using strict inequality here excludes the minimum/maximum representable
    // value. This is to prevent unexpected behavior when building timestamps
    // from parts, e.g. creating the timestamp of the maximum representable
    // minute would succeed, but adding 59 seconds would bring the result out of
    // range.
    invariant(
      $count > (int)(\PHP_INT_MIN / $unit) &&
        $count < (int)(\PHP_INT_MAX / $unit),
      'Time value out of range.',
    );
  }
}

// operations with monotonic clock
namespace HH\Lib\Timer {
  use namespace HH\Lib\Time;
  use type HH\Lib\Time\_Private\{BaseTimestamp, MonoTimestampType};

  newtype Timestamp as BaseTimestamp<MonoTimestampType> = int;

  function now(): Timestamp {
    return \clock_gettime_ns(\CLOCK_MONOTONIC);
  }

  function since(Timestamp $timestamp): Time\Interval {
    return now() - $timestamp;
  }

  function until(Timestamp $timestamp): Time\Interval {
    return $timestamp - now();
  }

  // from/to raw
  function from_raw_ns(int $ns): Timestamp {
    return $ns;
  }

  function from_raw_s(int $s): Timestamp {
    Time\_Private\assert_in_range($s, Time\SECOND);
    return $s * Time\SECOND;
  }

  function to_raw_ns(Timestamp $timestamp): int {
    return $timestamp;
  }

  function to_raw_s(Timestamp $timestamp): int {
    return (int)($timestamp / Time\SECOND);
  }
}

// operations with Unix clock
namespace HH\Lib\DateTime {
  use namespace HH\Lib\Time;
  use type HH\Lib\Time\_Private\{BaseTimestamp, UnixTimestampType};

  newtype Timestamp as BaseTimestamp<UnixTimestampType> = int;

  function now(): Timestamp {
    return \clock_gettime_ns(\CLOCK_REALTIME);
  }

  function since(Timestamp $timestamp): Time\Interval {
    return now() - $timestamp;
  }

  function until(Timestamp $timestamp): Time\Interval {
    return $timestamp - now();
  }

  // from/to raw
  function from_raw_ns(int $ns): Timestamp {
    return $ns;
  }

  function from_raw_s(int $s): Timestamp {
    Time\_Private\assert_in_range($s, Time\SECOND);
    return $s * Time\SECOND;
  }

  function to_raw_ns(Timestamp $timestamp): int {
    return $timestamp;
  }

  function to_raw_s(Timestamp $timestamp): int {
    return (int)($timestamp / Time\SECOND);
  }
}

// interval operations and clock-agnostic operations
namespace HH\Lib\Time {
  use type HH\Lib\Time\_Private\{
    BaseTimestamp,
    IntervalType,
    TimestampType,
    Type,
    Value,
  };

  newtype Interval as Value<IntervalType> = int;

  // raw number > interval
  const Interval SECOND = 1000000000;
  const Interval MINUTE = 60 * SECOND;
  const Interval HOUR = 60 * MINUTE;

  function nanoseconds(int $nanoseconds): Interval {
    return $nanoseconds;
  }

  function seconds(int $seconds, int $nanoseconds = 0): Interval {
    _Private\assert_in_range($seconds, SECOND);
    return ($seconds * SECOND) |> plus($$, $nanoseconds);
  }

  function minutes(
    int $minutes,
    int $seconds = 0,
    int $nanoseconds = 0,
  ): Interval {
    _Private\assert_in_range($minutes, MINUTE);
    return ($minutes * MINUTE) |> plus($$, seconds($seconds, $nanoseconds));
  }

  function hours(
    int $hours,
    int $minutes = 0,
    int $seconds = 0,
    int $nanoseconds = 0,
  ): Interval {
    _Private\assert_in_range($hours, HOUR);
    return
      ($hours * HOUR) |> plus($$, minutes($minutes, $seconds, $nanoseconds));
  }

  // interval > raw number
  function to_raw_ns(Interval $interval): int {
    return $interval;
  }

  function to_raw_s(Interval $interval): int {
    return (int)($interval / SECOND);
  }

  function interval_parts(
    Interval $interval,
  ): shape(
    'hours' => int,
    'minutes' => int,
    'seconds' => int,
    'nanoseconds' => int,
  ) {
    invariant($interval >= 0, 'Expected a non-negative time interval.');
    return shape(
      'hours' => (int)($interval / HOUR),
      'minutes' => (int)(($interval % HOUR) / MINUTE),
      'seconds' => (int)(($interval % MINUTE) / SECOND),
      'nanoseconds' => $interval % SECOND,
    );
  }

  // interval comparison
  function is_shorter(Interval $a, Interval $b): bool {
    return $a < $b;
  }

  function is_shorter_or_equal(Interval $a, Interval $b): bool {
    return $a <= $b;
  }

  function is_longer(Interval $a, Interval $b): bool {
    return $a > $b;
  }

  function is_longer_or_equal(Interval $a, Interval $b): bool {
    return $a >= $b;
  }

  // timestamp comparison
  function is_before<T as TimestampType>(
    BaseTimestamp<T> $a,
    BaseTimestamp<T> $b,
  ): bool {
    return $a < $b;
  }

  function is_before_or_equal<T as TimestampType>(
    BaseTimestamp<T> $a,
    BaseTimestamp<T> $b,
  ): bool {
    return $a <= $b;
  }

  function is_after<T as TimestampType>(
    BaseTimestamp<T> $a,
    BaseTimestamp<T> $b,
  ): bool {
    return $a > $b;
  }

  function is_after_or_equal<T as TimestampType>(
    BaseTimestamp<T> $a,
    BaseTimestamp<T> $b,
  ): bool {
    return $a >= $b;
  }

  // interval/timestamp comparison
  function is_equal<T as Type>(Value<T> $a, Value<T> $b): bool {
    return $a === $b;
  }

  function is_between_incl<T as Type>(
    Value<T> $value,
    Value<T> $a,
    Value<T> $b,
  ): bool {
    return $a <= $value && $value <= $b || $b <= $value && $value <= $b;
  }

  function is_between_excl<T as Type>(
    Value<T> $value,
    Value<T> $a,
    Value<T> $b,
  ): bool {
    return $a < $value && $value < $b || $b < $value && $value < $b;
  }

  function compare<T as Type>(Value<T> $a, Value<T> $b): int {
    return $a <=> $b;
  }

  // timestamp, timestamp > interval
  function between<T as TimestampType>(
    BaseTimestamp<T> $a,
    BaseTimestamp<T> $b,
  ): Interval {
    return $a >= $b ? $a - $b : $b - $a;
  }

  // anything, interval > same thing
  function plus<T as Type, Tv as Value<T>>(
    Tv $value,
    Interval $interval,
  ): Tv {
    invariant(
      $interval >= 0 && $value <= \PHP_INT_MAX - $interval ||
      $interval < 0 && $value >= \PHP_INT_MIN - $interval,
      'Time value out of range.',
    );
    return /* HH_FIXME[4110] */ $value + $interval;
  }

  function minus<T as Type, Tv as Value<T>>(
    Tv $value,
    Interval $interval,
  ): Tv {
    invariant(
      $interval >= 0 && $value >= \PHP_INT_MIN + $interval ||
      $interval < 0 && $value <= \PHP_INT_MAX + $interval,
      'Time value out of range.',
    );
    return /* HH_FIXME[4110] */ $value - $interval;
  }

  // interval > interval
  function invert(Interval $interval): Interval {
    invariant($interval !== \PHP_INT_MIN, 'Time value out of range.');
    return -$interval;
  }
}
