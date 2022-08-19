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

use Tarekdj\Socket\Exception\Exception;

/**
 * Class \Tarekdj\Socket.
 *
 * Socket analyzer.
 */
class Socket
{
    /**
     * Address type: IPv6.
     */
    public const ADDRESS_IPV6   = 0;

    /**
     * Address type: IPv4.
     */
    public const ADDRESS_IPV4   = 1;

    /**
     * Address type: domain.
     */
    public const ADDRESS_DOMAIN = 2;

    /**
     * Address type: path.
     */
    public const ADDRESS_PATH   = 3;

    /**
     * Address.
     */
    protected $_address     = null;

    /**
     * Address type. Please, see the self::ADDRESS_* constants.
     */
    protected $_addressType = 0;

    /**
     * Port.
     */
    protected $_port        = -1;

    /**
     * Transport.
     */
    protected $_transport   = null;

    /**
     * Whether the socket is secured or not.
     */
    protected $_secured     = false;


    /**
     * Constructor.
     */
    public function __construct(string $uri)
    {
        $this->setURI($uri);

        return;
    }

    /**
     * Set URI.
     */
    protected function setURI(string $uri): void
    {
        $m = preg_match(
            '#(?<scheme>[^:]+)://' .
                '(?:\[(?<ipv6_>[^\]]+)\]:(?<ipv6_port>\d+)$|' .
                '(?<ipv4>(\*|\d+(?:\.\d+){3}))(?::(?<ipv4_port>\d+))?$|' .
                '(?<domain>[^:]+)(?::(?<domain_port>\d+))?$|' .
                '(?<ipv6>.+)$)#',
            $uri,
            $matches
        );

        if (0 === $m) {
            throw new Exception(sprintf('URI %s is not recognized (it is not an IPv6, IPv4 nor domain name).', $uri));
        }

        $this->setTransport($matches['scheme']);

        if (isset($matches['ipv6_']) && !empty($matches['ipv6_'])) {
            $this->_address     = $matches['ipv6_'];
            $this->_addressType = self::ADDRESS_IPV6;
            $this->setPort((int) $matches['ipv6_port']);
        } elseif (isset($matches['ipv6']) && !empty($matches['ipv6'])) {
            $this->_address     = $matches['ipv6'];
            $this->_addressType = self::ADDRESS_IPV6;
        } elseif (isset($matches['ipv4']) && !empty($matches['ipv4'])) {
            $this->_address     = $matches['ipv4'];
            $this->_addressType = self::ADDRESS_IPV4;

            if ('*' === $this->_address) {
                $this->_address = '0.0.0.0';
            }

            if (isset($matches['ipv4_port'])) {
                $this->setPort((int) $matches['ipv4_port']);
            }
        } elseif (isset($matches['domain'])) {
            $this->_address = $matches['domain'];

            if (false !== strpos($this->_address, '/')) {
                $this->_addressType = self::ADDRESS_PATH;
            } else {
                $this->_addressType = self::ADDRESS_DOMAIN;
            }

            if (isset($matches['domain_port'])) {
                $this->setPort((int) $matches['domain_port']);
            }
        }

        if ($this->ipv6IsSupported()) {
            throw new Exception(sprintf(
                'IPv6 support has been disabled from PHP, we cannot use the %s URI.',
                1
            ));
        }
    }

    protected function ipv6IsSupported()
    {
        return self::ADDRESS_IPV6 == $this->_addressType &&
            (
                !defined('STREAM_PF_INET6') ||
                (function_exists('function_exists') && !defined('AF_INET6'))
            );
    }

    /**
     * Set the port.
     */
    protected function setPort(int $port): int
    {
        if ($port < 0) {
            throw new Exception(
                'Port must be greater or equal than zero, given %d.',
                2,
                $port
            );
        }

        $old         = $this->_port;
        $this->_port = $port;

        return $old;
    }

    /**
     * Set the transport.
     */
    protected function setTransport(string $transport): ?string
    {
        $transport = strtolower($transport);

        if (false === Transport::exists($transport)) {
            throw new Exception(sprintf('Transport %s is not enabled on this machine.', $transport));
        }

        $old              = $this->_transport;
        $this->_transport = $transport;

        return $old;
    }

    /**
     * Get the address.
     */
    public function getAddress(): ?string
    {
        return $this->_address;
    }

    /**
     * Get the address type.
     */
    public function getAddressType(): int
    {
        return $this->_addressType;
    }

    /**
     * Check if a port was declared.
     */
    public function hasPort(): bool
    {
        return -1 != $this->getPort();
    }

    /**
     * Get the port.
     */
    public function getPort(): int
    {
        return $this->_port;
    }

    /**
     * Check if a transport was declared.
     */
    public function hasTransport(): bool
    {
        return null !== $this->getTransport();
    }

    /**
     * Get the transport.
     */
    public function getTransport(): ?string
    {
        return $this->_transport;
    }

    /**
     * Check if the socket is secured or not.
     */
    public function isSecured(): bool
    {
        return $this->_secured;
    }

    /**
     * Get a string that represents the socket address.
     */
    public function __toString(): string
    {
        $out = null;

        if (true === $this->hasTransport()) {
            $out .= $this->getTransport() . '://';
        }

        if (true === $this->hasPort()) {
            if (self::ADDRESS_IPV6 === $this->getAddressType()) {
                $out .= '[' . $this->getAddress() . ']';
            } else {
                $out .= $this->getAddress();
            }

            return $out . ':' . $this->getPort();
        }

        return $out . $this->getAddress();
    }
}
