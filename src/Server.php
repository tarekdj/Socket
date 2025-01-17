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

use Hoa\Consistency;
use Hoa\Stream;
use Tarekdj\Socket\Connection\Connection;
use Tarekdj\Socket\Exception\Exception;

/**
 * Class \Hoa\Socket\Server.
 *
 * Established a server connection.
 */
class Server extends Connection
{
    /**
     * Tell a stream to bind to the specified target.
     */
    public const BIND               = STREAM_SERVER_BIND;

    /**
     * Tell a stream to start listening on the socket.
     */
    public const LISTEN             = STREAM_SERVER_LISTEN;

    /**
     * Encryption: SSLv2.
     */
    public const ENCRYPTION_SSLv2   = STREAM_CRYPTO_METHOD_SSLv2_SERVER;

    /**
     * Encryption: SSLv3.
     */
    public const ENCRYPTION_SSLv3   = STREAM_CRYPTO_METHOD_SSLv3_SERVER;

    /**
     * Encryption: SSLv2.3.
     */
    public const ENCRYPTION_SSLv23  = STREAM_CRYPTO_METHOD_SSLv23_SERVER;

    /**
     * Encryption: TLS.
     */
    public const ENCRYPTION_TLS     = STREAM_CRYPTO_METHOD_TLS_SERVER;

    /**
     * Encryption: TLSv1.0.
     */
    public const ENCRYPTION_TLSv1_0 = STREAM_CRYPTO_METHOD_TLSv1_0_SERVER;

    /**
     * Encryption: TLSv1.1.
     */
    public const ENCRYPTION_TLSv1_1 = STREAM_CRYPTO_METHOD_TLSv1_1_SERVER;

    /**
     * Encryption: TLSv1.2.
     */
    public const ENCRYPTION_TLSv1_2 = STREAM_CRYPTO_METHOD_TLSv1_2_SERVER;

    /**
     * Encryption: ANY
     */
    public const ENCRYPTION_ANY     = STREAM_CRYPTO_METHOD_ANY_SERVER;

    /**
     * Master connection.
     */
    protected $_master   = null;

    /**
     * All considered server.
     */
    protected $_servers  = [];

    /**
     * Masters connection.
     */
    protected $_masters  = [];

    /**
     * Stack of connections.
     */
    protected $_stack    = [];



    /**
     * Start a connection.
     */
    public function __construct(
        string $socket,
        int $timeout    = 30,
        int $flag       = -1,
        string $context = null
    ) {
        $this->setSocket($socket);
        $socket = $this->getSocket();

        if ($flag == -1) {
            switch ($socket->getTransport()) {
                case 'tcp':
                    $flag = self::BIND | self::LISTEN;

                    break;

                case 'udp':
                    $flag = self::BIND;

                    break;
            }
        } else {
            switch ($socket->getTransport()) {
                case 'tcp':
                    $flag |= self::LISTEN;

                    break;

                case 'udp':
                    if ($flag & self::LISTEN) {
                        throw new Exception(
                            'Cannot use the flag ' .
                            '\Hoa\Socket\Server::LISTEN ' .
                            'for connect-less transports (such as UDP).'
                        );
                    }

                    $flag = self::BIND;

                    break;
            }
        }

        parent::__construct(null, $timeout, $flag, $context);

        return;
    }

    /**
     * Open the stream and return the associated resource.
     */
    protected function &_open(string $streamName, Stream\Context $context = null)
    {
        if (null === $context) {
            $this->_master = @stream_socket_server(
                $streamName,
                $errno,
                $errstr,
                $this->getFlag()
            );
        } else {
            $this->_master = @stream_socket_server(
                $streamName,
                $errno,
                $errstr,
                $this->getFlag(),
                $context->getContext()
            );
        }

        if (false === $this->_master) {
            throw new Exception(
                'Server cannot join %s and returns an error (number %d): %s.',
                1,
                [$streamName, $errno, $errstr]
            );
        }

        $i                  = count($this->_masters);
        $this->_masters[$i] = $this->_master;
        $this->_servers[$i] = $this;
        $this->_stack[]     = $this->_masters[$i];

        return $this->_master;
    }

    /**
     * Close the current stream.
     */
    protected function _close(): bool
    {
        $current = $this->getStream();

        if (false === in_array($current, $this->getMasters(), true)) {
            $stack = &$this->getStack();
            $i     = array_search($current, $stack);

            if (false !== $i) {
                unset($stack[$i]);
            }

            // $this->_node is voluntary kept in memory until a new node will be
            // used.

            unset($this->_nodes[$this->getNodeId($current)]);

            @fclose($current);

            // Closing slave does not have the same effect that closing master.
            return false;
        }

        return (bool) (@fclose($this->_master) + @fclose($this->getStream()));
    }

    /**
     * Connect and accept the first connection.
     */
    public function connect(): Connection
    {
        parent::connect();

        $client = @stream_socket_accept($this->_master);

        if (false === $client) {
            throw new Exception(
                'Operation timed out (nothing to accept).',
                2
            );
        }

        $this->_setStream($client);

        return $this;
    }

    /**
     * Connect but wait for select and accept new connections.
     */
    public function connectAndWait(): self
    {
        return parent::connect();
    }

    /**
     * Select connections.
     */
    public function select(): iterable
    {
        $read   = $this->getStack();
        $write  = null;
        $except = null;

        @stream_select($read, $write, $except, $this->getTimeout(), 0);

        foreach ($read as $socket) {
            $masters = $this->getMasters();

            if (true === in_array($socket, $masters, true)) {
                $client = @stream_socket_accept($socket);

                if (false === $client) {
                    throw new Exception(
                        'Operation timed out (nothing to accept).',
                        3
                    );
                }

                $m      = array_search($socket, $masters, true);
                $server = $this->_servers[$m];
                $id     = $this->getNodeId($client);
                $node   = Consistency\Autoloader::dnew(
                    $server->getNodeName(),
                    [$id, $client, $server]
                );
                $this->_nodes[$id] = $node;
                $this->_stack[]    = $client;
            } else {
                $this->_iterator[] = $socket;
            }
        }

        return $this;
    }

    /**
     * Consider another server when selecting connection.
     */
    public function consider(Connection $other): Connection
    {
        if ($other instanceof Client) {
            if (true === $other->isDisconnected()) {
                $other->connect();
            }

            $this->_stack[] = $other->getStream();

            return $this;
        }

        if (true === $other->isDisconnected()) {
            $other->connectAndWait();
        }

        $i                  = count($this->_masters);
        $this->_masters[$i] = $other->_master;
        $this->_servers[$i] = $other;
        $this->_stack[]     = $this->_masters[$i];

        return $this;
    }

    /**
     * Check if the current node belongs to a specific server.
     */
    public function is(Connection $server): bool
    {
        return $this->getCurrentNode()->getConnection() === $server;
    }

    /**
     * Set and get the current selected connection.
     */
    public function current(): Node
    {
        $current = parent::_current();
        $id      = $this->getNodeId($current);

        if (!isset($this->_nodes[$id])) {
            return $current;
        }

        return $this->_node = $this->_nodes[$this->getNodeId($current)];
    }

    /**
     * Check if the server bind or not.
     */
    public function isBinding(): bool
    {
        return (bool) ($this->getFlag() & self::BIND);
    }

    /**
     * Check if the server is listening or not.
     */
    public function isListening(): bool
    {
        return (bool) ($this->getFlag() & self::LISTEN);
    }

    /**
     * Return internal considered servers.
     */
    protected function getServers(): array
    {
        return $this->_servers;
    }

    /**
     * Return internal master connections.
     */
    protected function getMasters(): array
    {
        return $this->_masters;
    }

    /**
     * Return internal node stack.
     */
    protected function &getStack(): array
    {
        return $this->_stack;
    }
}
