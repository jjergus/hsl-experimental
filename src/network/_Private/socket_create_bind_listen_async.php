<?hh
/*
 *  Copyright (c) 2004-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace HH\Lib\_Private\_Network;

use namespace HH\Lib\{Network, OS};
use namespace HH\Lib\_Private\_OS;
use type HH\Lib\_Private\PHPWarningSuppressor;

async function socket_create_bind_listen_async(
  int $domain,
  int $type,
  int $proto,
  string $host,
  int $port,
  Network\SocketOptions $socket_options = shape(),
): Awaitable<resource> {
  using new PHPWarningSuppressor();
  /* HH_FIXME[2049] PHPStdLib */
  /* HH_FIXME[4107] PHPStdLib */
  $sock = \socket_create($domain, $type, $proto);
  if (!$sock is resource) {
    /* HH_FIXME[2049] PHPStdLib */
    /* HH_FIXME[4107] PHPStdLib */
    $err = \socket_last_error($sock) as int;
    // using POSIX function naming instead of PHP
    throw_socket_error($err, "socket() failed");
  }
  set_socket_options($sock, $socket_options);
  /* HH_FIXME[2049] PHPStdLib */
  /* HH_FIXME[4107] PHPStdLib */
  \socket_set_blocking($sock, false);
  /* HH_FIXME[2049] PHPStdLib */
  /* HH_FIXME[4107] PHPStdLib */
  if (!\socket_bind($sock, $host, $port)) {
    /* HH_FIXME[2049] PHPStdLib */
    /* HH_FIXME[4107] PHPStdLib */
    $err = \socket_last_error($sock) as int;
    if ($err !== OS\Errno::EINPROGRESS) {
      throw_socket_error($err, 'bind() failed');
    }
  }
  /* HH_FIXME[2049] PHPStdLib */
  /* HH_FIXME[4107] PHPStdLib */
  $err = \socket_last_error($sock) as int;
  if ($err === OS\Errno::EINPROGRESS) {
    /* HH_FIXME[2049] PHPStdLib */
    /* HH_FIXME[4107] PHPStdLib */
    await \stream_await($sock, \STREAM_AWAIT_READ_WRITE);
    /* HH_FIXME[2049] PHP stdlib */
    /* HH_FIXME[4107] PHP stdlib */
    $err = \socket_get_option($sock, \SOL_SOCKET, \SO_ERROR);
  }
  maybe_throw_socket_error($err, 'non-blocking bind() failed asynchronously');
  /* HH_FIXME[2049] PHP stdlib */
  /* HH_FIXME[4107] PHP stdlib */
  if (!\socket_listen($sock)) {
    /* HH_FIXME[2049] PHP stdlib */
    /* HH_FIXME[4107] PHP stdlib */
    $err = \socket_last_error($sock) as int;
    throw_socket_error($err, 'listen() failed');
  }

  return $sock;
}
