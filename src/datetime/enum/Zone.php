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

enum Zone: string {
  AMERICA_LOS_ANGELES = 'America/Los_Angeles';
  EUROPE_PRAGUE = 'Europe/Prague';
  // ...

  UTC = 'UTC';

  MINUS_1200 = '-12:00';
  MINUS_0930 = '-09:30';
  PLUS_0545 = '+05:45';
  PLUS_1400 = '+14:00';
  // ...
}
