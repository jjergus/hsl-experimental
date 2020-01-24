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
use namespace HH\Lib\{Str, Time};

/**
 * A set of date/time parts associated with a timezone. Unlike DateTime\Unzoned
 * objects, this represents an "absolute" point in time and can be converted to
 * a DateTime\Timestamp unambiguously.
 */
final class Zoned extends Base {
  const type TFormat = _Private\ZonedDateFormat;
  const type TFormatString = ZonedDateFormatString;

  //////////////////////////////////////////////////////////////////////////////
  // from X to this

  public static function now(Zone $timezone): this {
    return self::fromTimestamp($timezone, now());
  }

  // useful if you don't care about the date
  public static function todayAt(
    Zone $timezone,
    int $hours,
    int $minutes,
    int $seconds = 0,
    int $nanoseconds = 0,
  ): Builder<Zoned> {
    return static::now($timezone)
      ->withTime($hours, $minutes, $seconds, $nanoseconds);
  }

  // if ambiguous because of DST, returns the earlier one; use ->plusHours(1) if
  // you want the later one
  // throws if invalid (e.g. 30th February) or out of timestamp range
  public static function fromParts(
    Zone $timezone,
    int $year,
    int $month,
    int $day,
    int $hours = 0,
    int $minutes = 0,
    int $seconds = 0,
    int $nanoseconds = 0,
  ): Builder<Zoned> {
    return new _Private\ZonedBuilder(
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

  public static function fromTimestamp(
    Zone $timezone,
    Timestamp $timestamp,
  ): this {
    return static::fromTimestampImpl($timezone, $timestamp);
  }

  public static function parse(
    Zone $timezone,
    string $str,
    ?Zoned $relative_to = null,
  ): Zoned {
    if ($relative_to is nonnull && $relative_to->timezone !== $timezone) {
      throw new Time\Exception('Expected objects with identical timezones.');
    }
    return Zoned::fromTimestampImpl(
      $timezone,
      parse($str, $timezone, $relative_to?->timestamp),
    );
  }

  //////////////////////////////////////////////////////////////////////////////
  // from this to X

  public function getTimestamp(): Timestamp {
    return $this->timestamp;
  }

  public function withoutTimezone(): Unzoned {
    return Unzoned::fromParts(...$this->getParts())->exactX();
  }

  public function convertToTimezone(Zone $timezone): this {
    return self::fromTimestamp($timezone, $this->timestamp);
  }

  public function format(ZonedDateFormatString $format_string): string {
    using new _Private\ZoneOverride($this->timezone);
    return \strftime($format_string as string, to_raw_s($this->timestamp));
  }

  //////////////////////////////////////////////////////////////////////////////
  // getters

  public function getTimezone(): Zone {
    return $this->timezone;
  }

  public function getTimezoneOffset(): (int, int) {  // hour, min
    $offset = $this->format('%z');
    $h = (int)Str\slice($offset, 0, Str\length($offset) - 2);
    $m = (int)Str\slice($offset, -2);
    return tuple($h, $m);
  }

  <<__Override>>
  public function getWeekday(): Weekday {
    return (int)$this->format('%w') as Weekday;
  }

  public function isDST(): bool {
    return $this->format('I') === '1';
  }

  <<__Override>>
  public function getISOWeekNumberImpl(): int {
    return (int)$this->format('%V');
  }

  //////////////////////////////////////////////////////////////////////////////
  // comparisons

  /**
   * Returns true iff the provided object represents the same point in time and
   * has the same timezone. Implies that all date/time parts as well as the
   * timestamp of the two objects are equal.
   *
   * See also isAtTheSameTime().
   *
   * To compare date/time parts ignoring timezones, use:
   *   $a->withoutTimezone()->isEqual($b->withoutTimezone())
   */
  public function isEqualIncludingTimezone(this $other): bool {
    return $this->timestamp === $other->timestamp &&
      $this->timezone === $other->timezone;
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
    return new _Private\ZonedBuilder(
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
