<?hh
/*
 *  Copyright (c) 2004-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace HH\Lib\Time {
  newtype Interval = (int, int);

  // raw number > interval
  function nanoseconds(int $nanoseconds): Interval {
    return _Private\normalize_interval(0, $nanoseconds);
  }

  function microseconds(int $microseconds): Interval {
    return _Private\normalize_interval(0, 1000 * $microseconds);
  }

  function milliseconds(int $milliseconds): Interval {
    return _Private\normalize_interval(0, 1000000 * $milliseconds);
  }

  function seconds(int $seconds, int $nanoseconds = 0): Interval {
    return _Private\normalize_interval($seconds, $nanoseconds);
  }

  function minutes(
    int $minutes,
    int $seconds = 0,
    int $nanoseconds = 0,
  ): Interval {
    return _Private\normalize_interval(60 * $minutes + $seconds, $nanoseconds);
  }

  function hours(
    int $hours,
    int $minutes = 0,
    int $seconds = 0,
    int $nanoseconds = 0,
  ): Interval {
    return _Private\normalize_interval(
      3600 * $hours + 60 * $minutes + $seconds,
      $nanoseconds,
    );
  }

  // interval > raw number
  function to_raw(Interval $interval): (int, int) {
    return $interval;
  }

  function to_raw_s(Interval $interval): int {
    return $interval[0];
  }

  // comparisons
  function compare(Interval $a, Interval $b): int {
    return $a[0] !== $b[0] ? $a[0] <=> $b[0] : $a[1] <=> $b[1];
  }

  function is_shorter(Interval $a, Interval $b): bool {
    return compare($a, $b) < 0;
  }

  function is_shorter_or_equal(Interval $a, Interval $b): bool {
    return compare($a, $b) <= 0;
  }

  function is_longer(Interval $a, Interval $b): bool {
    return compare($a, $b) > 0;
  }

  function is_longer_or_equal(Interval $a, Interval $b): bool {
    return compare($a, $b) >= 0;
  }

  function is_equal(Interval $a, Interval $b): bool {
    return compare($a, $b) === 0;
  }

  function is_between_incl(Interval $value, Interval $a, Interval $b): bool {
    $a = compare($value, $a);
    $b = compare($value, $b);
    return $a === 0 || $a !== $b;
  }

  function is_between_excl(Interval $value, Interval $a, Interval $b): bool {
    $a = compare($value, $a);
    $b = compare($value, $b);
    return $a !== 0 && $b !== 0 && $a !== $b;
  }

  // operations
  function difference(Interval $a, Interval $b): Interval {
    return is_shorter($a, $b)
      ? _Private\normalize_interval($b[0] - $a[0], $b[1] - $a[1])
      : _Private\normalize_interval($a[0] - $b[0], $a[1] - $b[1]);
  }

  function plus(Interval $a, Interval $b): Interval {
    return _Private\plus($a, $b);
  }
}

namespace HH\Lib\Time\_Private {
  use namespace HH\Lib\Time;

  function normalize(int $s, int $ns): (int, int) {
    $s += (int)($ns / 1000000000);
    $ns %= 1000000000;
    if ($ns < 0) {
      --$s;
      $ns += 1000000000;
    }
    return tuple($s, $ns);
  }

  function normalize_interval(int $s, int $ns): Time\Interval {
    $ret = normalize($s, $ns);
    if ($ret[0] < 0) {
      throw new Time\Exception('Negative time intervals are not supported.');
    }
    return $ret;
  }

  function plus((int, int) $value, Time\Interval $interval): (int, int) {
    return normalize($value[0] + $interval[0], $value[1] + $interval[1]);
  }

  function minus((int, int) $value, Time\Interval $interval): (int, int) {
    return normalize($value[0] - $interval[0], $value[1] - $interval[1]);
  }
}
