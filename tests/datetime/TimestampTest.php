<?hh // strict
/*
 *  Copyright (c) 2004-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

use namespace HH\Lib\{DateTime, Time, Timer};

use function Facebook\FBExpect\expect; // @oss-enable
use type Facebook\HackTest\HackTest; // @oss-enable
// @oss-disable: use type HackTest;

// @oss-disable: <<Oncalls('hhvm_oss')>>
final class TimestampTest extends HackTest {

  public function testStuff(): void {
    expect(
      Time\is_before(
        DateTime\now(),
        Time\plus(DateTime\now(), Time\MINUTE),
      )
    )->toBeTrue();

    $dt = DateTime\Zoned::fromParts(
      DateTime\Zone::UTC,
      2020,
      1,
      22,
      15,
      2,
      42,
      123,
    )->getOrThrow();
    expect($dt->getTimestamp())->toBeSame(1579705362000000123);

    expect(
      () ==> DateTime\Zoned::fromParts(
        DateTime\Zone::UTC,
        1020,
        1,
        22,
        15,
        2,
        42,
        123,
      )->getClosest(), // TODO: should not throw?
    )->toThrow(InvariantException::class, 'Time value out of range.');

    // Invalid time due to DST change.
    expect(
      () ==> DateTime\Zoned::fromParts(
        DateTime\Zone::AMERICA_LOS_ANGELES,
        2019,
        3,
        10,
        2,
        30,
      )->getOrThrow(),
    )->toThrow(
      DateTime\Exception::class,
      'Date/time is not valid in this timezone.',
    );

    expect(
      DateTime\Zoned::fromParts(
        DateTime\Zone::AMERICA_LOS_ANGELES,
        2019,
        3,
        10,
        2,
        30,
      )->getClosest()->getParts(),
    )->toBeSame(tuple(2019, 3, 10, 3, 0, 0, 0));

    // Ambiguous time due to DST change.
    $dt1 = DateTime\Zoned::fromParts(
      DateTime\Zone::AMERICA_LOS_ANGELES,
      2019,
      11,
      3,
      1,
      30,
    )->getOrThrow();
    expect($dt1->getTimestamp())->toBeSame(1572769800000000000);
    expect($dt1->getTimeZoneOffset())->toBeSame(tuple(-7, 0));

    $dt2 = $dt1->plusHours(1);
    expect($dt2->getTimestamp())->toBeSame(1572769800000000000 + 3600000000000);
    expect($dt2->getParts())->toBeSame($dt1->getParts());
    expect($dt2->getTimeZoneOffset())->toBeSame(tuple(-8, 0));
  }

  public function testNextPreviousMonth(): void {
    for ($month = 1; $month <= 12; ++$month) {
      for ($plus = 0; $plus < 50; ++$plus) {
        expect(
          DateTime\Unzoned::fromParts(2020, $month, 1)->getOrThrow()
            ->nextMonth($plus)->getOrThrow()
            ->previousMonth($plus)->getOrThrow()
            ->getDate()
        )->toBeSame(tuple(2020, $month, 1));
      }
    }
  }
}
