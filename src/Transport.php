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

namespace Tarekdj\Socket;

/**
 * Class \Hoa\Socket\Transport.
 *
 * Transports manipulation. Can be used to register new transports. A URI is of
 * kind `scheme://uri`. A callable is associated to a `scheme` and represents a
 * factory building valid `Hoa\Socket\Socket` instances (so with `tcp://` or
 * `udp://` “native” schemes).
 */
class Transport
{
    /**
     * Additionnal transports (scheme to callable).
     */
    protected static $_transports = [];



    /**
     * Get all enabled transports.
     */
    public static function get(): array
    {
        return array_merge(
            stream_get_transports(),
            array_keys(static::$_transports)
        );
    }

    /**
     * Check if a transport exists.
     */
    public static function exists(string $transport): bool
    {
        return in_array(strtolower($transport), static::get());
    }

    /**
     * Register a new transport.
     * Note: It is possible to override a standard transport.
     */
    public static function register(string $transport, callable $factory): void
    {
        static::$_transports[$transport] = $factory;
    }

    /**
     * Get the factory associated to a specific transport.
     */
    public static function getFactory(string $transport): callable
    {
        if (false === static::exists($transport) ||
            !isset(static::$_transports[$transport])) {
            return function ($socketUri) {
                return new Socket($socketUri);
            };
        }

        return static::$_transports[$transport];
    }
}
