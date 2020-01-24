<?hh
/*
 *  Copyright (c) 2004-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace HH\Lib\Time {
  use namespace HH\Lib\Str;

  final class Exception extends \Exception {
    public function __construct(
      Str\SprintfFormatString $format_string,
      mixed ...$format_args
    ) {
      parent::__construct(
        Str\format(
          /* HH_FIXME[4027] */ $format_string,
          ...$format_args
        ),
      );
    }
  }
}
