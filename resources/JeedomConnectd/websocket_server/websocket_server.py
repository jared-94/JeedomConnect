# Author: Johan Hanssen Seferidis
# License: MIT
# The MIT License (MIT)
#
# Copyright (c) 2018 Johan Hanssen Seferidis
#
# Permission is hereby granted, free of charge, to any person obtaining a copy
# of this software and associated documentation files (the "Software"), to deal
# in the Software without restriction, including without limitation the rights
# to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
# copies of the Software, and to permit persons to whom the Software is
# furnished to do so, subject to the following conditions:
#
# The above copyright notice and this permission notice shall be included in all
# copies or substantial portions of the Software.
#
# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
# IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
# FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
# AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
# LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
# OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
# SOFTWARE.

import sys
import struct
import ssl
from base64 import b64encode
from hashlib import sha1
import logging
from socket import error as SocketError
import errno
import threading
from socketserver import ThreadingMixIn, TCPServer, StreamRequestHandler

from websocket_server.thread import WebsocketServerThread

# logging = logging.getlogging(__name__)
# logging.basicConfig()

"""
+-+-+-+-+-------+-+-------------+-------------------------------+
 0                   1                   2                   3
 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
+-+-+-+-+-------+-+-------------+-------------------------------+
|F|R|R|R| opcode|M| Payload len |    Extended payload length    |
|I|S|S|S|  (4)  |A|     (7)     |             (16/64)           |
|N|V|V|V|       |S|             |   (if payload len==126/127)   |
| |1|2|3|       |K|             |                               |
+-+-+-+-+-------+-+-------------+ - - - - - - - - - - - - - - - +
|     Extended payload length continued, if payload len == 127  |
+ - - - - - - - - - - - - - - - +-------------------------------+
|                     Payload Data continued ...                |
+---------------------------------------------------------------+
"""

FIN = 0x80
OPCODE = 0x0F
MASKED = 0x80
PAYLOAD_LEN = 0x7F
PAYLOAD_LEN_EXT16 = 0x7E
PAYLOAD_LEN_EXT64 = 0x7F

OPCODE_CONTINUATION = 0x0
OPCODE_TEXT = 0x1
OPCODE_BINARY = 0x2
OPCODE_CLOSE_CONN = 0x8
OPCODE_PING = 0x9
OPCODE_PONG = 0xA

CLOSE_STATUS_NORMAL = 1000
DEFAULT_CLOSE_REASON = bytes("", encoding="utf-8")


class API:
    def run_forever(self, threaded=False):
        return self._run_forever(threaded)

    def new_client(self, client, server):
        pass

    def client_left(self, client, server):
        pass

    def message_received(self, client, server, message):
        pass

    def set_fn_new_client(self, fn):
        self.new_client = fn

    def set_fn_client_left(self, fn):
        self.client_left = fn

    def set_fn_message_received(self, fn):
        self.message_received = fn

    def send_message(self, client, msg):
        self._unicast(client, msg)

    def send_message_to_all(self, msg):
        logging.info("[WEBSOCKET SEND] broadcast message to ALL connected clients")
        self._multicast(msg)

    def deny_new_connections(
        self, status=CLOSE_STATUS_NORMAL, reason=DEFAULT_CLOSE_REASON
    ):
        self._deny_new_connections(status, reason)

    def allow_new_connections(self):
        self._allow_new_connections()

    def shutdown_gracefully(
        self, status=CLOSE_STATUS_NORMAL, reason=DEFAULT_CLOSE_REASON
    ):
        self._shutdown_gracefully(status, reason)

    def shutdown_abruptly(self):
        self._shutdown_abruptly()

    def disconnect_clients_gracefully(
        self, status=CLOSE_STATUS_NORMAL, reason=DEFAULT_CLOSE_REASON
    ):
        self._disconnect_clients_gracefully(status, reason)

    def disconnect_clients_abruptly(self):
        self._disconnect_clients_abruptly()


class WebsocketServer(ThreadingMixIn, TCPServer, API):
    """
        A websocket server waiting for clients to connect.

    Args:
        port(int): Port to bind to
        host(str): Hostname or IP to listen for connections. By default 127.0.0.1
            is being used. To accept connections from any client, you should use
            0.0.0.0.
        loglevel: Logging level from logging module to use for logging. By default
            warnings and errors are being logged.

    Properties:
        clients(list): A list of connected clients. A client is a dictionary
            like below.
                {
                 'id'      : id,
                 'handler' : handler,
                 'address' : (addr, port)
                }
    """

    allow_reuse_address = True
    daemon_threads = True  # comment to keep threads alive until finished

    def __init__(
        self, host="127.0.0.1", port=0, loglevel=logging.WARNING, key=None, cert=None
    ):
        # logging.setLevel(loglevel)
        TCPServer.__init__(self, (host, port), WebSocketHandler)
        self.host = host
        self.port = self.socket.getsockname()[1]

        self.key = key
        self.cert = cert

        self.clients = []
        self.id_counter = 0
        self.thread = None

        self._deny_clients = False

    def _run_forever(self, threaded):
        cls_name = self.__class__.__name__
        try:
            logging.info("Listening on port %d for clients..." % self.port)
            if threaded:
                self.daemon = True
                self.thread = WebsocketServerThread(
                    target=super().serve_forever, daemon=True, logging=logging
                )
                logging.info(f"Starting {cls_name} on thread {self.thread.getName()}.")
                self.thread.start()
            else:
                self.thread = threading.current_thread()
                logging.info(f"Starting {cls_name} on main thread.")
                super().serve_forever()
        except KeyboardInterrupt:
            self.server_close()
            logging.info("Server terminated.")
        except Exception as e:
            logging.error(str(e), exc_info=True)
            sys.exit(1)

    def _message_received_(self, handler, msg):
        self.message_received(self.handler_to_client(handler), self, msg)

    def _ping_received_(self, handler, msg):
        handler.send_pong(msg)

    def _pong_received_(self, handler, msg):
        pass

    def _new_client_(self, handler, real_ip_add):
        if self._deny_clients:
            status = self._deny_clients["status"]
            reason = self._deny_clients["reason"]
            handler.send_close(status, reason)
            self._terminate_client_handler(handler)
            return

        self.id_counter += 1
        client = {
            "id": self.id_counter,
            "handler": handler,
            "address": handler.client_address,
            "realIpAdd": real_ip_add,
            "apiKey": None,
            "configVersion": None,
            "lastReadTimestamp": None,
            "lastHistoricReadTimestamp": None,
        }
        self.clients.append(client)
        self.new_client(client, self)

    def _client_left_(self, handler):
        client = self.handler_to_client(handler)
        self.client_left(client, self)
        if client in self.clients:
            self.clients.remove(client)

    def _unicast(self, receiver_client, msg):
        logging.debug(
            f"[WEBSOCKET SEND] message to client #{receiver_client['id']}: " + str(msg)
        )
        receiver_client["handler"].send_message(msg)

    def _multicast(self, msg):
        for client in self.clients:
            self._unicast(client, msg)

    def handler_to_client(self, handler):
        for client in self.clients:
            if client["handler"] == handler:
                return client

    def apiKey_to_client(self, apiKey):
        for client in self.clients:
            if client["apiKey"] == apiKey:
                return client
        return None

    def close_client(self, client):
        logging.debug(
            f"Closing connection client #{client['id']} from address: {client['address']}"
        )
        client["handler"].send_close(CLOSE_STATUS_NORMAL, DEFAULT_CLOSE_REASON)
        self._terminate_client_handler(client["handler"])

    def close_client_existing(self, eqApiKey):
        for client in self.clients:
            if client["apiKey"] and client["apiKey"] == eqApiKey:
                self.close_client(client)

    def close_unauthenticated(self, currentTime, maxTime):
        logging.debug(
            f"trying to close unauthenticate client. current time {currentTime} with max time {maxTime}"
        )
        for client in self.clients:
            if "openTimestamp" in client and (
                (currentTime - client["openTimestamp"]) > maxTime
            ):
                logging.warning(
                    f"Over time unauthenticate closing connexion for {str(client)}"
                )
                self.close_client(client)

    def client_not_authenticated(self):
        count = 0
        for client in self.clients:
            if not client["apiKey"]:
                count += 1
        return count

    def _terminate_client_handler(self, handler):
        handler.keep_alive = False
        handler.finish()
        handler.connection.close()

    def _terminate_client_handlers(self):
        """
        Ensures request handler for each client is terminated correctly
        """
        for client in self.clients:
            self._terminate_client_handler(client["handler"])

    def _shutdown_gracefully(
        self, status=CLOSE_STATUS_NORMAL, reason=DEFAULT_CLOSE_REASON
    ):
        """
        Send a CLOSE handshake to all connected clients before terminating server
        """
        logging.debug("close ws server")
        self.keep_alive = False
        self._disconnect_clients_gracefully(status, reason)
        self.server_close()
        self.shutdown()

    def _shutdown_abruptly(self):
        """
        Terminate server without sending a CLOSE handshake
        """
        self.keep_alive = False
        self._disconnect_clients_abruptly()
        self.server_close()
        self.shutdown()

    def _disconnect_clients_gracefully(
        self, status=CLOSE_STATUS_NORMAL, reason=DEFAULT_CLOSE_REASON
    ):
        """
        Terminate clients gracefully without shutting down the server
        """
        for client in self.clients:
            client["handler"].send_close(status, reason)
        self._terminate_client_handlers()

    def _disconnect_clients_abruptly(self):
        """
        Terminate clients abruptly (no CLOSE handshake) without shutting down the server
        """
        self._terminate_client_handlers()

    def _deny_new_connections(self, status, reason):
        self._deny_clients = {
            "status": status,
            "reason": reason,
        }

    def _allow_new_connections(self):
        self._deny_clients = False


class WebSocketHandler(StreamRequestHandler):
    def __init__(self, socket, addr, server):
        self.server = server
        assert not hasattr(self, "_send_lock"), "_send_lock already exists"
        self._send_lock = threading.Lock()
        if server.key and server.cert:
            try:
                socket = ssl.wrap_socket(
                    socket, server_side=True, certfile=server.cert, keyfile=server.key
                )
            except:  # Not sure which exception it throws if the key/cert isn't found
                logging.warning(
                    "SSL not available (are the paths {} and {} correct for the key and cert?)".format(
                        server.key, server.cert
                    )
                )
        StreamRequestHandler.__init__(self, socket, addr, server)

    def setup(self):
        StreamRequestHandler.setup(self)
        self.keep_alive = True
        self.handshake_done = False
        self.valid_client = False

    def handle(self):
        while self.keep_alive:
            if not self.handshake_done:
                self.handshake()
            elif self.valid_client:
                self.read_next_message()

    def read_bytes(self, num):
        return self.rfile.read(num)

    def read_next_message(self):
        try:
            b1, b2 = self.read_bytes(2)
        except SocketError as e:  # to be replaced with ConnectionResetError for py3
            if e.errno == errno.ECONNRESET:
                logging.info("Client closed connection.")
                self.keep_alive = 0
                return
            b1, b2 = 0, 0
        except ValueError as e:
            b1, b2 = 0, 0

        fin = b1 & FIN
        opcode = b1 & OPCODE
        masked = b2 & MASKED
        payload_length = b2 & PAYLOAD_LEN

        if opcode == OPCODE_CLOSE_CONN:
            logging.info("Client asked to close connection.")
            self.keep_alive = 0
            return
        if not masked:
            logging.warning("Client must always be masked.")
            self.keep_alive = 0
            return
        if opcode == OPCODE_CONTINUATION:
            logging.warning("Continuation frames are not supported.")
            return
        elif opcode == OPCODE_BINARY:
            logging.warning("Binary frames are not supported.")
            return
        elif opcode == OPCODE_TEXT:
            opcode_handler = self.server._message_received_
        elif opcode == OPCODE_PING:
            opcode_handler = self.server._ping_received_
        elif opcode == OPCODE_PONG:
            opcode_handler = self.server._pong_received_
        else:
            logging.warning("Unknown opcode %#x." % opcode)
            self.keep_alive = 0
            return

        if payload_length == 126:
            payload_length = struct.unpack(">H", self.rfile.read(2))[0]
        elif payload_length == 127:
            payload_length = struct.unpack(">Q", self.rfile.read(8))[0]

        masks = self.read_bytes(4)
        message_bytes = bytearray()
        for message_byte in self.read_bytes(payload_length):
            message_byte ^= masks[len(message_bytes) % 4]
            message_bytes.append(message_byte)
        opcode_handler(self, message_bytes.decode("utf8"))

    def send_message(self, message):
        self.send_text(message)

    def send_pong(self, message):
        self.send_text(message, OPCODE_PONG)

    def send_close(self, status=CLOSE_STATUS_NORMAL, reason=DEFAULT_CLOSE_REASON):
        """
        Send CLOSE to client

        Args:
            status: Status as defined in https://datatracker.ietf.org/doc/html/rfc6455#section-7.4.1
            reason: Text with reason of closing the connection
        """
        if status < CLOSE_STATUS_NORMAL or status > 1015:
            raise Exception(f"CLOSE status must be between 1000 and 1015, got {status}")

        header = bytearray()
        payload = struct.pack("!H", status) + reason
        payload_length = len(payload)
        assert (
            payload_length <= 125
        ), "We only support short closing reasons at the moment"

        # Send CLOSE with status & reason
        header.append(FIN | OPCODE_CLOSE_CONN)
        header.append(payload_length)
        with self._send_lock:
            try:
                self.request.send(header + payload)
            except IOError as e:
                if e.errno == errno.EPIPE:
                    logging.warning("Broken pipe SKIP")
                    pass
                    # Handling of the error

    def send_text(self, message, opcode=OPCODE_TEXT):
        """
        Important: Fragmented(=continuation) messages are not supported since
        their usage cases are limited - when we don't know the payload length.
        """

        # Validate message
        if isinstance(message, bytes):
            message = try_decode_UTF8(
                message
            )  # this is slower but ensures we have UTF-8
            if not message:
                logging.warning("Can't send message, message is not valid UTF-8")
                return False
        elif not isinstance(message, str):
            logging.warning(
                "Can't send message, message has to be a string or bytes. Got %s"
                % type(message)
            )
            return False

        header = bytearray()
        payload = encode_to_UTF8(message)
        payload_length = len(payload)

        # Normal payload
        if payload_length <= 125:
            header.append(FIN | opcode)
            header.append(payload_length)

        # Extended payload
        elif payload_length >= 126 and payload_length <= 65535:
            header.append(FIN | opcode)
            header.append(PAYLOAD_LEN_EXT16)
            header.extend(struct.pack(">H", payload_length))

        # Huge extended payload
        elif payload_length < 18446744073709551616:
            header.append(FIN | opcode)
            header.append(PAYLOAD_LEN_EXT64)
            header.extend(struct.pack(">Q", payload_length))

        else:
            raise Exception("Message is too big. Consider breaking it into chunks.")
            return

        with self._send_lock:
            self.request.send(header + payload)

    def read_http_headers(self):
        headers = {}
        # first line should be HTTP GET
        http_get = self.rfile.readline().decode().strip()
        assert http_get.upper().startswith("GET")
        # remaining should be headers
        while True:
            header = self.rfile.readline().decode().strip()
            if not header:
                break
            head, value = header.split(":", 1)
            headers[head.lower().strip()] = value.strip()
        return headers

    def handshake(self):
        try:
            headers = self.read_http_headers()
        except Exception:
            logging.warning(
                "[E-00] Client tried to connect but encountered header issue - connexion aborted "
                + str(self.client_address)
            )
            self.keep_alive = False
            return

        try:
            assert headers["upgrade"].lower() == "websocket"
        except KeyError:
            logging.warning(
                "[E-01] Client tried to connect but not with a websocket protocol - connexion aborted "
                + str(self.client_address)
            )
            self.keep_alive = False
            return
        except AssertionError:
            logging.warning(
                "[E-02] Client tried to connect but not with a websocket protocol - connexion aborted"
                + str(self.client_address)
            )
            self.keep_alive = False
            return

        try:
            key = headers["sec-websocket-key"]
        except KeyError:
            logging.warning("Client tried to connect but was missing a key")
            self.keep_alive = False
            return

        try:
            real_ip_add = headers["x-real-ip"]
        except KeyError:
            # logging.warning("no real add")
            real_ip_add = None
            pass

        response = self.make_handshake_response(key)
        with self._send_lock:
            self.handshake_done = self.request.send(response.encode())
        self.valid_client = True
        self.server._new_client_(self, real_ip_add)

    @classmethod
    def make_handshake_response(cls, key):
        return (
            "HTTP/1.1 101 Switching Protocols\r\n"
            "Upgrade: websocket\r\n"
            "Connection: Upgrade\r\n"
            "Sec-WebSocket-Accept: %s\r\n"
            "\r\n" % cls.calculate_response_key(key)
        )

    @classmethod
    def calculate_response_key(cls, key):
        GUID = "258EAFA5-E914-47DA-95CA-C5AB0DC85B11"
        hash = sha1(key.encode() + GUID.encode())
        response_key = b64encode(hash.digest()).strip()
        return response_key.decode("ASCII")

    def finish(self):
        self.server._client_left_(self)


def encode_to_UTF8(data):
    try:
        return data.encode("UTF-8")
    except UnicodeEncodeError as e:
        logging.error("Could not encode data to UTF-8 -- %s" % e)
        return False
    except Exception as e:
        raise (e)
        return False


def try_decode_UTF8(data):
    try:
        return data.decode("utf-8")
    except UnicodeDecodeError:
        return False
    except Exception as e:
        raise (e)
