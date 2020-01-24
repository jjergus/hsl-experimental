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
  ): Builder<Unzoned> {
    return new _Private\UnzonedBuilder(
      Zone::UTC,
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
    Zone $timezone,
    string $str,
    ?Unzoned $relative_to = null,
  ): Unzoned {
    return Unzoned::fromTimestampImpl(
      $timezone,
      parse($str, $timezone, $relative_to?->timestamp),
    );
  }

  //////////////////////////////////////////////////////////////////////////////
  // from this to X

  public function withTimezone(Zone $timezone): Builder<Zoned> {
    return Zoned::fromParts($timezone, ...$this->getParts());
  }

  public function format(UnzonedDateFormatString $format_string): string {
    using new _Private\ZoneOverride($this->timezone);
    return \strftime($format_string as string, to_raw_s($this->timestamp));
  }

  //////////////////////////////////////////////////////////////////////////////
  // getters

  <<__Override>>
  public function getWeekday(): Weekday {
    return (int)$this->format('%w') as Weekday;
  }

  <<__Override>>
  protected function getISOWeekNumberImpl(): int {
    return (int)$this->format('%V');
  }

  //////////////////////////////////////////////////////////////////////////////
  // internals

  <<__Override>>
  protected static function builderFromParts(
    Zone $timezone,
    int $year,
    int $month,
    int $day,
    int $hours,
    int $minutes,
    int $seconds,
    int $nanoseconds,
  ): Builder<this> {
    return new _Private\UnzonedBuilder(
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
