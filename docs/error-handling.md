# Description of the error handling components

## Resources:

- [PHP Manual > Language Reference > Errors](https://www.php.net/manual/en/language.errors.php)
- [Error Handling and Logging > Predefined Constants](https://www.php.net/manual/en/errorfunc.constants.php)
- [Runtime Configuration > error_reporting](https://www.php.net/manual/en/errorfunc.configuration.php#ini.error-reporting)

## The main components of the error handling layer

### 1. The error handler (`PajuranCodes\Framework\ErrorHandling\ErrorHandler`)

This class can be used as a *user-defined error handler function* set by the error handling function 
[set_error_handler](https://www.php.net/manual/en/function.set-error-handler.php). In this role, it 
is responsible for **handling all errors, except the fatal ones**.

When *invoked*, the instance of this class converts each thrown non-fatal error to an exception of type 
[ErrorException](https://www.php.net/manual/en/class.errorexception.php).

### 2. The exception handler (`PajuranCodes\Framework\ErrorHandling\ExceptionHandler`)

This class can be used as a *user-defined exception handler function* set by the error handling function 
[set_exception_handler](https://www.php.net/manual/en/function.set-exception-handler.php). In this 
role, it is responsible for **handling all exceptions**.

When *invoked*, the instance of this class passes the catched `Throwable` object to a so-called 
*throwable presenter* - in order to be prepared for presentation to the user, and it 
prints the returned response to the user, by using a *response emitter*.

A *throwable presenter* can display the exception informations in various formats: as a templated 
HTML code, in plain text, in Whoops library's design, etc.

Depending if the application is executed in *debug mode* or not, the exception informations 
are presented to the user as they are (when the *debug mode* is active), or just as a 
user-friendly message or page (when the *debug mode* is not active).

***Note:*** An alternative to invoking this class by `set_exception_handler()` could be to 
use a `try-catch` block at the entry point of the application, in order to catch and handle 
any raised exception.

### 3. The fatal error handler (`PajuranCodes\Framework\ErrorHandling\FatalErrorHandler`)

This class can be used as a *user-defined function* set by the function handling function 
[register_shutdown_function](https://www.php.net/manual/en/function.register-shutdown-function.php). 
In this role, it is responsible for **handling all fatal errors**.

Fatal errors are those error types which can not be handled with a user-defined function 
set by an error handling function (like `set_error_handler()`, for example):

- E_ERROR
- E_PARSE
- E_CORE_ERROR
- E_CORE_WARNING
- E_COMPILE_ERROR
- E_COMPILE_WARNING

The object of type `FatalErrorHandler` is *invoked* on shutdown, e.g. after script execution 
finishes or [exit](https://www.php.net/manual/en/function.exit.php) is called. It reads and 
handles the last occurred error by passing it to a *throwable presenter* instance. The 
returned response is finally printed to the user with a *response emitter*.

***Note:*** In some cases, multiple types of errors are thrown at the same time. For example, 
the control structure [require](https://www.php.net/manual/en/function.require.php) produces 
a fatal [E_COMPILE_ERROR](https://www.php.net/manual/en/errorfunc.constants.php) level error 
upon failure (e.g. if it cannot find the given file), along with an additional 
[E_WARNING](https://www.php.net/manual/en/errorfunc.constants.php) (signalizing that the file 
cannot be accessed).