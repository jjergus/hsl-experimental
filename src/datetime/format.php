<?hh
/*
 *  Copyright (c) 2004-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

// In this file: Parsing and formatting operations with scalar values (Timestamp
// and Interval).

namespace HH\Lib\DateTime {

  function parse(
    string $str,
    ?Zone $tz = null,
    ?Timestamp $relative_to = null,
  ): Timestamp {
    using new _Private\ZoneOverride($tz);
    if ($relative_to is nonnull) {
      $relative_to = to_raw_s($relative_to);
    }
    return from_raw_s(\strtotime($str, $relative_to));
    // TODO: falsey > exception
  }

  function format(string $fs, Timestamp $ts, ?Zone $tz = null): string {
    using new _Private\ZoneOverride($tz);
    return \date($fs, to_raw_s($ts));
    // TODO: falsey > exception
  }

  /**
   * Handles the common combination of `DateTime\format(...DateTime\now()...)`.
   */
  function now_format(string $fs, ?Zone $tz = null): string {
    return format($fs, now(), $tz);
  }
}

namespace HH\Lib\Time {
  use namespace HH\Lib\{C, Math, Str};

  /* TODO: should we support a generic interval_format with a FormatString?
  function interval_format(string $fs, Interval $interval): string {
    // days, hours, minutes, seconds, mili (l), micro (u), nano
    // for highest unit in $fs: total rounded down
    // for all other units: count since last [higher unit], rounded down
    // support padding: "02x" or " 2x"
    // support decimals: ".2x" (on smallest unit only? but not on nanoseconds?)
    // disallow duplicates?
  }
  */

  /**
   * 1d 2h 3m 4.05s
   * 23h 0m 0s
   */
  function interval_format_long(Interval $interval, int $decimals = 0): string {
    $parts = interval_parts($interval);
    $output = vec[];
    $days = (int)($parts['hours'] / 24);
    $hours = $parts['hours'] % 24;
    if ($days > 0) {
      $output[] = $days.'d';
    }
    if ($hours > 0 || !C\is_empty($output)) {
      $output[] = $hours.'h';
    }
    if ($parts['minutes'] > 0 || !C\is_empty($output)) {
      $output[] = $parts['minutes'].'m';
    }
    $output[] = $parts['seconds'].
      _Private\decimal_format($parts['nanoseconds'], $decimals).
      's';
    return Str\join($output, ' ');
  }

  /**
   * 123:04:05.067
   * 4:05.067
   * 0:00:00 with $smallest_forced_unit = MINUTE
   */
  function interval_format_short(
    Interval $interval,
    int $decimals = 0,
    Interval $smallest_forced_unit = SECOND,
  ): string {
    $parts = interval_parts($interval);
    $output = vec[];
    if (
      $parts['hours'] > 0 || is_longer_or_equal($smallest_forced_unit, HOUR)
    ) {
      $output[] = $parts['hours'];
    }
    if (
      $parts['minutes'] > 0 ||
      !C\is_empty($output) ||
      is_longer_or_equal($smallest_forced_unit, MINUTE)
    ) {
      $output[] = $parts['minutes'];
    }
    $output[] = $parts['seconds'];
    // pad everything except the first
    for ($i = 1; $i < C\count($output); ++$i) {
      $output[$i] = Str\pad_left((string)$output[$i], 2, '0');
    }
    return Str\join($output, ':').
      _Private\decimal_format($parts['nanoseconds'], $decimals);
  }
}

namespace HH\Lib\Time\_Private {
  use namespace HH\Lib\Str;

  function decimal_format(int $nanoseconds, int $decimals): string {
    invariant($decimals >= 0, 'Expected a non-negative number of decimals.');
    if ($decimals === 0) {
      return '';
    }
    $ret = (string)$nanoseconds
      |> Str\pad_left($$, 9, '0')
      |> Str\slice($$, 0, $decimals)
      |> Str\trim_right($$, '0');
    return $ret === '' ? '' : '.'.$ret;
  }
}
