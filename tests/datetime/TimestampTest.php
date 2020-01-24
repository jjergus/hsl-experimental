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
      DateTime\is_before(
        DateTime\now(),
        DateTime\plus(DateTime\now(), Time\minutes(1)),
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
    )->exactX();
    expect($dt->getTimestamp())->toBeSame(tuple(1579705362, 123));

    expect(
      DateTime\Zoned::fromParts(
        DateTime\Zone::UTC,
        1020,
        1,
        22,
        15,
        2,
        42,
        123,
      )->closest()->getParts(),
    )->toBeSame(tuple(1020, 1, 22, 15, 2, 42, 123));

    // Invalid time due to DST change.
    expect(
      () ==> DateTime\Zoned::fromParts(
        DateTime\Zone::AMERICA_LOS_ANGELES,
        2019,
        3,
        10,
        2,
        30,
      )->exactX(),
    )->toThrow(
      Time\Exception::class,
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
      )->closest()->getParts(),
    )->toBeSame(tuple(2019, 3, 10, 3, 30, 0, 0));

    // Ambiguous time due to DST change.
    $dt1 = DateTime\Zoned::fromParts(
      DateTime\Zone::AMERICA_LOS_ANGELES,
      2019,
      11,
      3,
      1,
      30,
    )->exactX();
    expect($dt1->getTimestamp())->toBeSame(tuple(1572769800, 0));
    expect($dt1->getTimezoneOffset())->toBeSame(tuple(-7, 0));

    $dt2 = $dt1->plusHours(1);
    expect($dt2->getTimestamp())->toBeSame(tuple(1572769800 + 3600, 0));
    expect($dt2->getParts())->toBeSame($dt1->getParts());
    expect($dt2->getTimezoneOffset())->toBeSame(tuple(-8, 0));
  }

  public function testNextPreviousMonth(): void {
    for ($month = 1; $month <= 12; ++$month) {
      for ($plus = 0; $plus < 50; ++$plus) {
        expect(
          DateTime\Unzoned::fromParts(2020, $month, 1)->exactX()
            ->plusMonths($plus)->exactX()
            ->minusMonths($plus)->exactX()
            ->getDate()
        )->toBeSame(tuple(2020, $month, 1));
      }
    }
  }

  public function testDateTimeInterval(): void {
    $z = DateTime\Zoned::now(DateTime\Zone::UTC);
    $uz = $z->withoutTimezone();

    $uz->difference($uz);

    $i1 = $z->difference($z);
    $i2 = $uz->difference($uz);

    //DateTime\Interval::between($z, $uz);
    //DateTime\Interval::between($uz, $z);
  }

  public function testComparableInterval(): void {
    $z1 = DateTime\Interval::zero();
    $z2 = DateTime\Interval::zero();
    $d1 = DateTime\Interval::days(3);
    $d2 = DateTime\Interval::days(4);
    $t1 = DateTime\Interval::hours(3, 30);
    $t2 = DateTime\Interval::hours(4);
    $d1->compare($d2);
    $t1->plus($t2)->toScalar();
    $z1->toScalar();
    $z1->plus($t1)->toScalar();
    $t1->plus($z1)->toScalar();
    //$z1->plus($d1)->toScalar();
    $tmp = $z1->withMonths(3);//->toScalar();
    $z1->withHours(3)->toScalar();
  }
}
