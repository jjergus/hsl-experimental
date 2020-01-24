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

function format(
  ZonedDateFormatString $format_string,
  Timestamp $timestamp,
  ?Zone $timezone = null,
  // TODO: locale?
): string {
  using new _Private\ZoneOverride($timezone);
  return \strftime($format_string as string, to_raw_s($timestamp));
}

/**
 * Handles the common combination of `DateTime\format(...DateTime\now()...)`.
 */
function now_format(
  ZonedDateFormatString $format_string,
  ?Zone $timezone = null,
): string {
  return format($format_string, now(), $timezone);
}

function parse(
  string $raw_string,
  ?Zone $timezone = null,
  ?Timestamp $relative_to = null,
): Timestamp {
  using new _Private\ZoneOverride($timezone);
  if ($relative_to is nonnull) {
    $relative_to = to_raw_s($relative_to);
  }
  $raw = \strtotime($raw_string, $relative_to);
  if ($raw === false) {
    throw new Time\Exception('Failed to parse date/time: %s', $raw_string);
  }
  return from_raw($raw);
}
