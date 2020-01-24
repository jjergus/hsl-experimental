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
 * A set of date/time parts associated with a timezone. Unlike DateTime\Unzoned
 * objects, this represents an "absolute" point in time and can be converted to
 * a DateTime\Timestamp unambiguously.
 */
final class Zoned extends Base {

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
  ): Builder<this> {
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
  ): Builder<this> {
    return self::getBuilder(
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
    using new _Private\ZoneOverride($timezone);
    $raw_s = to_raw_s($timestamp);
    $parts = \getdate($raw_s);
    return new self(
      $timezone,
      $timestamp,
      $parts['year'],
      $parts['mon'],
      $parts['mday'],
      $parts['hours'],
      $parts['minutes'],
      $parts['seconds'],
      Time\minus($timestamp, Time\seconds($raw_s)) |> to_raw_ns($$),
    );
  }

  public static function parse(
    Zone $timezone,
    string $str,
    ?Zoned $relative_to = null,
  ): this {
    return static::fromTimestamp(
      $timezone,
      parse($str, $timezone, $relative_to?->getTimestamp()),
    );
  }

  //////////////////////////////////////////////////////////////////////////////
  // from this to X

  public function getTimestamp(): Timestamp {
    return $this->timestamp;
  }

  public function withoutTimezone(): Unzoned {
    return Unzoned::fromParts(...$this->getParts())->getOrThrow();
  }

  public function convertToTimezone(Zone $timezone): this {
    return self::fromTimestamp($timezone, $this->timestamp);
  }

  public function format(string $format): string {
    using new _Private\ZoneOverride($this->timezone);
    return \date($format, to_raw_s($this->timestamp));
  }

  //////////////////////////////////////////////////////////////////////////////
  // getters

  public function getTimezone(): Zone {
    return $this->timezone;
  }

  //////////////////////////////////////////////////////////////////////////////
  // fancy getters
  // (most are in the base class, only timezone-related here)

  public function getTimeZoneOffset(): (int, int) { // hour, min
    $offset_s = (int)$this->format('Z');
    return tuple((int)($offset_s / 3600), (int)(($offset_s % 3600) / 60));
  }

  public function isDST(): bool {
    return $this->format('I') === '1';
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
  public function isEqualIncludingTimezone(Zoned $other): bool {
    return $this->timestamp === $other->timestamp &&
      $this->timezone === $other->timezone;
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
   *   $a->withoutTimezone()->isEqual($b->withoutTimezone())
   */
  public function isAtTheSameTime(Zoned $other): bool {
    return Time\is_equal($this->timestamp, $other->timestamp);
  }

  // note: 3pm in some timezone may be later than 2pm in another
  public function isBefore(Zoned $other): bool {
    return Time\is_before($this->timestamp, $other->timestamp);
  }

  public function isBeforeOrAtTheSameTime(Zoned $other): bool {
    return Time\is_before_or_equal($this->timestamp, $other->timestamp);
  }

  public function isAfter(Zoned $other): bool {
    return Time\is_after($this->timestamp, $other->timestamp);
  }

  public function isAfterOrAtTheSameTime(Zoned $other): bool {
    return Time\is_after_or_equal($this->timestamp, $other->timestamp);
  }

  public function isBetweenExcl(Zoned $a, Zoned $b): bool {
    return Time\is_between_excl($this->timestamp, $a->timestamp, $b->timestamp);
  }

  public function isBetweenIncl(Zoned $a, Zoned $b): bool {
    return Time\is_between_incl($this->timestamp, $a->timestamp, $b->timestamp);
  }

  public function compare(Zoned $other): int {
    return Time\compare($this->timestamp, $other->timestamp);
  }

  //////////////////////////////////////////////////////////////////////////////
  // plus minus

  public function plus(Time\Interval $interval): this {
    return self::fromTimestamp(
      $this->timezone,
      Time\plus($this->getTimestamp(), $interval),
    );
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
      $this->timezone,
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
    Zone $timezone,
    int $year,
    int $month,
    int $day,
    int $hours,
    int $minutes,
    int $seconds,
    int $nanoseconds,
  ): Builder<this> {
    return new Builder(
      ($y, $mon, $d, $h, $min, $s, $ns) ==> {
        using new _Private\ZoneOverride($timezone);
        $timestamp = Time\plus(
          from_raw_s(\mktime($h, $min, $s, $mon, $d, $y)),
          Time\nanoseconds($ns),
        );
        $ret = new self(
          $timezone,
          $timestamp,
          $y,
          $mon,
          $d,
          $h,
          $min,
          $s,
          $ns,
        );
        // TODO: This check should probably be done by \mktime().
        if (
          $ret->getParts() !==
            self::fromTimestamp($timezone, $timestamp)->getParts()
        ) {
          throw new Exception('Date/time is not valid in this timezone.');
        }
        return $ret;
      },
      $year,
      $month,
      $day,
      $hours,
      $minutes,
      $seconds,
      $nanoseconds,
    );
  }

  // storing both the timestamp and the parts is redundant, but
  // - allows fast access to each
  // - timestamp disambiguates across DST changes
  // disadvantage: limits range to Timestamp range
  <<__Override>>
  protected function __construct(
    private Zone $timezone,
    private Timestamp $timestamp,
    int $year,
    int $month,
    int $day,
    int $hours,
    int $minutes,
    int $seconds,
    int $nanoseconds,
  ) {
    parent::__construct(
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
