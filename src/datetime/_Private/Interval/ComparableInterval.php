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
use namespace HH\Lib\{DateTime, Time};

/**
 * Trait shared by all implementations of DateTime\ComparableInterval.
 */
trait ComparableInterval<TComparableTo as DateTime\Interval>
  implements DateTime\ComparableInterval<TComparableTo> {

  final public function isEqual(TComparableTo $other): bool {
    return $this->compare($other) === 0;
  }

  final public function isShorter(TComparableTo $other): bool {
    return $this->compare($other) === -1;
  }

  final public function isShorterOrEqual(TComparableTo $other): bool {
    return $this->compare($other) <= 0;
  }

  final public function isLonger(TComparableTo $other): bool {
    return $this->compare($other) === 1;
  }

  final public function isLongerOrEqual(TComparableTo $other): bool {
    return $this->compare($other) >= 0;
  }

  final public function isBetweenIncl(
    TComparableTo $a,
    TComparableTo $b,
  ): bool {
    $a = $this->compare($a);
    $b = $this->compare($b);
    return $a === 0 || $a !== $b;
  }

  final public function isBetweenExcl(
    TComparableTo $a,
    TComparableTo $b,
  ): bool {
    $a = $this->compare($a);
    $b = $this->compare($b);
    return $a !== 0 && $b !== 0 && $a !== $b;
  }
}
