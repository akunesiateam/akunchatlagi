<?php

namespace Modules\Ecommerce\Helpers;

if (! function_exists('parseWebhookText')) {
    /**
     * Parse text with merge fields
     *
     * @param  string  $responsedata
     * @param  string  $type
     * @param  array  $data
     * @param  string  $return_type
     * @return string|array
     */
    function parseWebhookText($responsedata, $type, $data, $return_type = 'text')
    {
        // Default to empty array if params are not set
        $data["{$type}_params"] = $data["{$type}_params"] ?? '[]';

        // Convert @{...} to {...} for consistency
        $data["{$type}_params"] = preg_replace('/@{(.*?)}/', '{$1}', $data["{$type}_params"]);

        // Parse the parameters
        $params = json_decode($data["{$type}_params"], true) ?? [];
        $paramsCount = $data["{$type}_params_count"] ?? count($params);
        $index = ($return_type == 'text') ? 1 : 0;
        $last = ($return_type == 'text') ? $paramsCount : $paramsCount - 1;
        $parsedData = [];

        // Function to fetch nested value from array using dot notation
        $getValue = function ($array, $path) {
            $segments = explode('.', $path);
            foreach ($segments as $segment) {
                if (is_array($array) && array_key_exists($segment, $array)) {
                    $array = $array[$segment];
                } else {
                    return ''; // Return empty string if not found
                }
            }

            return is_array($array) ? json_encode($array) : $array;
        };

        // Process each parameter
        for ($i = $index; $i <= $last; $i++) {
            $parsedText = is_array($params) ? array_map(function ($body) use ($responsedata, $getValue) {
                // Replace placeholders like {billing.first_name}
                return preg_replace_callback('/{(.*?)}/', function ($matches) use ($responsedata, $getValue) {
                    return $getValue($responsedata, $matches[1]);
                }, $body);
            }, $params) : [1 => trim($data["{$type}_params"] ?? '')];

            // Replace template variables like {{1}}, {{2}} etc. in message
            if ($return_type == 'text' && ! empty($data["{$type}_message"])) {
                $data["{$type}_message"] = str_replace("{{{$i}}}", ! empty($parsedText[$i - 1]) ? $parsedText[$i - 1] : ' ', $data["{$type}_message"]);
            }

            $parsedData[] = ! empty($parsedText[$i]) ? trim($parsedText[$i]) : ' ';
        }

        return ($return_type == 'text') ? $data["{$type}_message"] : $parsedData;
    }
}
