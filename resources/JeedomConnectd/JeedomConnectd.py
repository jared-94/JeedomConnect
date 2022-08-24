import logging
import argparse
import sys
import os
import traceback
import signal
import json
import threading
import time
import sys
import uuid

from multiprocessing import Process

from jeedom.jeedom import jeedom_utils, jeedom_com, jeedom_socket, JEEDOM_SOCKET_MESSAGE

from websocket_server import WebsocketServer


def read_socket():
    global JEEDOM_SOCKET_MESSAGE
    if not JEEDOM_SOCKET_MESSAGE.empty():
        logging.debug("Msg received in JEEDOM_SOCKET_MESSAGE")
        msg_socket_str = JEEDOM_SOCKET_MESSAGE.get().decode("utf-8")
        msg_socket = json.loads(msg_socket_str)
        logging.debug("Msg received => " + msg_socket_str)
        try:
            if msg_socket.get("jeedomApiKey", None) != _apikey:
                raise Exception("Invalid apikey from socket : " + str(msg_socket))

            method = msg_socket.get("type", None)
            eqApiKey = msg_socket.get("eqApiKey", None)
            if eqApiKey:
                toClient = server.apiKey_to_client(eqApiKey)
            else:
                raise Exception("no apiKey found ! ")

            if toClient and method == "WELCOME":
                toClient["configVersion"] = msg_socket["payload"]["configVersion"]
                toClient["lastReadTimestamp"] = time.time()
                toClient["lastHistoricReadTimestamp"] = time.time()
                # logging.debug("all data client =>" + str(toClient))

            if method == "SET_EVENTS":
                for elt in msg_socket.get("payload", None):
                    # logging.debug("checking elt =>" + str(elt))
                    if elt.get("type", None) == "DATETIME":
                        toClient["lastReadTimestamp"] = elt.get("payload", None)

                    elif elt.get("type", None) == "HIST_DATETIME":
                        toClient["lastHistoricReadTimestamp"] = elt.get("payload", None)

                    else:
                        if (
                            "payload" in elt
                            and hasattr(elt, "__len__")
                            and len(elt["payload"]) > 0
                        ):
                            logging.debug(
                                f"Broadcast to {toClient['id']} : " + str(elt)
                            )
                            server.send_message(toClient, json.dumps(elt))
            else:
                if toClient:
                    server.send_message(toClient, msg_socket_str)
                else:
                    raise Exception("no client found ! ")

        except Exception as e:
            logging.exception(e)


# ----------------------------------------------------------------------------
# ----------------------------------------------------------------------------


def listen():
    logging.debug("Start listening")
    jeedomSocket.open()
    try:
        while 1:
            time.sleep(0.01)
            read_socket()
    except KeyboardInterrupt:
        shutdown()


def handler(signum=None, frame=None):
    logging.debug("Signal %i caught, exiting..." % int(signum))
    shutdown()


def shutdown():
    logging.debug("Shutdown")
    try:
        logging.debug("Shutdown websocket server")
        server.shutdown_gracefully
    except Exception as err:
        # logging.exception("shutdown ws server exception " + str(err))
        pass

    try:
        logging.debug("Removing PID file " + str(_pidfile))
        os.remove(_pidfile)
    except:
        pass

    # if error below, closing the connexion, and exit
    try:
        logging.debug("Closing Jeedom Socket connexion")
        jeedomSocket.close()
    except:
        pass

    logging.debug("Exit 0")
    sys.stdout.flush()
    os._exit(0)


# ----------------------------------------------------------------------------
# ----------------------------------------------------------------------------
def new_client(client, server):
    logging.debug(f"New connection: #{client['id']} from IP: {client['address']}")


def onMessageReceived(client, server, message):
    logging.info("[WS] Message received=> " + str(message))
    try:
        original = json.loads(message)
        method = original.get("method", None)
        params = original.get("params", None)

        if method == "CONNECT":
            apiKey = params.get("apiKey", None)
            client["apiKey"] = apiKey

        if not params:
            original["params"] = dict()

        if "apiKey" not in original["params"]:
            original["params"]["apiKey"] = client.get("apiKey", None)

        original["params"]["connexionFrom"] = "WS"

        jeedomCom.send_change_immediate(original)
    except Exception as err:
        logging.exception("Exception onMessageReceived : " + str(err))


def async_worker():
    logging.debug("Starting loop to retrieve all events")
    try:
        while True:
            for client in server.clients:
                if client.get("apiKey", None):
                    result = dict()
                    result["jsonrpc"] = "2.0"
                    result["method"] = "GET_EVENTS"
                    result["id"] = str(uuid.uuid4())

                    params = dict()
                    params["apiKey"] = client["apiKey"]
                    params["configVersion"] = client["configVersion"]
                    params["lastReadTimestamp"] = client["lastReadTimestamp"]
                    params["lastHistoricReadTimestamp"] = client[
                        "lastHistoricReadTimestamp"
                    ]
                    params["connexionFrom"] = "WS"

                    result["params"] = params

                    jeedomCom.send_change_immediate(result)
                else:
                    logging.debug(f"no api key found for client ${str(client)}")
            time.sleep(1)
    except Exception as err:
        logging.exception(err)


# ----------------------------------------------------------------------------
# ----------------------------------------------------------------------------

_log_level = "debug"
_socket_port = 58090
_websocket_port = 8090
_socket_host = "localhost"
_pidfile = "/tmp/JeedomConnectd.pid"
_apikey = ""
_callback = ""

parser = argparse.ArgumentParser(description="Daemon for Jeedom plugin")
parser.add_argument("--loglevel", help="Log Level for the daemon", type=str)
parser.add_argument("--user", help="username", type=str)
parser.add_argument("--pwd", help="password", type=str)
parser.add_argument("--socketport", help="Socket Port", type=int)
parser.add_argument("--callback", help="Value to write", type=str)
parser.add_argument("--apikey", help="Value to write", type=str)
parser.add_argument("--pid", help="Value to write", type=str)
args = parser.parse_args()

_log_level = args.loglevel
_socket_port = args.socketport
_websocket_port = 8090
_pidfile = args.pid
_apikey = args.apikey
_callback = args.callback

jeedom_utils.set_log_level(_log_level)

logging.info("Start daemon")
logging.info("Log level : " + str(_log_level))
logging.debug("Socket port : " + str(_socket_port))
logging.debug("PID file : " + str(_pidfile))


signal.signal(signal.SIGINT, handler)
signal.signal(signal.SIGTERM, handler)

try:
    jeedom_utils.write_pid(str(_pidfile))
    jeedomSocket = jeedom_socket(port=_socket_port, address=_socket_host)
    jeedomCom = jeedom_com(apikey=_apikey, url=_callback)

    server = WebsocketServer(host="0.0.0.0", port=_websocket_port)
    server.set_fn_message_received(onMessageReceived)
    server.set_fn_new_client(new_client)
    server.run_forever(True)

    async_GET_EVENTS = threading.Thread(target=async_worker, daemon=True)
    async_GET_EVENTS.start()

    logging.debug("final listening for jeedom socket")
    listen()


except Exception as e:
    logging.exception("Fatal error : " + str(e))
    logging.info(traceback.format_exc())
    shutdown()
