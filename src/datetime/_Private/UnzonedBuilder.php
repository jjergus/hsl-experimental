<?hh
/*
 *  Copyright (c) 2004-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace HH\Lib\DateTime\_Private;
use namespace HH\Lib\DateTime;

/**
 * Implementation of DateTime\Builder<DateTime\Unzoned>. This class is an
 * implementation detail and shouldn't be referenced directly.
 */
final class UnzonedBuilder extends DateTime\Builder<DateTime\Unzoned> {

  <<__Override>>
  protected static function instanceFromPartsX(
    DateTime\Zone $timezone,
    int $year,
    int $month,
    int $day,
    int $hours,
    int $minutes,
    int $seconds,
    int $nanoseconds,
  ): DateTime\Unzoned {
    return DateTime\Unzoned::instanceFromPartsX(
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
