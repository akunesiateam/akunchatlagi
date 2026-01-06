<?php

namespace Modules\Ecommerce\Traits;

use App\Traits\WhatsApp;
use Netflie\WhatsAppCloudApi\Message\Template\Component;

trait EcommerceWebhook
{
    use WhatsApp;

    /**
     * Send a template message using the WhatsApp Cloud API
     *
     * @param  string  $to  Recipient phone number
     * @param  array  $template_data  Data for the template message
     * @param  array  $responsedata  Data for the template message
     * @param  string  $type  Type of the message, default is 'campaign'
     * @param  string|null  $fromNumber  Optional sender phone number
     * @return array Response containing status, log data, and any response data or error message
     */
    public function sendWebhookTemplateMessage($to, $template_data, $responsedata, $type = 'webhook', $fromNumber = null)
    {
        $rel_type = $template_data['rel_type'];
        $header_data = [];
        if ($template_data['header_data_format'] == 'TEXT') {
            $header_data = $this->parseWebhookText($responsedata, 'header', $template_data, 'array');
        }
        $body_data = $this->parseWebhookText($responsedata, 'body', $template_data, 'array');
        $buttons_data = $this->parseWebhookText($responsedata, 'footer', $template_data, 'array');

        $component_header = $component_body = $component_buttons = [];
        $file_link = asset('storage/'.$template_data['filename']);

        $template_buttons_data = json_decode($template_data['buttons_data']);
        $is_flow = false;
        if (! empty($template_buttons_data)) {
            $button_types = array_column($template_buttons_data, 'type');
            $is_flow = in_array('FLOW', $button_types);
        }

        $component_header = $this->buildHeaderComponent($template_data, $file_link, $header_data);
        $component_body = $this->buildTextComponent($body_data);
        $component_buttons = $this->buildTextComponent($buttons_data);

        if ($is_flow) {
            $buttons = json_decode($template_data['buttons_data']);
            $flow_id = reset($buttons)->flow_id;
            $component_buttons[] = [
                'type' => 'button',
                'sub_type' => 'FLOW',
                'index' => 0,
                'parameters' => [
                    [
                        'type' => 'action',
                        'action' => [
                            'flow_token' => json_encode(['flow_id' => $flow_id, 'rel_data' => $template_data['flow_action_data'] ?? []]),
                        ],
                    ],
                ],
            ];
        }

        $whatsapp_cloud_api = $this->loadConfig($fromNumber);

        try {
            $components = new Component($component_header, $component_body, $component_buttons);
            $result = $whatsapp_cloud_api->sendTemplate($to, $template_data['template_name'], $template_data['language'], $components);
            $status = true;
            $data = json_decode($result->body());
            $responseCode = $result->httpStatusCode();
            $responseData = json_encode($result->decodedBody());
            $rawData = json_encode($result->request()->body());
        } catch (\Netflie\WhatsAppCloudApi\Response\ResponseException $th) {
            $status = false;
            $message = $th->responseData()['error']['message'] ?? $th->rawResponse() ?? json_decode($th->getMessage());
            $responseCode = $th->httpStatusCode();
            $responseData = json_encode($message);
            $rawData = json_encode([]);

            whatsapp_log('Error sending template: '.$message, 'error', [
                'to' => $to,
                'template_name' => $template_data['template_name'],
                'language' => $template_data['language'],
                'response_code' => $responseCode,
                'response_data' => $responseData,
                'raw_data' => $rawData,
            ], $th);
        }

        $log_data = [
            'response_code' => $responseCode,
            'category' => $type,
            'category_id' => $template_data['relation_id'],
            'rel_type' => $rel_type,
            'rel_id' => $template_data['relation_id'],
            'category_params' => json_encode(['templateId' => $template_data['template_id'], 'message' => $message ?? '']),
            'response_data' => $responseData,
            'raw_data' => $rawData,
            'phone_number_id' => get_setting('whatsapp.wm_default_phone_number_id'),
            'access_token' => get_setting('whatsapp.wm_access_token'),
            'business_account_id' => get_setting('whatsapp.wm_business_account_id'),
        ];

        return ['status' => $status, 'log_data' => $log_data, 'data' => $data ?? [], 'message' => $message->error->message ?? ''];
    }

    /**
     * Parse text with merge fields
     *
     * @param  string  $responsedata
     * @param  string  $type
     * @param  array  $data
     * @param  string  $return_type
     * @return string|array
     */
    public function parseWebhookText($responsedata, $type, $data, $return_type = 'text')
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
