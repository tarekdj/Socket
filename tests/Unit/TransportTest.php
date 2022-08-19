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

use PHPUnit\Framework\TestCase;
use Tarekdj\Socket\Exception\Exception;
use Tarekdj\Socket\Socket;
use Tarekdj\Socket\Transport;

/**
 * Class \Tarekdj\Socket\Test\Unit\Transport.
 *
 * Test suite for the transport.
 *
 * @license    New BSD License
 */
class TransportTest extends TestCase
{
    public function test_case_get_standards(): void
    {
        $this->assertEquals(stream_get_transports(), Transport::get());
    }

    public function test_case_get_standards_and_vendors(): void
    {
        Transport::register('foo', function (): void {
        });
        Transport::register('bar', function (): void {
        });
        $standardTransports = stream_get_transports();
        $vendorTransports   = ['foo', 'bar'];
        $this->assertEquals(Transport::get(), array_merge($standardTransports, $vendorTransports));
    }

    public function test_case_exists_standards(): void
    {
        $standardTransports = stream_get_transports();
        shuffle($standardTransports);
        $this->assertTrue(Transport::exists(current($standardTransports)));
    }

    public function test_case_exists_standards_and_vendors(): void
    {
        Transport::register('foo', function (): void {
        });
        Transport::register('bar', function (): void {
        });
        $standardTransports = stream_get_transports();
        $this->assertTrue(Transport::exists(current($standardTransports)));
        $this->assertTrue(Transport::exists('foo'));
    }

    /**
     * @runInSeparateProcess
     */
    public function test_case_not_exists_standards_and_vendors(): void
    {
        Transport::register('foo', function (): void {
        });
        $this->assertFalse(Transport::exists('bar'));
    }

    public function test_case_exists_not_in_lower_case(): void
    {
        $standardTransports = stream_get_transports();
        $transport = strtoupper(current($standardTransports));

        $this->assertTrue(Transport::exists($transport));
    }

    public function test_case_register(): void
    {
        $oldGet = Transport::get();
        $transport = 'foo' . uniqid();
        $oldExists = Transport::exists($transport);
        $result = Transport::register($transport, function (): void {
        });

        $this->assertNull($result);
        $this->assertFalse($oldExists);
        $this->assertTrue(Transport::exists($transport));
        $this->assertEquals(count(Transport::get()), count($oldGet) + 1);
    }

    public function test_case_get_unknown_factory(): void
    {
        $transport = 'foo' . uniqid();
        $result = Transport::getFactory($transport);
        $this->assertInstanceOf(\Closure::class, $result);
        $this->expectException(Exception::class);
        $result($transport . '://127.0.0.1:80');
    }

    public function test_case_get_standard_factory(): void
    {
        $result = Transport::getFactory('tcp');

        $this->assertInstanceOf(\Closure::class, $result);
        $this->assertEquals($result('tcp://127.0.0.1:80'), new Socket('tcp://127.0.0.1:80'));
    }

    public function test_case_get_vendor_factory(): void
    {
        $self = $this;
        $transport = 'foo';
        $called = false;
        $factory = function ($socketUri) use (&$called, $self, $transport) {
            $called = true;
            $self->assertEquals($socketUri, $transport . '://bar/baz');
            return new Socket(
                str_replace($transport, 'tcp', $socketUri)
            );
        };
        Transport::register($transport, $factory);

        $result = Transport::getFactory($transport);

        $this->assertInstanceOf(\Closure::class, $result);
        $this->assertEquals($result('foo://bar/baz'), new Socket('tcp://bar/baz'));
        $this->assertTrue($called);
    }
}
