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

newtype Timestamp = (int, int);

function now(): Timestamp {
  return from_raw(0, \clock_gettime_ns(\CLOCK_REALTIME));
}

function since(Timestamp $timestamp): Time\Interval {
  $now = now();
  if (is_after($timestamp, $now)) {
    throw new Time\Exception('Expected a Timestamp in the past.');
  }
  return difference($timestamp, $now);
}

function until(Timestamp $timestamp): Time\Interval {
  $now = now();
  if (is_before($timestamp, $now)) {
    throw new Time\Exception('Expected a Timestamp in the future.');
  }
  return difference($now, $timestamp);
}

// from/to raw
function from_raw(int $seconds, int $nanoseconds = 0): Timestamp {
  return Time\_Private\normalize($seconds, $nanoseconds);
}

function to_raw(Timestamp $timestamp): (int, int) {
  return $timestamp;
}

function to_raw_s(Timestamp $timestamp): int {
  return $timestamp[0];
}

// comparisons
function compare(Timestamp $a, Timestamp $b): int {
  return $a[0] !== $b[0] ? $a[0] <=> $b[0] : $a[1] <=> $b[1];
}

function is_before(Timestamp $a, Timestamp $b): bool {
  return compare($a, $b) < 0;
}

function is_before_or_equal(Timestamp $a, Timestamp $b): bool {
  return compare($a, $b) <= 0;
}

function is_after(Timestamp $a, Timestamp $b): bool {
  return compare($a, $b) > 0;
}

function is_after_or_equal(Timestamp $a, Timestamp $b): bool {
  return compare($a, $b) >= 0;
}

function is_equal(Timestamp $a, Timestamp $b): bool {
  return compare($a, $b) === 0;
}

function is_between_incl(Timestamp $value, Timestamp $a, Timestamp $b): bool {
  $a = compare($value, $a);
  $b = compare($value, $b);
  return $a === 0 || $a !== $b;
}

function is_between_excl(Timestamp $value, Timestamp $a, Timestamp $b): bool {
  $a = compare($value, $a);
  $b = compare($value, $b);
  return $a !== 0 && $b !== 0 && $a !== $b;
}

// operations
function difference(Timestamp $a, Timestamp $b): Time\Interval {
  return is_before($a, $b)
    ? Time\_Private\normalize_interval($b[0] - $a[0], $b[1] - $a[1])
    : Time\_Private\normalize_interval($a[0] - $b[0], $a[1] - $b[1]);
}

function plus(Timestamp $value, Time\Interval $interval): Timestamp {
  return Time\_Private\plus($value, $interval);
}

function minus(Timestamp $value, Time\Interval $interval): Timestamp {
  return Time\_Private\minus($value, $interval);
}
