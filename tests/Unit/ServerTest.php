<?php

declare(strict_types=1);

/**
 * Hoa
 *
 *
 * @license
 *
 * New BSD License
 *
 * Copyright © 2007-2017, Hoa community. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the Hoa nor the names of its contributors may be
 *       used to endorse or promote products derived from this software without
 *       specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDERS AND CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace Tarekdj\Socket\Test\Unit;

use Hoa\Socket as LUT;
use Hoa\Socket\Server as SUT;
use Hoa\Stream;
use PHPUnit\Framework\TestCase;
use Tarekdj\Socket\Connection\Connection;
use Tarekdj\Socket\Exception\Exception;
use Tarekdj\Socket\Server;
use Tarekdj\Socket\Socket;

/**
 * Class \Hoa\Socket\Test\Unit\Server.
 *
 * Test suite for the server object.
 *
 * @license    New BSD License
 */
class ServerTest extends TestCase
{
    public function test_case_is_a_connection(): void
    {
        $socket = new Server('tcp://hoa-project.net:80');
        $this->assertInstanceOf(Connection::class, $socket);
    }

    public function test_case_constructor(): void
    {
        $socket = 'tcp://hoa-project.net:80';
        $timeout = 42;
        $flag = Server::BIND;
        $context = 'foo';

        $result = new Server($socket, $timeout, $flag, $context);
        $_socket = $result->getSocket();
        $this->assertInstanceOf(Socket::class, $_socket);
        $this->assertEquals($_socket->getAddressType(), $_socket::ADDRESS_DOMAIN);
        $this->assertEquals('tcp', $_socket->getTransport());
        $this->assertEquals('hoa-project.net', $_socket->getAddress());
        $this->assertEquals(80, $_socket->getPort());
        $this->assertFalse($_socket->isSecured());
        $this->assertEquals($result->getTimeout(), $timeout);
        $this->assertEquals($flag | Server::LISTEN, $result->getFlag());
        $this->assertEquals($result->getContext(), $context);
    }

    public function test_case_constructor_no_flag_with_tcp(): void
    {
        $socket = 'tcp://hoa-project.net:80';
        $timeout = 42;
        $result = new Server($socket, $timeout);
        $_socket = $result->getSocket();
        $this->assertEquals('tcp', $_socket->getTransport());
        $this->assertEquals(Server::BIND | Server::LISTEN, $result->getFlag());
    }

    public function test_case_constructor_no_flag_with_udp(): void
    {
        $socket  = 'udp://hoa-project.net:80';
        $timeout = 42;
        $result = new Server($socket, $timeout);
        $_socket = $result->getSocket();
        $this->assertEquals('udp', $_socket->getTransport());
        $this->assertEquals(Server::BIND, $result->getFlag());
    }

    public function test_case_constructor_with_flag_and_tcp(): void
    {
        $socket = 'tcp://hoa-project.net:80';
        $timeout = 42;
        $flag = Server::BIND;
        $result = new Server($socket, $timeout, $flag);
        $_socket = $result->getSocket();
        $this->assertEquals('tcp', $_socket->getTransport());
        $this->assertEquals(Server::BIND | Server::LISTEN, $result->getFlag());
    }

    public function test_case_constructor_with_flag_and_udp(): void
    {
        $socket = 'udp://hoa-project.net:80';
        $timeout = 42;
        $flag = Server::BIND;
        $result = new Server($socket, $timeout, $flag);
        $_socket = $result->getSocket();
        $this->assertEquals('udp', $_socket->getTransport());
        $this->assertEquals($flag, $result->getFlag());
    }

    public function test_case_constructor_with_flag_and_udp_listen_not_allowed(): void
    {
        $socket = 'udp://hoa-project.net:80';
        $timeout = 42;
        $flag = Server::LISTEN;
        $this->expectException(Exception::class);
        new Server($socket, $timeout, $flag);
    }

    public function case_open_cannot_join(): void
    {
        $self = $this;

        $this
            ->given(
                $this->mockGenerator->orphanize('__construct'),
                $server     = new \Mock\Hoa\Socket\Server(),
                $streamName = 'foobar',
                $flag       = SUT::BIND | SUT::LISTEN,

                $this->calling($server)->getFlag      = $flag,
                $this->function->stream_socket_server = function ($_streamName, &$_errno, &$_errstr, $_flag) use ($self, &$called, $streamName, $flag) {
                    $called = true;
                    $_errno = 0;

                    $self
                        ->string($_streamName)
                            ->isEqualTo($streamName)
                        ->integer($_flag)
                            ->isEqualTo($flag);

                    return false;
                }
            )
            ->exception(function () use ($self, $server, $streamName): void {
                $self->invoke($server)->_open($streamName);
            })
                ->isInstanceOf(LUT\Exception::class)
                ->hasCode(1)
            ->boolean($called)
                ->isTrue();
    }

    public function case_open(): void
    {
        $self = $this;

        $this
            ->given(
                $this->mockGenerator->orphanize('__construct'),
                $server     = new \Mock\Hoa\Socket\Server(),
                $streamName = 'foobar',
                $flag       = SUT::BIND | SUT::LISTEN,

                $oldMasters = $this->invoke($server)->getMasters(),
                $oldServers = $this->invoke($server)->getServers(),
                $oldStack   = $this->invoke($server)->getStack(),

                $this->calling($server)->getFlag      = $flag,
                $this->function->stream_socket_server = function ($_streamName, &$_errno, &$_errstr, $_flag) use ($self, &$called, $streamName, $flag) {
                    $called = true;

                    $self
                        ->string($_streamName)
                            ->isEqualTo($streamName)
                        ->integer($_flag)
                            ->isEqualTo($flag);

                    return fopen(__FILE__, 'r');
                }
            )
            ->when($result = $this->invoke($server)->_open($streamName))
            ->then
                ->resource($result)
                ->let($masters = $this->invoke($server)->getMasters())
                ->integer(count($masters))
                    ->isEqualTo(count($oldMasters) + 1)
                    ->isEqualTo(1)
                ->array($masters)
                ->resource($masters[0])
                    ->isIdenticalTo($result)

                ->let($servers = $this->invoke($server)->getServers())
                ->integer(count($servers))
                    ->isEqualTo(count($oldServers) + 1)
                    ->isEqualTo(1)
                ->array($servers)
                ->object($servers[0])
                    ->isIdenticalTo($server)

                ->let($stack = $this->invoke($server)->getStack())
                ->integer(count($stack))
                    ->isEqualTo(count($oldStack) + 1)
                    ->isEqualTo(1)
                ->array($stack)
                ->resource($stack[0])
                    ->isIdenticalTo($result);
    }

    public function case_open_with_context(): void
    {
        $self = $this;

        $this
            ->given(
                $this->mockGenerator->orphanize('__construct'),
                $server     = new \Mock\Hoa\Socket\Server(),
                $streamName = 'foobar',
                $context    = Stream\Context::getInstance('foo'),
                $flag       = SUT::BIND | SUT::LISTEN,

                $oldMasters = $this->invoke($server)->getMasters(),
                $oldServers = $this->invoke($server)->getServers(),
                $oldStack   = $this->invoke($server)->getStack(),

                $this->calling($server)->getFlag      = $flag,
                $this->function->stream_socket_server = function ($_streamName, &$_errno, &$_errstr, $_flag, $_context) use ($self, &$called, $streamName, $flag, $context) {
                    $called = true;

                    $self
                        ->string($_streamName)
                            ->isEqualTo($streamName)
                        ->integer($_flag)
                            ->isEqualTo($flag)
                        ->resource($_context)
                            ->isStreamContext()
                            ->isIdenticalTo($context->getContext());

                    return fopen(__FILE__, 'r');
                }
            )
            ->when($result = $this->invoke($server)->_open($streamName, $context))
            ->then
                ->resource($result)
                ->let($masters = $this->invoke($server)->getMasters())
                ->integer(count($masters))
                    ->isEqualTo(count($oldMasters) + 1)
                    ->isEqualTo(1)
                ->array($masters)
                ->resource($masters[0])
                    ->isIdenticalTo($result)

                ->let($servers = $this->invoke($server)->getServers())
                ->integer(count($servers))
                    ->isEqualTo(count($oldServers) + 1)
                    ->isEqualTo(1)
                ->array($servers)
                ->object($servers[0])
                    ->isIdenticalTo($server)

                ->let($stack = $this->invoke($server)->getStack())
                ->integer(count($stack))
                    ->isEqualTo(count($oldStack) + 1)
                    ->isEqualTo(1)
                ->array($stack)
                ->resource($stack[0])
                    ->isIdenticalTo($result);
    }

    public function case_connect_timed_out(): void
    {
        $self = $this;

        $this
            ->given(
                $this->mockGenerator->orphanize('__construct'),
                $streamName = 'tcp://hoa-project.net:80',
                $server     = new SUT($streamName),

                $this->function->stream_socket_server = fopen(__FILE__, 'r'),

                $master = $this->invoke($server)->_open($streamName),

                $this->function->stream_socket_accept = function ($_master) use ($self, &$called, $master) {
                    $called = true;

                    $self
                        ->resource($_master)
                            ->isEqualTo($master);

                    return false;
                }
            )
            ->exception(function () use ($server): void {
                $server->connect();
            })
                ->isInstanceOf(LUT\Exception::class)
            ->boolean($called)
                ->isTrue();
    }

    public function case_connect(): void
    {
        $self = $this;

        $this
            ->given(
                $this->mockGenerator->orphanize('__construct'),
                $streamName = 'tcp://hoa-project.net:80',
                $server     = new SUT($streamName),

                $this->function->stream_socket_server = fopen(__FILE__, 'r'),

                $master = $this->invoke($server)->_open($streamName),

                $this->function->stream_socket_accept = function ($_master) use ($self, &$called, $master, &$client) {
                    $called = true;

                    $self
                        ->resource($_master)
                            ->isEqualTo($master);

                    return $client = fopen(__FILE__, 'r');
                }
            )
            ->when($result = $server->connect())
            ->then
                ->object($result)
                    ->isIdenticalTo($server)
                ->boolean($called)
                    ->isTrue()
                ->resource($server->getStream())
                    ->isIdenticalTo($client);
    }

    public function case_connect_and_wait(): void
    {
        $self = $this;

        $this
            ->given(
                $this->mockGenerator->orphanize('__construct'),
                $streamName = 'tcp://hoa-project.net:80',
                $server     = new SUT($streamName),

                $this->function->stream_socket_server = fopen(__FILE__, 'r'),

                $master = $this->invoke($server)->_open($streamName),

                $this->function->stream_socket_accept = function () use (&$called) {
                    $called = true;

                    return false;
                }
            )
            ->when($result = $server->connectAndWait())
            ->then
                ->object($result)
                    ->isIdenticalTo($server)
                ->variable($called)
                    ->isNotEqualTo(true);
    }

    public function case_select_not_a_master(): void
    {
        $self = $this;

        $this
            ->given(
                $this->mockGenerator->orphanize('__construct'),
                $server  = new \Mock\Hoa\Socket\Server(),
                $stack   = ['foo'],
                $timeout = 42,

                $oldIteratorValues = $this->invoke($server)->getIteratorValues(),

                $this->calling($server)->getTimeout = $timeout,
                $this->function->stream_select      = function (&$_read, &$_write, &$_except, $_timeout, $_ttimeout) use ($self, &$called, $stack, $timeout): void {
                    $called = true;

                    $self
                        ->array($_read)
                        ->variable($_write)
                            ->isNull()
                        ->variable($_except)
                            ->isNull()
                        ->integer($_timeout)
                            ->isEqualTo($timeout)
                        ->integer($_ttimeout)
                            ->isZero();

                    $_read = $stack;

                    return;
                },
                $this->function->in_array = false
            )
            ->when($result = $server->select())
            ->then
                ->object($result)
                    ->isIdenticalTo($server)

                ->boolean($called)
                    ->isTrue()

                ->let($iteratorValues = $this->invoke($server)->getIteratorValues())
                ->integer(count($iteratorValues))
                    ->isEqualTo(count($oldIteratorValues) + 1);
    }

    public function case_select_timed_out(): void
    {
        $self = $this;

        $this
            ->given(
                $this->mockGenerator->orphanize('__construct'),
                $server  = new \Mock\Hoa\Socket\Server(),
                $stack   = ['foo'],
                $timeout = 42,

                $this->calling($server)->getTimeout = $timeout,
                $this->function->stream_select      = function (&$_read, &$_write, &$_except, $_timeout, $_ttimeout) use ($self, &$called0, $stack, $timeout): void {
                    $called0 = true;

                    $self
                        ->array($_read)
                        ->variable($_write)
                            ->isNull()
                        ->variable($_except)
                            ->isNull()
                        ->integer($_timeout)
                            ->isEqualTo($timeout)
                        ->integer($_ttimeout)
                            ->isZero();

                    $_read = $stack;

                    return;
                },
                $this->function->in_array = true,
                $this->function->stream_socket_accept = function () use (&$called1) {
                    $called1 = true;

                    return false;
                }
            )
            ->exception(function () use ($server): void {
                $server->select();
            })
                ->isInstanceOf(LUT\Exception::class)
            ->boolean($called0)
                ->isTrue()
            ->boolean($called1)
                ->isTrue();
    }

    public function case_consider_client(): void
    {
        $this->_case_consider_client(false);
    }

    public function case_consider_disconnected_client(): void
    {
        $this->_case_consider_client(true);
    }

    protected function _case_consider_client($disconnected): void
    {
        $this
            ->given(
                $this->mockGenerator->orphanize('__construct'),
                $server = new \Mock\Hoa\Socket\Server(),

                $this->mockGenerator->orphanize('__construct'),
                $other = new \Mock\Hoa\Socket\Client(),

                $oldMasters = $this->invoke($server)->getMasters(),
                $oldServers = $this->invoke($server)->getServers(),
                $oldStack   = $this->invoke($server)->getStack(),

                $this->calling($other)->isDisconnected = $disconnected,
                $this->calling($other)->connect        = function () use (&$called, $other) {
                    $called = true;

                    return $other;
                }
            )
            ->when($result = $server->consider($other))
            ->then
                ->object($result)
                    ->isIdenticalTo($server)

                ->let($masters = $this->invoke($server)->getMasters())
                ->integer(count($masters))
                    ->isEqualTo(count($oldMasters))

                ->let($servers = $this->invoke($server)->getServers())
                ->integer(count($servers))
                    ->isEqualTo(count($oldServers))

                ->let($stack = $this->invoke($server)->getStack())
                ->integer(count($stack))
                    ->isEqualTo(count($oldStack) + 1)

                ->variable($called)
                    ->isEqualTo($disconnected ?: null);
    }

    public function case_consider(): void
    {
        $this->_case_consider(false);
    }

    public function case_consider_disconnected_other(): void
    {
        $this->_case_consider(true);
    }

    protected function _case_consider($disconnected): void
    {
        $this
            ->given(
                $this->mockGenerator->orphanize('__construct'),
                $server = new \Mock\Hoa\Socket\Server(),

                $this->mockGenerator->orphanize('__construct'),
                $other = new \Mock\Hoa\Socket\Server(),

                $oldMasters = $this->invoke($server)->getMasters(),
                $oldServers = $this->invoke($server)->getServers(),
                $oldStack   = $this->invoke($server)->getStack(),

                $this->calling($other)->isDisconnected = $disconnected,
                $this->calling($other)->connectAndWait = function () use (&$called, $other) {
                    $called = true;

                    return $other;
                }
            )
            ->when($result = $server->consider($other))
            ->then
                ->object($result)
                    ->isIdenticalTo($server)

                ->let($masters = $this->invoke($server)->getMasters())
                ->integer(count($masters))
                    ->isEqualTo(count($oldMasters) + 1)

                ->let($servers = $this->invoke($server)->getServers())
                ->integer(count($servers))
                    ->isEqualTo(count($oldServers) + 1)

                ->let($stack = $this->invoke($server)->getStack())
                ->integer(count($stack))
                    ->isEqualTo(count($oldStack) + 1)

                ->variable($called)
                    ->isEqualTo($disconnected ?: null);
    }

    public function case_is(): void
    {
        $this
            ->given(
                $this->mockGenerator->orphanize('__construct'),
                $connection = new \Mock\Hoa\Socket\Connection()
            )
            ->_case_is($connection, $connection)
                ->isTrue();
    }

    public function case_is_not(): void
    {
        $this
            ->given(
                $this->mockGenerator->orphanize('__construct'),
                $nodeConnection = new \Mock\Hoa\Socket\Connection(),
                $connection     = clone $nodeConnection
            )
            ->_case_is($nodeConnection, $connection)
                ->isFalse();
    }

    protected function _case_is($nodeConnection, $connection)
    {
        return
            $this
                ->given(
                    $this->mockGenerator->orphanize('__construct'),
                    $server = new \Mock\Hoa\Socket\Server(),
                    $this->mockGenerator->orphanize('__construct'),
                    $node = new \Mock\Hoa\Socket\Node(),

                    $this->calling($server)->getCurrentNode = $node,
                    $this->calling($node)->getConnection = $nodeConnection
                )
                ->when($result = $server->is($connection))
                ->then
                    ->boolean($result);
    }

    public function case_is_binding(): void
    {
        $this
            ->_case_flag_is(SUT::BIND, 'isBinding');
    }

    public function case_is_Listening(): void
    {
        $this
            ->_case_flag_is(SUT::LISTEN, 'isListening');
    }

    protected function _case_flag_is($flag, $method)
    {
        return
            $this
                ->given(
                    $socket  = 'tcp://hoa-project.net:80',
                    $timeout = 42
                )
                ->when($result = new SUT($socket, $timeout, $flag))
                ->then
                    ->boolean($result->$method())
                        ->isTrue();
    }
}
