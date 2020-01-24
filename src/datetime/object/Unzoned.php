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
use namespace HH\Lib\{C, Str, Time};

/**
 * A combination of date/time parts with no timezone associated. Therefore, not
 * actually representing an "absolute" point in time, e.g. you can only
 * transform it to a DateTime\Timestamp if you provide a timezone.
 *
 * TODO: optional literal syntax: dt"2020-01-15 12:51"
 */
final class Unzoned extends Base {

  //////////////////////////////////////////////////////////////////////////////
  // from X to this

  public static function fromParts(
    int $year,
    int $month,
    int $day,
    int $hours = 0,
    int $minutes = 0,
    int $seconds = 0,
    int $nanoseconds = 0,
  ): Builder<this> {
    return self::getBuilder(
      $year,
      $month,
      $day,
      $hours,
      $minutes,
      $seconds,
      $nanoseconds,
    );
  }

  public static function parse(
    string $str,
    Unzoned $relative_to,
  ): this {
    return Zoned::parse(
      Zone::UTC,
      $str,
      $relative_to->withTimezone(Zone::UTC)->getOrThrow(),
    )
      ->withoutTimezone();
  }

  //////////////////////////////////////////////////////////////////////////////
  // from this to X

  public function withTimezone(Zone $timezone): Builder<Zoned> {
    return Zoned::fromParts($timezone, ...$this->getParts());
  }

  // TODO: conversion to Zoned limits range unnecessarily
  public function format(string $format): string {
    $verbotten = keyset['e', 'I', 'O', 'P', 'T', 'Z', 'c', 'r', 'U'];
    $length = Str\length($format);
    for ($i = 0; $i < $length; ++$i) {
      if ($format[$i] === '\\') {
        ++$i;
      } else if (C\contains_key($verbotten, $format[$i])) {
        throw new Exception(
          'Format character "%s" can only be used with DateTime\\Zoned.',
          $format[$i],
        );
      }
    }

    // Timezone used here for convenience, as an implementation detail. It
    // doesn't matter which one, as all format characters depending on the
    // timezone are verbotten.
    return $this->withTimezone(Zone::UTC)->getOrThrow()->format($format);
  }

  //////////////////////////////////////////////////////////////////////////////
  // comparisons

  public function isEqual(Unzoned $other): bool {
    return $this->getParts() === $other->getParts();
  }

  public function isBefore(Unzoned $other): bool {
    if ($this->getYear() < $other->getYear()) {
      return true;
    }
    if ($this->getMonth() < $other->getMonth()) {
      return true;
    }
    if ($this->getDay() < $other->getDay()) {
      return true;
    }
    if ($this->getHours() < $other->getHours()) {
      return true;
    }
    if ($this->getMinutes() < $other->getMinutes()) {
      return true;
    }
    if ($this->getSeconds() < $other->getSeconds()) {
      return true;
    }
    if ($this->getNanoseconds() < $other->getNanoseconds()) {
      return true;
    }
    return false;
  }

  public function isBeforeOrEqual(Unzoned $other): bool {
    return $this->isEqual($other) || $this->isBefore($other);
  }

  public function isAfter(Unzoned $other): bool {
    return !$this->isEqual($other) && !$this->isBefore($other);
  }

  public function isAfterOrEqual(Unzoned $other): bool {
    return !$this->isBefore($other);
  }

  public function isBetweenExcl(Unzoned $a, Unzoned $b): bool {
    return $a->isBefore($this) && $this->isBefore($b) ||
      $b->isBefore($this) && $this->isBefore($a);
  }

  public function isBetweenIncl(Unzoned $a, Unzoned $b): bool {
    return $a->isBeforeOrEqual($this) && $this->isBeforeOrEqual($b) ||
      $b->isBeforeOrEqual($this) && $this->isBeforeOrEqual($a);
  }

  public function compare(Unzoned $other): int {
    return $this->isEqual($other) ? 0 : ($this->isBefore($other) ? -1 : 1);
  }

  //////////////////////////////////////////////////////////////////////////////
  // plus/minus

  // TODO: conversion to Zoned limits range unnecessarily
  public function plus(Time\Interval $interval): this {
    // Timezone doesn't matter, used as an implementation detail here.
    return $this->withTimezone(Zone::UTC)
      ->getOrThrow()
      ->plus($interval)
      ->withoutTimezone();
  }

  //////////////////////////////////////////////////////////////////////////////
  // internals

  <<__Override>>
  protected function withParts(
    int $year,
    int $month,
    int $day,
    int $hours,
    int $minutes,
    int $seconds,
    int $nanoseconds,
  ): Builder<this> {
    return self::getBuilder(
      $year,
      $month,
      $day,
      $hours,
      $minutes,
      $seconds,
      $nanoseconds,
    );
  }

  private static function getBuilder(
    int $year,
    int $month,
    int $day,
    int $hours,
    int $minutes,
    int $seconds,
    int $nanoseconds,
  ): Builder<this> {
    return new Builder(
      ($y, $m, $d, $h, $i, $s, $n) ==> new self($y, $m, $d, $h, $i, $s, $n),
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
