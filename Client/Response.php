<?php
/**
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

namespace MultiSafepay\ConnectCore\Client;

use Magento\Framework\Exception\InvalidArgumentException;

class Response
{
    /**
     * Create a new HTTP response array from a string
     *
     * @param string $response_str
     * @return array
     * @throws InvalidArgumentException
     */
    public function fromString(string $response_str): array
    {
        $statusCode = $this->extractCode($response_str);
        $headers = $this->extractHeaders($response_str);
        $body = $this->extractBody($response_str);
        $version = $this->extractVersion($response_str);
        $message = $this->extractMessage($response_str);

        return [
            'status_code' => $statusCode,
            'headers' => $headers,
            'body' => $body,
            'http_version' => $version,
            'message' => $message
        ];
    }

    /**
     * Extract the response code from a response string
     *
     * @param string $response_str
     * @return int
     */
    private function extractCode(string $response_str): int
    {
        preg_match("|^HTTP/[\d\.x]+ (\d+)|", $response_str, $m);

        if (isset($m[1])) {
            return (int) $m[1];
        } else {
            return 0;
        }
    }

    /**
     * Extract the headers from a response string
     *
     * @param string $response_str
     * @return  array
     * @throws InvalidArgumentException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function extractHeaders(string $response_str): array
    {
        $headers = [];

        // First, split body and headers. Headers are separated from the
        // message at exactly the sequence "\r\n\r\n"
        $parts = preg_split('|(?:\r\n){2}|m', $response_str, 2);
        if (! $parts[0]) {
            return $headers;
        }

        // Split headers part to lines; "\r\n" is the only valid line separator.
        $lines = explode("\r\n", $parts[0]);
        unset($parts);
        $last_header = null;

        foreach ($lines as $index => $line) {
            if ($index === 0 && preg_match('#^HTTP/\d+(?:\.\d+)? [1-5]\d+#', $line)) {
                // Status line; ignore
                continue;
            }

            if ($line == "") {
                // Done processing headers
                break;
            }

            // Locate headers like 'Location: ...' and 'Location:...' (note the missing space)
            if (preg_match("|^([a-zA-Z0-9`#$%&*+.^_\|!-]+):\s*(.*)|s", $line, $m)) {
                unset($last_header);
                $h_name  = strtolower($m[1]);
                $h_value = $m[2];
                $this->isValid($h_value);

                if (isset($headers[$h_name])) {
                    if (! is_array($headers[$h_name])) {
                        $headers[$h_name] = [$headers[$h_name]];
                    }

                    $headers[$h_name][] = ltrim($h_value);
                    $last_header = $h_name;
                    continue;
                }

                $headers[$h_name] = ltrim($h_value);
                $last_header = $h_name;
                continue;
            }

            // Identify header continuations
            if (preg_match("|^[ \t](.+)$|s", $line, $m) && $last_header !== null) {
                $h_value = trim($m[1]);
                if (is_array($headers[$last_header])) {
                    end($headers[$last_header]);
                    $last_header_key = key($headers[$last_header]);

                    $h_value = $headers[$last_header][$last_header_key] . $h_value;
                    $this->isValid($h_value);

                    $headers[$last_header][$last_header_key] = $h_value;
                    continue;
                }

                $h_value = $headers[$last_header] . $h_value;
                $this->isValid($h_value);

                $headers[$last_header] = $h_value;
                continue;
            }

            // Anything else is an error condition
            throw new InvalidArgumentException(__('Invalid header line detected'));
        }

        return $headers;
    }

    /**
     * Extract the body from a response string
     *
     * @param string $response_str
     * @return string
     */
    private function extractBody(string $response_str): string
    {
        $parts = preg_split('|(?:\r\n){2}|m', $response_str, 2);
        if (isset($parts[1])) {
            return $parts[1];
        }
        return '';
    }

    /**
     * Validate a header value.
     *
     * Per RFC 7230, only VISIBLE ASCII characters, spaces, and horizontal
     * tabs are allowed in values; only one whitespace character is allowed
     * between visible characters.
     *
     * @see http://en.wikipedia.org/wiki/HTTP_response_splitting
     * @param string $value
     * @throws InvalidArgumentException
     */
    private function isValid(string $value)
    {
        $length = strlen($value);
        for ($i = 0; $i < $length; $i++) {
            $ascii = ord($value[$i]);

            // Non-visible, non-whitespace characters
            // 9 === horizontal tab
            // 32-126, 128-254 === visible
            // 127 === DEL
            // 255 === null byte
            if (($ascii < 32 && $ascii !== 9)
                || $ascii === 127
                || $ascii > 254
            ) {
                throw new InvalidArgumentException(__('Invalid header value'));
            }
        }
    }

    /**
     * Extract the HTTP version from a response
     *
     * @param string $response_str
     * @return string
     */
    private function extractVersion(string $response_str): string
    {
        preg_match("|^HTTP/([\dx]+) \d+|", $response_str, $m);

        return $m[1] ?? '';
    }

    /**
     * Extract the HTTP message from a response
     *
     * @param string $response_str
     * @return string
     */
    private function extractMessage(string $response_str): string
    {
        preg_match("|^HTTP/[\dx]+ \d+ ([^\r\n]+)|", $response_str, $m);

        return $m[1] ?? '';
    }
}
