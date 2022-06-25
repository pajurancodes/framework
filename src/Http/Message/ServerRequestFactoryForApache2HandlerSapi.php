<?php

namespace PajuranCodes\Framework\Http\Message;

use const UPLOAD_ERR_OK;
use function trim;
use function ltrim;
use function substr;
use function strpos;
use function intval;
use function implode;
use function ucwords;
use function explode;
use function is_array;
use function urldecode;
use function is_string;
use function strtolower;
use function preg_match;
use function preg_split;
use function str_replace;
use function preg_replace;
use function function_exists;
use function str_starts_with;
use function array_key_exists;
use function apache_request_headers;
use Psr\Http\Message\{
    UriInterface,
    StreamInterface,
    UriFactoryInterface,
    UploadedFileInterface,
    ServerRequestInterface,
    StreamFactoryInterface,
    UploadedFileFactoryInterface,
};
use PajuranCodes\Http\Message\ServerRequestFactory;
use Fig\Http\Message\RequestMethodInterface as RequestMethod;

/**
 * A factory to create a server request, for the "apache2handler" SAPI.
 * 
 * @author pajurancodes
 */
class ServerRequestFactoryForApache2HandlerSapi extends ServerRequestFactory {

    /**
     * 
     * @param UriFactoryInterface $uriFactory A URI factory.
     * @param StreamFactoryInterface $streamFactory A stream factory.
     * @param UploadedFileFactoryInterface $uploadedFileFactory An uploaded file factory.
     */
    public function __construct(
        private readonly UriFactoryInterface $uriFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly UploadedFileFactoryInterface $uploadedFileFactory,
        StreamInterface $body,
        array $attributes = [],
        array $headers = [],
        null|array|object $parsedBody = null,
        array $queryParams = [],
        array $uploadedFiles = [],
        array $cookieParams = [],
        string $protocolVersion = '1.1'
    ) {
        parent::__construct(
            $body,
            $attributes,
            $headers,
            $parsedBody,
            $queryParams,
            $uploadedFiles,
            $cookieParams,
            $protocolVersion
        );
    }

    /**
     * Create a new server request from the given list of SAPI parameters.
     * 
     * Besides beeing passed as argument to the new server request 
     * instance, the SAPI parameters list serves as source of values 
     * for the other arguments of it.
     *
     * @param array $serverParams (optional) A list of SAPI parameters, used as argument to 
     * the new server request instance and as source of values for the other arguments of it.
     * @return ServerRequestInterface The server request.
     */
    public function createServerRequestFromArray(array $serverParams = []): ServerRequestInterface {
        if (!$serverParams) {
            $serverParams = $_SERVER;
        }

        $method = $this->buildMethod($serverParams);
        $this->headers = $this->buildHeaders($serverParams);
        $uri = $this->buildUri($serverParams, $this->headers);
        $this->parsedBody = $this->buildParsedBody($this->parsedBody, $method, $this->headers);
        $this->queryParams = $this->queryParams ?: $_GET;
        $this->uploadedFiles = $this->buildUploadedFiles($this->uploadedFiles ?: $_FILES);
        $this->cookieParams = $this->buildCookieParams($this->headers, $this->cookieParams);
        $this->protocolVersion = $this->buildProtocolVersion($serverParams, $this->protocolVersion);

        return parent::createServerRequest($method, $uri, $serverParams);
    }

    /**
     * Build the headers list.
     * 
     * This method either calls the apache_request_headers() function to 
     * fetch all HTTP request headers, or reads them one by one from the 
     * given server parameters list.
     *
     * The entries of the server parameters list relevant to this method
     * are those whose keys start with "HTTP_" and the two entries whose 
     * keys start with "CONTENT_" ("CONTENT_TYPE" and "CONTENT_LENGTH"). 
     * An example:
     * 
     *  [
     *      [HTTP_HOST] => localhost
     *      [HTTP_ACCEPT_LANGUAGE] => en-US,en;q=0.5
     *      [HTTP_DNT] => 1
     *      [HTTP_UPGRADE_INSECURE_REQUESTS] => 1
     *      ...
     *      [CONTENT_TYPE] => application/x-www-form-urlencoded
     *      [CONTENT_LENGTH] => 23
     *  ]
     *
     * The following operations are performed on each header name, 
     * before beeing passed to the resulting list of headers:
     * 
     *  - conversion to lower case;
     *  - replacing underscores ("_") with hyphens ("-");
     *  - uppercasing the first character of each word.
     *
     * @link https://tools.ietf.org/html/rfc3875#section-4.1 4.1. Request Meta-Variables
     * @link https://tools.ietf.org/html/rfc7230#section-3.2 Header Fields
     * @link https://tools.ietf.org/html/rfc7231#section-5 Request Header Fields
     *
     * @param array $serverParams A list of server parameters.
     * @return string[] The headers list.
     */
    private function buildHeaders(array $serverParams): array {
        if (function_exists('\apache_request_headers')) {
            return apache_request_headers();
        }

        $headers = [];

        foreach ($serverParams as $key => $value) {
            $keyToLower = strtolower($key);

            if (str_starts_with($keyToLower, 'http_')) { /* Headers starting with "HTTP_" */
                $headerNameToLower = substr($keyToLower, 5);
                $headerNameWithHyphens = str_replace('_', '-', $headerNameToLower);
                $headerNameUcwords = ucwords($headerNameWithHyphens, '-');

                $headers[$headerNameUcwords] = $this->buildHeaderValue($value);
            } elseif (str_starts_with($keyToLower, 'content_')) { /* Headers starting with "CONTENT_" */
                $headerNameWithHyphens = str_replace('_', '-', $keyToLower);
                $headerNameUcwords = ucwords($headerNameWithHyphens, '-');

                $headers[$headerNameUcwords] = $this->buildHeaderValue($value);
            }
        }

        return $headers;
    }

    /**
     * Build a header value as a comma 
     * separated string of the given values.
     *
     * @param string|string[]|null $value A list of header values.
     * @return string The header value as a comma separated string of the given values.
     */
    private function buildHeaderValue(string|array|null $value): string {
        if (!isset($value)) {
            $value = '';
        }

        if (is_array($value)) {
            $value = implode(',', $value);
        }

        if (is_string($value)) {
            $value = trim($value, ' ');
        }

        return $value;
    }

    /**
     * Retrieve a header value from a list of headers.
     *
     * @param string $name A case-insensitive header name.
     * @param string[] $headers A list of headers.
     * @return string The header value, or an empty string.
     */
    private function getHeader(string $name, array $headers): string {
        $value = '';

        foreach ($headers as $headerName => $headerValue) {
            if (strtolower($name) === strtolower($headerName)) {
                $value = $headerValue;
                break;
            }
        }

        return $value;
    }

    /**
     * Get a parameter value from a list of server parameters.
     * 
     * If the given parameter name is not found, 
     * then the given default value is returned.
     *
     * @param string $name A case-insensitive parameter name.
     * @param array $serverParams A list of server parameters.
     * @param mixed $default (optional) A default value.
     * @return mixed The parameter value, or the default one.
     */
    private function getServerParam(
        string $name,
        array $serverParams,
        mixed $default = null
    ): mixed {
        $value = $default;

        foreach ($serverParams as $serverParamName => $serverParamValue) {
            if (strtolower($name) === strtolower($serverParamName)) {
                $value = $serverParamValue;
                break;
            }
        }

        return $value;
    }

    /**
     * Check if a server parameter exists in a list of server parameters.
     * 
     * @param string $name A case-insensitive parameter name.
     * @param array $serverParams A list of server parameters.
     * @return bool True if the server parameter exists, or false otherwise.
     */
    private function hasServerParam(string $name, array $serverParams): bool {
        $found = false;

        foreach ($serverParams as $serverParamName => $serverParamValue) {
            if (strtolower($name) === strtolower($serverParamName)) {
                $found = true;
                break;
            }
        }

        return $found;
    }

    /**
     * Build the HTTP method of the request.
     * 
     * This method reads the parameter "REQUEST_METHOD" 
     * from the given list of server parameters.
     *
     * @link https://tools.ietf.org/html/rfc7231#section-4 4. Request Methods
     * @link https://www.php.net/manual/en/reserved.variables.server.php Reserved variables: $_SERVER
     *
     * @param array $serverParams A list of server parameters.
     * @return string The HTTP method of the request.
     */
    private function buildMethod(array $serverParams): string {
        return $this->getServerParam('REQUEST_METHOD', $serverParams, '');
    }

    /**
     * Build the URI.
     * 
     * A URI is composed of the following parts:
     * 
     *  URI = scheme ":" hier-part [ "?" query ] [ "#" fragment ]
     *  hier-part = "//" authority path
     *  authority = [ userinfo "@" ] host [ ":" port ]
     *  userinfo = username[:password]
     * 
     * {@internal Instead of creating a URI string from pieces and injecting 
     * it into a new Uri instance, it's better to create a new Uri instance 
     * with an empty string injected into it, and then call the corresponding 
     * "with*" methods. The reason is, that, in the "with*" methods, the proper 
     * validations and filters are already implemented. Otherwise it would be 
     * needed to implement them in this class too.}
     *
     * @link https://tools.ietf.org/html/rfc3986#section-3 Syntax Components
     * @link https://tools.ietf.org/html/rfc7230#section-2.7 Uniform Resource Identifiers
     * @link https://www.php-fig.org/psr/psr-17/meta/#56-why-does-requestfactoryinterfacecreaterequest-allow-a-string-uri 5.6 Why does RequestFactoryInterface::createRequest allow a string URI?
     *
     * @param array $serverParams A list of server parameters.
     * @param string[] $headers A list of headers.
     * @return UriInterface The URI.
     */
    private function buildUri(array $serverParams, array $headers): UriInterface {
        $uri = $this->uriFactory->createUri('');

        return $uri
                ->withScheme(
                    $this->getUriScheme($serverParams, $headers)
                )
                ->withUserInfo(
                    $this->getUriUser($serverParams)
                    , $this->getUriPassword($serverParams)
                )
                ->withHost(
                    $this->getUriHost($serverParams, $headers)
                )
                ->withPort(
                    $this->getUriPort($serverParams, $headers)
                )
                ->withPath(
                    $this->getUriPath($serverParams)
                )
                ->withQuery(
                    $this->getUriQuery($serverParams)
                )
                ->withFragment(
                    $this->getUriFragment()
                )
        ;
    }

    /**
     * Get the URI scheme.
     * 
     * This method reads the parameter "HTTPS" from the given 
     * list of server parameters and the header "X-Forwarded-Proto" 
     * from the given list of headers.
     *
     * @link https://www.php.net/manual/en/reserved.variables.server.php Reserved variables: $_SERVER
     * @link https://tools.ietf.org/html/rfc7239 Forwarded HTTP Extension
     * @link https://tools.ietf.org/html/rfc7239#section-5.4 Purpose of the X-Forwarded-Proto HTTP Header
     *
     * @param array $serverParams A list of server parameters.
     * @param string[] $headers A list of headers.
     * @return string The URI scheme.
     */
    private function getUriScheme(array $serverParams, array $headers): string {
        $scheme = 'http';

        $xForwardedProto = $this->getHeader('X-Forwarded-Proto', $headers);
        $https = $this->getServerParam('HTTPS', $serverParams);

        if (
            (!empty($xForwardedProto) && strtolower($xForwardedProto) === 'https') ||
            (isset($https) && strtolower($https) !== 'off')
        ) {
            $scheme = 'https';
        }

        return $scheme;
    }

    /**
     * Get the user name of the user information component of the URI.
     * 
     * This method reads the parameter "PHP_AUTH_USER" 
     * from the given list of server parameters.
     * 
     * The user name is included in the authority component of the URI:
     *  
     *  authority = [ userinfo "@" ] host [ ":" port ]
     *  userinfo = username[:password]
     * 
     * Use of the format "user:password" in the userinfo field is deprecated. See 
     * {@link https://tools.ietf.org/html/rfc3986#section-3.2.1 User information}
     *
     * @link https://tools.ietf.org/html/rfc3986#section-3.2.1 User information
     * @link https://www.php.net/manual/en/reserved.variables.server.php Reserved variables: $_SERVER
     *
     * @param array $serverParams A list of server parameters.
     * @return string The user name.
     */
    private function getUriUser(array $serverParams): string {
        return $this->getServerParam('PHP_AUTH_USER', $serverParams, '');
    }

    /**
     * Get the user password of the user information component of the URI.
     * 
     * This method reads the parameter "PHP_AUTH_PW" 
     * from the given list of server parameters.
     * 
     * The user password is included in the authority component of the URI:
     *  
     *  authority = [ userinfo "@" ] host [ ":" port ]
     *  userinfo = username[:password]
     * 
     * Use of the format "user:password" in the userinfo field is deprecated. See 
     * {@link https://tools.ietf.org/html/rfc3986#section-3.2.1 User information}
     *
     * @link https://tools.ietf.org/html/rfc3986#section-3.2.1 User information
     * @link https://www.php.net/manual/en/reserved.variables.server.php Reserved variables: $_SERVER
     *
     * @param array $serverParams A list of server parameters.
     * @return null|string The user password.
     */
    private function getUriPassword(array $serverParams): ?string {
        return $this->getServerParam('PHP_AUTH_PW', $serverParams);
    }

    /**
     * Get the URI host.
     * 
     * This method reads the parameters "SERVER_NAME" and "SERVER_ADDR" 
     * from the given list of server parameters and the header "Host" 
     * from the given list of headers, in order to extract the host 
     * part from it.
     * 
     * The host is included in the authority component of the URI:
     *  
     *  authority = [ userinfo "@" ] host [ ":" port ]
     *  host = IP-literal / IPv4address / reg-name
     *  IP-literal = "[" ( IPv6address / IPvFuture ) "]"
     * 
     * @link https://tools.ietf.org/html/rfc3986#section-3.2.2 Host
     * @link https://tools.ietf.org/html/rfc3513 Internet Protocol Version 6 (IPv6) Addressing Architecture
     * @link https://tools.ietf.org/html/rfc3986#section-3.2 Authority
     * @link https://tools.ietf.org/html/rfc7230#section-5.4 Host request header
     * @link https://www.php.net/manual/en/reserved.variables.server.php Reserved variables: $_SERVER
     *
     * @param array $serverParams A list of server parameters.
     * @param string[] $headers A list of headers.
     * @return string The URI host.
     */
    private function getUriHost(array $serverParams, array $headers): string {
        $host = '';

        $hostHeader = $this->getHeader('Host', $headers);
        $serverName = $this->getServerParam('SERVER_NAME', $serverParams, '');
        $serverAddr = $this->getServerParam('SERVER_ADDR', $serverParams, '');

        if (!empty($hostHeader)) {
            /*
             * Check if the host header is an IPv6 address, by performing
             * a regex match on the host header. Match result:
             *
             *  array (size=2)
             *      0 => string '[x:x:x:x:x:x:x:x]' (length=32)
             *      1 => string '[x:x:x:x:x:x:x:x]' (length=32)
             */
            $hostIsIPv6 = preg_match('/^(\[[:.0-9a-fA-F]+\])/', $hostHeader, $hostMatches);

            if ($hostIsIPv6 === 1) { /* IPv6address */
                $host = $hostMatches[1];
            } else { /* IPv4address or reg-name. */
                $host = explode(':', $hostHeader)[0];
            }
        } elseif (!empty($serverName)) {
            $host = $serverName;
        } elseif (!empty($serverAddr)) {
            $host = $serverAddr;
        }

        return $host;
    }

    /**
     * Get the URI port.
     * 
     * This method reads the parameter "SERVER_PORT" from the given 
     * list of server parameters and the headers "X-Forwarded-Port" 
     * and "Host" (for extracting the host part from it) from the 
     * given list of headers.
     * 
     * The port is included in the authority component of the URI:
     *  
     *  authority = [ userinfo "@" ] host [ ":" port ]
     *  host = IP-literal / IPv4address / reg-name
     *  IP-literal = "[" ( IPv6address / IPvFuture ) "]"
     *
     * @link https://tools.ietf.org/html/rfc3986#section-3.2.3 Port
     * @link https://tools.ietf.org/html/rfc3513 Internet Protocol Version 6 (IPv6) Addressing Architecture
     * @link https://tools.ietf.org/html/rfc3986#section-3.2 Authority
     * @link https://tools.ietf.org/html/rfc7230#section-5.4 Host request header
     * @link https://www.php.net/manual/en/reserved.variables.server.php Reserved variables: $_SERVER
     *
     * @param array $serverParams A list of server parameters.
     * @param string[] $headers A list of headers.
     * @return null|int The URI port.
     */
    private function getUriPort(array $serverParams, array $headers): ?int {
        $port = null;
        $portFound = false;

        $hostHeader = $this->getHeader('Host', $headers);
        $xForwardedPort = $this->getHeader('X-Forwarded-Port', $headers);
        $serverPort = $this->getServerParam('SERVER_PORT', $serverParams);

        if (!empty($hostHeader)) {
            /*
             * Check if the host header is an IPv6 address, by performing
             * a regex match on the host header. Match result:
             *
             *  array (size=4)
             *      0 => string '[x:x:x:x:x:x:x:x]:y' (length=36)
             *      1 => string '[x:x:x:x:x:x:x:x]' (length=32)
             *      2 => string ':' (length=1)
             *      3 => string 'y' (length=3)
             *
             * The elements with index 2 and 3 are set only
             * when they are provided in the host header.
             */
            $hostIsIPv6 = preg_match('/^(\[[:.0-9a-fA-F]+\])([:]*)([0-9]*)/', $hostHeader, $hostMatches);

            if ($hostIsIPv6 === 1) { /* IPv6address */
                if (!empty($hostMatches[2]) && !empty($hostMatches[3])) {
                    $port = intval($hostMatches[3]);
                    $portFound = true;
                }
            } else { /* IPv4address or reg-name. */
                $hostHeaderComponents = explode(':', $hostHeader);
                if (!empty($hostHeaderComponents[1])) {
                    $port = intval($hostHeaderComponents[1]);
                    $portFound = true;
                }
            }
        }

        if (!$portFound) {
            if (!empty($xForwardedPort)) {
                $port = intval($xForwardedPort);
            } elseif (!empty($serverPort)) {
                $port = intval($serverPort);
            }
        }

        return $port;
    }

    /**
     * Get the URI path.
     * 
     * This method reads the parameter "REQUEST_URI" 
     * from the given list of server parameters.
     * 
     * The returned value is of the form:
     *
     *  path-absolute = "/" [ segment-nz *( "/" segment ) ]
     *  segment = *pchar
     *  segment-nz = 1*pchar
     * 
     * @link https://tools.ietf.org/html/rfc3986#section-3.3 URI Path
     * @link https://www.php.net/manual/en/reserved.variables.server.php Reserved variables: $_SERVER
     *
     * @param array $serverParams A list of server parameters.
     * @return string The URI path.
     */
    private function getUriPath(array $serverParams): string {
        $path = '/';

        $requestUri = $this->getServerParam('REQUEST_URI', $serverParams, '');

        if (!empty($requestUri)) {
            $requestUriParts = explode('?', $requestUri, 2);
            $onlyPath = $requestUriParts[0];
            $path = '/' . ltrim($onlyPath, '/');
        }

        return $path;
    }

    /**
     * Get the query string of the URI.
     * 
     * This method reads the parameters "QUERY_STRING" and 
     * "REQUEST_URI" from the given list of server parameters.
     *
     * The query string has the following structure:
     * 
     *  query = *( pchar / "/" / "?" )
     *
     * @link https://tools.ietf.org/html/rfc3986#section-3.4 Query
     * @link https://tools.ietf.org/html/rfc3986#section-2 Characters
     * @link https://tools.ietf.org/html/rfc3986#section-2.1 Percent-Encoding
     * @link https://tools.ietf.org/html/rfc3986#section-2.4 When to Encode or Decode
     *
     * @param array $serverParams A list of server parameters.
     * @return string The query string.
     */
    private function getUriQuery(array $serverParams): string {
        $query = '';

        $queryString = $this->getServerParam('QUERY_STRING', $serverParams, '');
        $requestUri = $this->getServerParam('REQUEST_URI', $serverParams, '');

        if (!empty($queryString)) {
            $query = $queryString;
        } elseif (!empty($requestUri)) {
            $firstQuestionMarkOccurrence = strpos($requestUri, '?');

            if (
                $firstQuestionMarkOccurrence !== false /* Question mark found. */
            ) {
                $query = substr($requestUri, $firstQuestionMarkOccurrence + 1);
            }
        }

        return $query;
    }

    /**
     * Get the fragment component of the URI.
     * 
     * A URI fragment has the following structure:
     * 
     *  fragment = *( pchar / "/" / "?" / "#" )
     * 
     * URL fragments are not sent to the server over HTTP. 
     * Therefore, this method returns just an empty string.
     *
     * @link https://tools.ietf.org/html/rfc3986#section-3.5 Fragment
     * @link https://tools.ietf.org/html/rfc3986#section-2 Characters
     * @link https://tools.ietf.org/html/rfc3986#section-2.1 Percent-Encoding
     * @link https://tools.ietf.org/html/rfc3986#section-2.4 When to Encode or Decode
     *
     * @return string The URI fragment.
     */
    private function getUriFragment(): string {
        return '';
    }

    /**
     * Build the parsed body.
     *
     * If the request Content-Type is either application/x-www-form-urlencoded
     * or multipart/form-data, and the request method is POST, this method MUST
     * return the contents of $_POST.
     *
     * @see ServerRequestInterface::getParsedBody()
     * @see ServerRequestInterface::withParsedBody()
     * 
     * This method reads the header "Content-Type" 
     * from the given list of headers. It can have 
     * the following structure:
     * 
     *  Content-Type = media-type
     *  media-type = type "/" subtype *( OWS ";" OWS parameter )
     *
     * Examples of the header "Content-Type":
     *  
     *  - Content-Type: text/plain
     *  - Content-Type: text/html; charset=utf-8
     *  - Content-Type: multipart/form-data; boundary=something
     *
     * @link https://tools.ietf.org/html/rfc7231#section-3.1.1.5 Content-Type
     * @link https://tools.ietf.org/html/rfc7231#section-3.1.1.1 Media Type
     *
     * @param null|array|object $defaultParsedBody A list of deserialized body parameters 
     * used as the default parsed body.
     * @param string $method A HTTP Method.
     * @param string[] $headers A list of headers.
     * @return null|array|object A list of deserialized body parameters.
     */
    private function buildParsedBody(
        null|array|object $defaultParsedBody,
        string $method,
        array $headers
    ): null|array|object {
        $parsedBody = $defaultParsedBody ?? $_POST;

        if (strtolower($method) === strtolower(RequestMethod::METHOD_POST)) {
            $contentType = $this->getHeader('Content-Type', $headers);

            if (!empty($contentType)) {
                $contentTypeWithoutWhitespaces = preg_replace('/\s*;\s*/', ';', $contentType);
                $mediaType = explode(';', $contentTypeWithoutWhitespaces)[0];
                $mediaTypeToLower = strtolower($mediaType);

                if (
                    $mediaTypeToLower === 'application/x-www-form-urlencoded' ||
                    $mediaTypeToLower === 'multipart/form-data'
                ) {
                    $parsedBody = $_POST;
                }
            }
        }

        return $parsedBody;
    }

    /**
     * Build the list of uploaded files.
     *
     * @param array $uploadedFiles A list of uploaded files, either already 
     * normalized to a tree of UploadedFileInterface instances, or not yet normalized.
     * @return (UploadedFileInterface|array)[] An array tree of 
     * UploadedFileInterface instances, or an empty array.
     */
    private function buildUploadedFiles(array $uploadedFiles): array {
        return $this->normalizeUploadedFiles($uploadedFiles);
    }

    /**
     * Convert a list of uploaded files to a normalized tree, with 
     * each leaf an instance of UploadedFileInterface, if not already.
     *
     * {@internal This method iterates recursively through the uploaded files list, 
     * until the key "tmp_name" is found in an item. As soon as the key is found, 
     * it will be assumed that the item to which it belongs is an array with a 
     * structure similar to the one saved in the global variable $_FILES when 
     * a standard file upload is executed. Therefore, the item will be normalized.
     *  
     * A correct normalization of the uploaded files list means, that the key 
     * "tmp_name" is the one to be checked against, no other key instead.}
     *
     * @link https://www.php.net/manual/en/features.file-upload.php Handling file uploads
     * @link https://www.php.net/manual/en/reserved.variables.files.php $_FILES
     * @link https://www.php.net/manual/en/faq.html.php#faq.html.arrays  How do I create arrays in a HTML form?
     * @link https://tools.ietf.org/html/rfc1867 Form-based File Upload in HTML
     * @link https://tools.ietf.org/html/rfc2854 The 'text/html' Media Type
     *
     * @param array $uploadedFiles A list of uploaded files, either already 
     * normalized to a tree of UploadedFileInterface instances, or not yet normalized.
     * @return (UploadedFileInterface|array)[] An array tree of 
     * UploadedFileInterface instances, or an empty array.
     * @throws \InvalidArgumentException One of the values of the list of uploaded files 
     * is not an array, nor an instance of UploadedFileInterface.
     */
    private function normalizeUploadedFiles(array $uploadedFiles): array {
        $normalizedUploadedFiles = [];

        foreach ($uploadedFiles as $key => $item) {
            if (is_array($item)) {
                $normalizedUploadedFiles[$key] = array_key_exists('tmp_name', $item) ?
                    $this->normalizeFileUploadItem($item) :
                    $this->normalizeUploadedFiles($item)
                ;
            } elseif ($item instanceof UploadedFileInterface) {
                $normalizedUploadedFiles[$key] = $item;
            } else {
                throw new \InvalidArgumentException(
                        'The structure of the uploaded files list is not valid.'
                );
            }
        }

        return $normalizedUploadedFiles;
    }

    /**
     * Normalize a file upload item containing the key "tmp_name".
     * 
     * @link https://www.php.net/manual/en/features.file-upload.errors.php Error Messages Explained
     * 
     * @param (string|array)[] $item A file upload item.
     * @return UploadedFileInterface|array An instance of UploadedFileInterface, 
     * an array tree of UploadedFileInterface instances, or an empty array.
     * @throws \InvalidArgumentException The value of the key "tmp_name" is empty.
     */
    private function normalizeFileUploadItem(array $item): UploadedFileInterface|array {
        if (empty($item['tmp_name'])) {
            throw new \InvalidArgumentException(
                    'The value of the key "tmp_name" in the list of uploaded '
                    . 'files must be a non-empty value or a non-empty array.'
            );
        }

        $filename = $item['tmp_name'];

        /*
         * If the value of the key "tmp_name" is an array 
         * (meaning that multiple files were uploaded), 
         * then normalize and return it.
         */

        if (is_array($filename)) {
            return $this->normalizeFileUploadTmpNameItem($filename, $item);
        }

        /*
         * Otherwise, if the value of the key "tmp_name" is a string 
         * (meaning that the current item describes the only uploaded 
         * file), then normalize and return the current item.
         */

        $size = $item['size'] ?? null;
        $error = $item['error'] ?? UPLOAD_ERR_OK;
        $clientFilename = $item['name'] ?? null;
        $clientMediaType = $item['type'] ?? null;

        // Create an instance of UploadedFileInterface.
        $uploadedFile = $this->createUploadedFile(
            $filename,
            $size,
            $error,
            $clientFilename,
            $clientMediaType
        );

        return $uploadedFile;
    }

    /**
     * Normalize the value of the key "tmp_name" of a file upload item.
     * 
     * The given value of a key "tmp_name" is an array, 
     * meaning that multiple files were uploaded.
     * 
     * {@internal This method iterates recursively through the value 
     * of the "tmp_name" key, in order to build a tree structure, 
     * with each leaf an instance of UploadedFileInterface.}
     *
     * @param array $tmpNameItem The value of the "tmp_name" key of a file upload item.
     * @param array $currentElements An array holding the key/value pairs 
     * of the file upload item to which the key "tmp_name" belongs.
     * @return (UploadedFileInterface|array)[] An array tree of 
     * UploadedFileInterface instances, or an empty array.
     * @throws \InvalidArgumentException The keys "size" and "error" have invalid values.
     */
    private function normalizeFileUploadTmpNameItem(array $tmpNameItem, array $currentElements): array {
        $normalizedTmpNameItem = [];

        foreach ($tmpNameItem as $key => $value) {
            if (is_array($value)) {
                // Validate the values of the keys "size" and "error".
                if (
                    !isset($currentElements['size'][$key]) ||
                    !is_array($currentElements['size'][$key]) ||
                    !isset($currentElements['error'][$key]) ||
                    !is_array($currentElements['error'][$key])
                ) {
                    throw new \InvalidArgumentException(
                            'The structure of the items assigned to the keys "size" and "error" '
                            . 'in the list of uploaded files must be identical with the one of '
                            . 'the item assigned to the key "tmp_name". This restriction does '
                            . 'not apply to the leaf elements.'
                    );
                }

                // Get the array values.
                $filename = $currentElements['tmp_name'][$key];
                $size = $currentElements['size'][$key];
                $error = $currentElements['error'][$key];
                $clientFilename = isset($currentElements['name'][$key]) &&
                    is_array($currentElements['name'][$key]) ?
                    $currentElements['name'][$key] :
                    null;
                $clientMediaType = isset($currentElements['type'][$key]) &&
                    is_array($currentElements['type'][$key]) ?
                    $currentElements['type'][$key] :
                    null;

                /*
                 * Normalize recursively.
                 */
                $normalizedTmpNameItem[$key] = $this->normalizeFileUploadTmpNameItem($value, [
                    'tmp_name' => $filename,
                    'size' => $size,
                    'error' => $error,
                    'name' => $clientFilename,
                    'type' => $clientMediaType,
                ]);
            } else {
                /*
                 * Normalize to an instance of UploadedFileInterface.
                 */

                // Get the leaf values.
                $filename = $currentElements['tmp_name'][$key];
                $size = $currentElements['size'][$key] ?? null;
                $error = $currentElements['error'][$key] ?? UPLOAD_ERR_OK;
                $clientFilename = $currentElements['name'][$key] ?? null;
                $clientMediaType = $currentElements['type'][$key] ?? null;

                // Create an instance of UploadedFileInterface.
                $normalizedTmpNameItem[$key] = $this->createUploadedFile(
                    $filename,
                    $size,
                    $error,
                    $clientFilename,
                    $clientMediaType
                );
            }
        }

        return $normalizedTmpNameItem;
    }

    /**
     * Create an instance of UploadedFileInterface.
     * 
     * @link https://www.php.net/manual/en/features.file-upload.errors.php Error Messages Explained
     *
     * @param string $filename The filename of the uploaded file.
     * @param int|null $size (optional) The file size in bytes or null if unknown.
     * @param int $error (optional) The error associated with the uploaded file.
     * @param string|null $clientFilename (optional) The filename sent by the client, if any.
     * @param string|null $clientMediaType (optional) The media type sent by the client, if any.
     * @return UploadedFileInterface
     */
    private function createUploadedFile(
        string $filename,
        ?int $size = null,
        int $error = UPLOAD_ERR_OK,
        ?string $clientFilename = null,
        ?string $clientMediaType = null
    ): UploadedFileInterface {
        // Create a stream with read-only access.
        $stream = $this->streamFactory->createStreamFromFile($filename, 'rb');

        $uploadedFile = $this->uploadedFileFactory->createUploadedFile(
            $stream,
            $size,
            $error,
            $clientFilename,
            $clientMediaType
        );

        return $uploadedFile;
    }

    /**
     * Build the list of cookie parameters.
     * 
     * This method reads the header "Cookie" from the given list of headers. 
     * Its syntax is: "Cookie: {cookie-pairs}", with {cookie-pairs} a list 
     * of cookie-pairs. Each pair represents a cookie stored by the user 
     * agent and contains the cookie-name and the cookie-value which the 
     * user agent received in the header "Set-Cookie".
     *
     * @link https://tools.ietf.org/html/rfc6265#section-4.2 4.2. Cookie
     * @link https://tools.ietf.org/html/rfc6265#section-5.4 5.4. The Cookie Header
     *
     * @param string[] $headers A list of headers.
     * @param array $defaultCookieParams A default list of cookie parameters to return if not empty.
     * @return array A list of key/value pairs representing cookies.
     */
    private function buildCookieParams(array $headers, array $defaultCookieParams): array {
        if ($defaultCookieParams) {
            return $defaultCookieParams;
        }

        $cookieParams = [];

        $cookieHeader = $this->getHeader('Cookie', $headers);

        if (!empty($cookieHeader)) {
            $cookiePairs = preg_split('/[;]/', $cookieHeader);

            foreach ($cookiePairs as $cookiePair) {
                $trimmedCookiePair = trim($cookiePair);
                $cookiePairParts = preg_split('/[=]/', $trimmedCookiePair);

                $cookieName = urldecode($cookiePairParts[0]);
                $cookieValue = urldecode($cookiePairParts[1]);

                $cookieParams[$cookieName] = $cookieValue;
            }
        }

        return $cookieParams ?: $_COOKIE;
    }

    /**
     * Build the HTTP protocol version.
     * 
     * This method reads the parameter "SERVER_PROTOCOL" 
     * from the given list of server parameters.
     *
     * @link https://tools.ietf.org/html/rfc7230#section-2.6 Protocol Versioning
     * @link https://tools.ietf.org/html/rfc7230#section-3.1.1 Request Line
     * @link https://www.php.net/manual/en/reserved.variables.server.php Reserved variables: $_SERVER
     *
     * @param array $serverParams A list of server parameters.
     * @param string $defaultProtocolVersion A default HTTP protocol version.
     * @return string The HTTP protocol version.
     */
    private function buildProtocolVersion(
        array $serverParams,
        string $defaultProtocolVersion
    ): string {
        $protocolVersion = $defaultProtocolVersion;

        $serverProtocol = $this->getServerParam('SERVER_PROTOCOL', $serverParams, '');

        if (!empty($serverProtocol)) {
            $firstSlashOccurrence = strpos($serverProtocol, '/');

            if (
                $firstSlashOccurrence !== false /* Slash character found. */
            ) {
                $protocolVersion = substr($serverProtocol, $firstSlashOccurrence + 1);
            }
        }

        return $protocolVersion;
    }

}
