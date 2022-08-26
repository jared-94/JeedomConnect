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

import threading


class ThreadWithLoggedException(threading.Thread):
    """
    Similar to Thread but will log exceptions to passed logging.

    Args:
        logging: logging instance used to log any exception in child thread

    Exception is also reachable via <thread>.exception from the main thread.
    """

    DIVIDER = "*" * 80

    def __init__(self, *args, **kwargs):
        try:
            self.logging = kwargs.pop("logging")
        except KeyError:
            raise Exception("Missing 'logging' in kwargs")
        super().__init__(*args, **kwargs)
        self.exception = None

    def run(self):
        try:
            if self._target is not None:
                self._target(*self._args, **self._kwargs)
        except Exception as exception:
            thread = threading.current_thread()
            self.exception = exception
            self.logging.exception(
                f"{self.DIVIDER}\nException in child thread {thread}: {exception}\n{self.DIVIDER}"
            )
        finally:
            del self._target, self._args, self._kwargs


class WebsocketServerThread(ThreadWithLoggedException):
    """Dummy wrapper to make debug messages a bit more readable"""

    pass
