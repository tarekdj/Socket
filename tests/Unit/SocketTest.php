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

/**
 * Class \Hoa\Socket\Test\Unit\Socket.
 *
 * Test suite for the socket object.
 *
 * @license    New BSD License
 */
class SocketTest extends TestCase
{
    protected function caseCheck(string $uri, array $result)
    {
        $socket = new Socket($uri);
        $this->assertEquals($result, [
            $socket->getAddressType(),
            $socket->getTransport(),
            $socket->getAddress(),
            $socket->getPort(),
        ]);
    }
    public function test_case_full_domain_name()
    {
        $this->caseCheck('tcp://hoa-project.net:80', [
            Socket::ADDRESS_DOMAIN,
            'tcp',
            'hoa-project.net',
            80
        ]);
    }

    public function test_case_domain_name_without_port()
    {
        $this->caseCheck('tcp://hoa-project.net', [
            Socket::ADDRESS_DOMAIN,
            'tcp',
            'hoa-project.net',
            -1
        ]);
    }

    public function test_case_full_ipv4()
    {
        $this->caseCheck('tcp://12.345.67.789:80', [
            Socket::ADDRESS_IPV4,
            'tcp',
            '12.345.67.789',
            80,
        ]);
    }

    public function test_case_ipv4_without_port()
    {
        $this->caseCheck('tcp://12.345.67.789', [
            Socket::ADDRESS_IPV4,
            'tcp',
            '12.345.67.789',
            -1,
        ]);
    }

    public function test_case_ipv4_with_wildcard()
    {
        $this->caseCheck('tcp://*:80', [
            Socket::ADDRESS_IPV4,
            'tcp',
            '0.0.0.0',
            80
        ]);
    }

    public function test_case_ipv4_with_wildcard_without_port()
    {
        $this->caseCheck('tcp://*', [
            Socket::ADDRESS_IPV4,
            'tcp',
            '0.0.0.0',
            -1
        ]);
    }

    public function test_case_full_ipv6()
    {
        $this->caseCheck('tcp://[2001:0db8:85a3:0000:0000:8a2e:0370:7334]:80', [
            Socket::ADDRESS_IPV6,
            'tcp',
            '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
            80
        ]);
    }

    public function test_case_ipv6_without_port()
    {
        $this->caseCheck('tcp://2001:0db8:85a3:0000:0000:8a2e:0370:7334', [
            Socket::ADDRESS_IPV6,
            'tcp',
            '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
            -1
        ]);
    }

    public function test_case_short_ipv6()
    {
        $this->caseCheck('tcp://[2001:0db8:85a3::]:80', [
            Socket::ADDRESS_IPV6,
            'tcp',
            '2001:0db8:85a3::',
            80
        ]);
    }

//    public function case_ipv6_disabled_by_STREAM_PF_INET6(): void
//    {
//        $this
//            ->given(
//                $this->function->defined         = false,
//                $this->function->function_exists = false
//            )
//            ->exception(function (): void {
//                new SUT('tcp://[2001:0db8:85a3::]:80');
//            })
//                ->isInstanceOf(SUT\Exception::class);
//    }

//    public function case_ipv6_disabled_by_AF_INET6(): void
//    {
//        $this
//            ->given(
//                $this->function->function_exists = true,
//                $this->function->defined = function ($constantName) {
//                    return 'AF_INET6' !== $constantName;
//                }
//            )
//            ->exception(function (): void {
//                new SUT('tcp://[2001:0db8:85a3::]:80');
//            })
//                ->isInstanceOf(SUT\Exception::class);
//    }

    // todo check this.
    public function test_case_full_path()
    {
        $this->expectException(Exception::class);
        $this->caseCheck(
            'file:///Hoa/Socket',
            [
                Socket::ADDRESS_PATH,
                'file',
                '/Hoa/Socket',
                -1
            ]
        );
    }

    public function testcase_no_a_URI(): void
    {
        $this->expectException(Exception::class);
        new Socket('foobar');
    }

    public function test_case_has_port(): void
    {
        $this->assertTrue((new Socket('tcp://hoa-project.net:80'))->hasPort());
    }

    public function test_case_has_no_port(): void
    {
        $this->assertFalse((new Socket('tcp://hoa-project.net'))->hasPort());
    }

    public function test_case_is_not_secured(): void
    {
        $this->assertFalse((new Socket('tcp://hoa-project.net:80'))->isSecured());
    }
}
