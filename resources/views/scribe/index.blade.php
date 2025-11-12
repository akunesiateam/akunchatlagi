<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="IE=edge,chrome=1" http-equiv="X-UA-Compatible">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>WhatsApp Marketing SaaS API Documentation</title>

    <link href="https://fonts.googleapis.com/css?family=Open+Sans&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset("/vendor/scribe/css/theme-default.style.css") }}" media="screen">
    <link rel="stylesheet" href="{{ asset("/vendor/scribe/css/theme-default.print.css") }}" media="print">

    <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.10/lodash.min.js"></script>

    <link rel="stylesheet"
          href="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/styles/obsidian.min.css">
    <script src="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/highlight.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jets/0.14.1/jets.min.js"></script>

    <style id="language-style">
        /* starts out as display none and is replaced with js later  */
                    body .content .bash-example code { display: none; }
                    body .content .javascript-example code { display: none; }
                    body .content .php-example code { display: none; }
                    body .content .python-example code { display: none; }
            </style>

    <script>
        var tryItOutBaseUrl = "https://whatsmark-saas.test";
        var useCsrf = Boolean();
        var csrfUrl = "/sanctum/csrf-cookie";
    </script>
    <script src="{{ asset("/vendor/scribe/js/tryitout-5.2.1.js") }}"></script>

    <script src="{{ asset("/vendor/scribe/js/theme-default-5.2.1.js") }}"></script>

</head>

<body data-languages="[&quot;bash&quot;,&quot;javascript&quot;,&quot;php&quot;,&quot;python&quot;]">

<a href="#" id="nav-button">
    <span>
        MENU
        <img src="{{ asset("/vendor/scribe/images/navbar.png") }}" alt="navbar-image"/>
    </span>
</a>
<div class="tocify-wrapper">
            <img src="./img/light_logo.png" alt="logo" class="logo" style="padding-top: 10px;" width="100%"/>
    
            <div class="lang-selector">
                                            <button type="button" class="lang-button" data-language-name="bash">bash</button>
                                            <button type="button" class="lang-button" data-language-name="javascript">javascript</button>
                                            <button type="button" class="lang-button" data-language-name="php">php</button>
                                            <button type="button" class="lang-button" data-language-name="python">python</button>
                    </div>
    
    <div class="search">
        <input type="text" class="search" id="input-search" placeholder="Search">
    </div>

    <div id="toc">
                    <ul id="tocify-header-introduction" class="tocify-header">
                <li class="tocify-item level-1" data-unique="introduction">
                    <a href="#introduction">Introduction</a>
                </li>
                                    <ul id="tocify-subheader-introduction" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="welcome-to-whatsapp-marketing-saas-api">
                                <a href="#welcome-to-whatsapp-marketing-saas-api">Welcome to WhatsApp Marketing SaaS API</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="getting-started">
                                <a href="#getting-started">Getting Started</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="api-features">
                                <a href="#api-features">API Features</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="important-notes">
                                <a href="#important-notes">Important Notes</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-authenticating-requests" class="tocify-header">
                <li class="tocify-item level-1" data-unique="authenticating-requests">
                    <a href="#authenticating-requests">Authenticating requests</a>
                </li>
                                    <ul id="tocify-subheader-authenticating-requests" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="authentication-process">
                                <a href="#authentication-process">Authentication Process</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="token-expiration-refresh">
                                <a href="#token-expiration-refresh">Token Expiration & Refresh</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="security-best-practices">
                                <a href="#security-best-practices">Security Best Practices</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-contact-management" class="tocify-header">
                <li class="tocify-item level-1" data-unique="contact-management">
                    <a href="#contact-management">Contact Management</a>
                </li>
                                    <ul id="tocify-subheader-contact-management" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="contact-management-POSTapi-v1--subdomain--contacts">
                                <a href="#contact-management-POSTapi-v1--subdomain--contacts">Create Contact</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="contact-management-GETapi-v1--subdomain--contacts">
                                <a href="#contact-management-GETapi-v1--subdomain--contacts">List Contacts</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="contact-management-GETapi-v1--subdomain--contacts--id-">
                                <a href="#contact-management-GETapi-v1--subdomain--contacts--id-">Get Contact Details</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="contact-management-PUTapi-v1--subdomain--contacts--id-">
                                <a href="#contact-management-PUTapi-v1--subdomain--contacts--id-">Update Contact</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="contact-management-DELETEapi-v1--subdomain--contacts--id-">
                                <a href="#contact-management-DELETEapi-v1--subdomain--contacts--id-">Delete Contact</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-status-management" class="tocify-header">
                <li class="tocify-item level-1" data-unique="status-management">
                    <a href="#status-management">Status Management</a>
                </li>
                                    <ul id="tocify-subheader-status-management" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="status-management-POSTapi-v1--subdomain--statuses">
                                <a href="#status-management-POSTapi-v1--subdomain--statuses">Create Status</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="status-management-GETapi-v1--subdomain--statuses">
                                <a href="#status-management-GETapi-v1--subdomain--statuses">List Statuses</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="status-management-GETapi-v1--subdomain--statuses--id-">
                                <a href="#status-management-GETapi-v1--subdomain--statuses--id-">Get Status Details</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="status-management-PUTapi-v1--subdomain--statuses--id-">
                                <a href="#status-management-PUTapi-v1--subdomain--statuses--id-">Update Status</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="status-management-DELETEapi-v1--subdomain--statuses--id-">
                                <a href="#status-management-DELETEapi-v1--subdomain--statuses--id-">Delete Status</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-source-management" class="tocify-header">
                <li class="tocify-item level-1" data-unique="source-management">
                    <a href="#source-management">Source Management</a>
                </li>
                                    <ul id="tocify-subheader-source-management" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="source-management-POSTapi-v1--subdomain--sources">
                                <a href="#source-management-POSTapi-v1--subdomain--sources">Create Source</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="source-management-GETapi-v1--subdomain--sources">
                                <a href="#source-management-GETapi-v1--subdomain--sources">List Sources</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="source-management-GETapi-v1--subdomain--sources--id-">
                                <a href="#source-management-GETapi-v1--subdomain--sources--id-">Get Source Details</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="source-management-PUTapi-v1--subdomain--sources--id-">
                                <a href="#source-management-PUTapi-v1--subdomain--sources--id-">Update Source</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="source-management-DELETEapi-v1--subdomain--sources--id-">
                                <a href="#source-management-DELETEapi-v1--subdomain--sources--id-">Delete Source</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-group-management" class="tocify-header">
                <li class="tocify-item level-1" data-unique="group-management">
                    <a href="#group-management">Group Management</a>
                </li>
                                    <ul id="tocify-subheader-group-management" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="group-management-GETapi-v1--subdomain--groups">
                                <a href="#group-management-GETapi-v1--subdomain--groups">List Groups</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="group-management-GETapi-v1--subdomain--groups--id-">
                                <a href="#group-management-GETapi-v1--subdomain--groups--id-">Get Groups Details</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-template-management" class="tocify-header">
                <li class="tocify-item level-1" data-unique="template-management">
                    <a href="#template-management">Template Management</a>
                </li>
                                    <ul id="tocify-subheader-template-management" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="template-management-GETapi-v1--subdomain--templates">
                                <a href="#template-management-GETapi-v1--subdomain--templates">List Templates</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="template-management-GETapi-v1--subdomain--templates--id-">
                                <a href="#template-management-GETapi-v1--subdomain--templates--id-">Get Templates Details</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-template-bot-management" class="tocify-header">
                <li class="tocify-item level-1" data-unique="template-bot-management">
                    <a href="#template-bot-management">Template Bot Management</a>
                </li>
                                    <ul id="tocify-subheader-template-bot-management" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="template-bot-management-GETapi-v1--subdomain--templatebots">
                                <a href="#template-bot-management-GETapi-v1--subdomain--templatebots">List TemplateBot</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="template-bot-management-GETapi-v1--subdomain--templatebots--id-">
                                <a href="#template-bot-management-GETapi-v1--subdomain--templatebots--id-">Get TemplateBot Details</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-message-bot-management" class="tocify-header">
                <li class="tocify-item level-1" data-unique="message-bot-management">
                    <a href="#message-bot-management">Message Bot Management</a>
                </li>
                                    <ul id="tocify-subheader-message-bot-management" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="message-bot-management-GETapi-v1--subdomain--messagebots">
                                <a href="#message-bot-management-GETapi-v1--subdomain--messagebots">List Messagebots</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="message-bot-management-GETapi-v1--subdomain--messagebots--id-">
                                <a href="#message-bot-management-GETapi-v1--subdomain--messagebots--id-">Get Messagebots Details</a>
                            </li>
                                                                        </ul>
                            </ul>
            </div>

    <ul class="toc-footer" id="toc-footer">
                    <li style="padding-bottom: 5px;"><a href="{{ route("scribe.postman") }}">View Postman collection</a></li>
                            <li style="padding-bottom: 5px;"><a href="{{ route("scribe.openapi") }}">View OpenAPI spec</a></li>
                <li><a href="http://github.com/knuckleswtf/scribe">Documentation powered by Scribe ✍</a></li>
    </ul>

    <ul class="toc-footer" id="last-updated">
        <li>Last updated: July 22, 2025</li>
    </ul>
</div>

<div class="page-wrapper">
    <div class="dark-box"></div>
    <div class="content">
        <h1 id="introduction">Introduction</h1>
<p>Comprehensive API documentation for WhatsApp Marketing SaaS platform. Manage campaigns, contacts, bot flows, and WhatsApp integrations.</p>
<aside>
    <strong>Base URL</strong>: <code>https://whatsmark-saas.test</code>
</aside>
<h2 id="welcome-to-whatsapp-marketing-saas-api">Welcome to WhatsApp Marketing SaaS API</h2>
<p>This API provides a powerful set of tools for integrating WhatsApp marketing capabilities into your applications. With our API, you can:</p>
<ul>
<li>Manage contacts and contact groups</li>
<li>Send templated messages and interactive messages</li>
<li>Create and manage WhatsApp bots</li>
<li>Track message delivery and responses</li>
<li>Automate customer journeys</li>
</ul>
<h2 id="getting-started">Getting Started</h2>
<p>Follow these steps to quickly integrate with our API:</p>
<h3 id="step-1-create-an-account">Step 1: Create an Account</h3>
<p>Before you can use the API, you need to:</p>
<ol>
<li>Register for an account at <a href="https://whatsmark-saas.test/register">our website</a></li>
<li>Verify your email address</li>
<li>Set up your tenant subdomain</li>
<li>Generate your API credentials from the dashboard</li>
</ol>
<h3 id="step-2-authentication">Step 2: Authentication</h3>
<p>This API uses Bearer token authentication. You can obtain your token by making a POST request to the login endpoint:</p>
<pre><code class="language-bash">curl -X POST https://whatsmark-saas.test/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "your-email@example.com", "password": "your-password"}'</code></pre>
<p>The response will include your access token:</p>
<pre><code class="language-json">{
  "access_token": "YOUR_ACCESS_TOKEN",
  "token_type": "Bearer",
  "expires_in": 3600
}</code></pre>
<p>Include this token in all subsequent requests:</p>
<pre><code>Authorization: Bearer YOUR_ACCESS_TOKEN</code></pre>
<h3 id="step-3-tenant-context">Step 3: Tenant Context</h3>
<p>All API endpoints that handle tenant-specific data require a subdomain in the URL path:</p>
<pre><code>https://whatsmark-saas.test/api/v1/{your-subdomain}/contacts</code></pre>
<p>Replace <code>{your-subdomain}</code> with your actual tenant subdomain.</p>
<h3 id="step-4-making-api-requests">Step 4: Making API Requests</h3>
<p>Once authenticated, you can start making API calls. Here's an example of listing all contacts:</p>
<pre><code class="language-bash">curl -X GET https://whatsmark-saas.test/api/v1/your-subdomain/contacts \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Content-Type: application/json"</code></pre>
<h2 id="api-features">API Features</h2>
<ul>
<li><strong>RESTful Design</strong>: Our API follows RESTful principles for predictable resource-oriented URLs</li>
<li><strong>JSON Responses</strong>: All API responses are returned in JSON format</li>
<li><strong>Pagination</strong>: List endpoints support pagination for handling large datasets</li>
<li><strong>Error Handling</strong>: Clear error messages with appropriate HTTP status codes</li>
<li><strong>Rate Limiting</strong>: 100 requests per minute per API key</li>
</ul>
<h2 id="important-notes">Important Notes</h2>
<ul>
<li><strong>Base URL</strong>: All API requests should be made to: <code>https://whatsmark-saas.test/api/v1/{subdomain}/</code></li>
<li><strong>Content Type</strong>: Use <code>application/json</code> for request and response bodies</li>
<li><strong>Date Format</strong>: All dates are in ISO 8601 format (e.g., <code>2025-07-22T14:30:00Z</code>)</li>
<li><strong>Rate Limits</strong>: Check the <code>X-RateLimit-Remaining</code> header to monitor your usage</li>
<li><strong>Errors</strong>: Error responses include <code>status</code> and <code>message</code> fields to help troubleshoot issues</li>
</ul>
<aside>As you scroll, you'll see code examples for working with the API in different programming languages in the dark area to the right (or as part of the content on mobile).
You can switch the language used with the tabs at the top right (or from the nav menu at the top left on mobile).</aside>

        <h1 id="authenticating-requests">Authenticating requests</h1>
<h2 id="authentication-process">Authentication Process</h2>
<p>The WhatsApp Marketing SaaS API uses JWT (JSON Web Tokens) for authentication. Follow these steps to authenticate your requests:</p>
<h3 id="1-obtain-an-access-token">1. Obtain an Access Token</h3>
<p>Make a POST request to the login endpoint with your credentials:</p>
<pre><code class="language-bash">curl -X POST https://whatsmark-saas.test/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "your-email@example.com", "password": "your-password"}'</code></pre>
<h3 id="2-store-the-token">2. Store the Token</h3>
<p>The response will include your access token:</p>
<pre><code class="language-json">{
  "status": "success",
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
  "token_type": "Bearer",
  "expires_in": 3600
}</code></pre>
<p>This token is valid for 1 hour (3600 seconds) by default.</p>
<h3 id="3-use-the-token">3. Use the Token</h3>
<p>Include the token in the <code>Authorization</code> header with all API requests:</p>
<pre><code>Authorization: Bearer {YOUR_ACCESS_TOKEN}</code></pre>
<p>Replace <code>{YOUR_ACCESS_TOKEN}</code> with the actual token you received.</p>
<h2 id="token-expiration-refresh">Token Expiration &amp; Refresh</h2>
<p>When your token expires, you'll receive a 401 Unauthorized response. You can either:</p>
<ol>
<li>Generate a new token with the login endpoint</li>
<li>Use the refresh token endpoint (if refresh tokens are enabled):</li>
</ol>
<pre><code class="language-bash">curl -X POST https://whatsmark-saas.test/api/auth/refresh \
  -H "Authorization: Bearer {YOUR_EXPIRED_TOKEN}"</code></pre>
<h2 id="security-best-practices">Security Best Practices</h2>
<ul>
<li>Keep your API tokens secure and never share them publicly</li>
<li>Use HTTPS for all API requests to encrypt token transmission</li>
<li>Implement token refresh logic in your application</li>
<li>Create separate API tokens for different integrations</li>
<li>Revoke tokens that are no longer needed</li>
</ul>
<p>All authenticated endpoints in this documentation are marked with a <code>requires authentication</code> badge.</p>

        <h1 id="contact-management">Contact Management</h1>

    <p>APIs for managing contacts within the tenant context</p>

                                <h2 id="contact-management-POSTapi-v1--subdomain--contacts">Create Contact</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Create a new contact or lead in the system.</p>

<span id="example-requests-POSTapi-v1--subdomain--contacts">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://whatsmark-saas.test/api/v1/architecto/contacts" \
    --header "Authorization: Bearer {YOUR_ACCESS_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"firstname\": \"John\",
    \"lastname\": \"Doe\",
    \"company\": \"Acme Corp\",
    \"type\": \"lead\",
    \"email\": \"john@example.com (must be unique)\",
    \"phone\": \"+911234567890 (must be unique)\",
    \"status_id\": 1,
    \"source_id\": 1,
    \"description\": \"Potential client from website\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://whatsmark-saas.test/api/v1/architecto/contacts"
);

const headers = {
    "Authorization": "Bearer {YOUR_ACCESS_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "firstname": "John",
    "lastname": "Doe",
    "company": "Acme Corp",
    "type": "lead",
    "email": "john@example.com (must be unique)",
    "phone": "+911234567890 (must be unique)",
    "status_id": 1,
    "source_id": 1,
    "description": "Potential client from website"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'https://whatsmark-saas.test/api/v1/architecto/contacts';
$response = $client-&gt;post(
    $url,
    [
        'headers' =&gt; [
            'Authorization' =&gt; 'Bearer {YOUR_ACCESS_TOKEN}',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
        'json' =&gt; [
            'firstname' =&gt; 'John',
            'lastname' =&gt; 'Doe',
            'company' =&gt; 'Acme Corp',
            'type' =&gt; 'lead',
            'email' =&gt; 'john@example.com (must be unique)',
            'phone' =&gt; '+911234567890 (must be unique)',
            'status_id' =&gt; 1,
            'source_id' =&gt; 1,
            'description' =&gt; 'Potential client from website',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>


<div class="python-example">
    <pre><code class="language-python">import requests
import json

url = 'https://whatsmark-saas.test/api/v1/architecto/contacts'
payload = {
    "firstname": "John",
    "lastname": "Doe",
    "company": "Acme Corp",
    "type": "lead",
    "email": "john@example.com (must be unique)",
    "phone": "+911234567890 (must be unique)",
    "status_id": 1,
    "source_id": 1,
    "description": "Potential client from website"
}
headers = {
  'Authorization': 'Bearer {YOUR_ACCESS_TOKEN}',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}

response = requests.request('POST', url, headers=headers, json=payload)
response.json()</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1--subdomain--contacts">
            <blockquote>
            <p>Example response (201, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;success&quot;,
    &quot;message&quot;: &quot;Contact created successfully&quot;,
    &quot;data&quot;: {
        &quot;id&quot;: 1,
        &quot;firstname&quot;: &quot;John&quot;,
        &quot;lastname&quot;: &quot;Doe&quot;,
        &quot;email&quot;: &quot;john@example.com&quot;,
        &quot;created_at&quot;: &quot;2024-02-08 10:00:00&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, validation error):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;error&quot;,
    &quot;message&quot;: &quot;Validation failed&quot;,
    &quot;errors&quot;: {
        &quot;email&quot;: [
            &quot;The email field must be a valid email address.&quot;
        ],
        &quot;phone&quot;: [
            &quot;The phone field is required.&quot;
        ]
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1--subdomain--contacts" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1--subdomain--contacts"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1--subdomain--contacts"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1--subdomain--contacts" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1--subdomain--contacts">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1--subdomain--contacts" data-method="POST"
      data-path="api/v1/{subdomain}/contacts"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1--subdomain--contacts', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1--subdomain--contacts"
                    onclick="tryItOut('POSTapi-v1--subdomain--contacts');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1--subdomain--contacts"
                    onclick="cancelTryOut('POSTapi-v1--subdomain--contacts');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1--subdomain--contacts"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/{subdomain}/contacts</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-v1--subdomain--contacts"
               value="Bearer {YOUR_ACCESS_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_ACCESS_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1--subdomain--contacts"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1--subdomain--contacts"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>subdomain</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="subdomain"                data-endpoint="POSTapi-v1--subdomain--contacts"
               value="architecto"
               data-component="url">
    <br>
<p>Example: <code>architecto</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>firstname</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="firstname"                data-endpoint="POSTapi-v1--subdomain--contacts"
               value="John"
               data-component="body">
    <br>
<p>The first name of the contact. Example: <code>John</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>lastname</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="lastname"                data-endpoint="POSTapi-v1--subdomain--contacts"
               value="Doe"
               data-component="body">
    <br>
<p>The last name of the contact. Example: <code>Doe</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>company</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="text" style="display: none"
                              name="company"                data-endpoint="POSTapi-v1--subdomain--contacts"
               value="Acme Corp"
               data-component="body">
    <br>
<p>The company name. Example: <code>Acme Corp</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="type"                data-endpoint="POSTapi-v1--subdomain--contacts"
               value="lead"
               data-component="body">
    <br>
<p>The contact type (lead/customer). Example: <code>lead</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="POSTapi-v1--subdomain--contacts"
               value="john@example.com (must be unique)"
               data-component="body">
    <br>
<p>The contact's email address. Example: <code>john@example.com (must be unique)</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>phone</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="phone"                data-endpoint="POSTapi-v1--subdomain--contacts"
               value="+911234567890 (must be unique)"
               data-component="body">
    <br>
<p>The contact's phone number. Example: <code>+911234567890 (must be unique)</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>status_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="status_id"                data-endpoint="POSTapi-v1--subdomain--contacts"
               value="1"
               data-component="body">
    <br>
<p>The status ID. Example: <code>1</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>source_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="source_id"                data-endpoint="POSTapi-v1--subdomain--contacts"
               value="1"
               data-component="body">
    <br>
<p>The source ID. Example: <code>1</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>description</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="text" style="display: none"
                              name="description"                data-endpoint="POSTapi-v1--subdomain--contacts"
               value="Potential client from website"
               data-component="body">
    <br>
<p>Any additional notes. Example: <code>Potential client from website</code></p>
        </div>
        </form>

                    <h2 id="contact-management-GETapi-v1--subdomain--contacts">List Contacts</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Get a paginated list of contacts. You can filter by type and other parameters.</p>

<span id="example-requests-GETapi-v1--subdomain--contacts">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://whatsmark-saas.test/api/v1/architecto/contacts?type=lead&amp;source_id=1&amp;status_id=1&amp;page=1&amp;per_page=15" \
    --header "Authorization: Bearer {YOUR_ACCESS_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://whatsmark-saas.test/api/v1/architecto/contacts"
);

const params = {
    "type": "lead",
    "source_id": "1",
    "status_id": "1",
    "page": "1",
    "per_page": "15",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Authorization": "Bearer {YOUR_ACCESS_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'https://whatsmark-saas.test/api/v1/architecto/contacts';
$response = $client-&gt;get(
    $url,
    [
        'headers' =&gt; [
            'Authorization' =&gt; 'Bearer {YOUR_ACCESS_TOKEN}',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
        'query' =&gt; [
            'type' =&gt; 'lead',
            'source_id' =&gt; '1',
            'status_id' =&gt; '1',
            'page' =&gt; '1',
            'per_page' =&gt; '15',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>


<div class="python-example">
    <pre><code class="language-python">import requests
import json

url = 'https://whatsmark-saas.test/api/v1/architecto/contacts'
params = {
  'type': 'lead',
  'source_id': '1',
  'status_id': '1',
  'page': '1',
  'per_page': '15',
}
headers = {
  'Authorization': 'Bearer {YOUR_ACCESS_TOKEN}',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}

response = requests.request('GET', url, headers=headers, params=params)
response.json()</code></pre></div>

</span>

<span id="example-responses-GETapi-v1--subdomain--contacts">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;success&quot;,
    &quot;data&quot;: [
        {
            &quot;id&quot;: 1,
            &quot;firstname&quot;: &quot;John&quot;,
            &quot;lastname&quot;: &quot;Doe&quot;,
            &quot;company&quot;: &quot;Demo Company&quot;,
            &quot;type&quot;: &quot;lead&quot;,
            &quot;email&quot;: &quot;john@example.com&quot;,
            &quot;phone&quot;: &quot;+1234567890&quot;,
            &quot;created_at&quot;: &quot;2024-02-08 10:00:00&quot;,
            &quot;updated_at&quot;: &quot;2024-02-08 10:00:00&quot;
        }
    ],
    &quot;meta&quot;: {
        &quot;current_page&quot;: 1,
        &quot;total&quot;: 100,
        &quot;per_page&quot;: 15
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (401, unauthenticated):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;error&quot;,
    &quot;message&quot;: &quot;Invalid API token&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1--subdomain--contacts" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1--subdomain--contacts"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1--subdomain--contacts"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1--subdomain--contacts" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1--subdomain--contacts">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1--subdomain--contacts" data-method="GET"
      data-path="api/v1/{subdomain}/contacts"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1--subdomain--contacts', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1--subdomain--contacts"
                    onclick="tryItOut('GETapi-v1--subdomain--contacts');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1--subdomain--contacts"
                    onclick="cancelTryOut('GETapi-v1--subdomain--contacts');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1--subdomain--contacts"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/{subdomain}/contacts</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1--subdomain--contacts"
               value="Bearer {YOUR_ACCESS_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_ACCESS_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1--subdomain--contacts"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1--subdomain--contacts"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>subdomain</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="subdomain"                data-endpoint="GETapi-v1--subdomain--contacts"
               value="architecto"
               data-component="url">
    <br>
<p>Example: <code>architecto</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="text" style="display: none"
                              name="type"                data-endpoint="GETapi-v1--subdomain--contacts"
               value="lead"
               data-component="query">
    <br>
<p>Filter by contact type (lead/customer). Example: <code>lead</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>source_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="number" style="display: none"
               step="any"               name="source_id"                data-endpoint="GETapi-v1--subdomain--contacts"
               value="1"
               data-component="query">
    <br>
<p>Filter by source ID. Example: <code>1</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>status_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="number" style="display: none"
               step="any"               name="status_id"                data-endpoint="GETapi-v1--subdomain--contacts"
               value="1"
               data-component="query">
    <br>
<p>Filter by status ID. Example: <code>1</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="number" style="display: none"
               step="any"               name="page"                data-endpoint="GETapi-v1--subdomain--contacts"
               value="1"
               data-component="query">
    <br>
<p>The page number. Example: <code>1</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETapi-v1--subdomain--contacts"
               value="15"
               data-component="query">
    <br>
<p>Number of items per page. Example: <code>15</code></p>
            </div>
                </form>

                    <h2 id="contact-management-GETapi-v1--subdomain--contacts--id-">Get Contact Details</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Get detailed information about a specific contact.</p>

<span id="example-requests-GETapi-v1--subdomain--contacts--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://whatsmark-saas.test/api/v1/architecto/contacts/1" \
    --header "Authorization: Bearer {YOUR_ACCESS_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://whatsmark-saas.test/api/v1/architecto/contacts/1"
);

const headers = {
    "Authorization": "Bearer {YOUR_ACCESS_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'https://whatsmark-saas.test/api/v1/architecto/contacts/1';
$response = $client-&gt;get(
    $url,
    [
        'headers' =&gt; [
            'Authorization' =&gt; 'Bearer {YOUR_ACCESS_TOKEN}',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>


<div class="python-example">
    <pre><code class="language-python">import requests
import json

url = 'https://whatsmark-saas.test/api/v1/architecto/contacts/1'
headers = {
  'Authorization': 'Bearer {YOUR_ACCESS_TOKEN}',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}

response = requests.request('GET', url, headers=headers)
response.json()</code></pre></div>

</span>

<span id="example-responses-GETapi-v1--subdomain--contacts--id-">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;success&quot;,
    &quot;data&quot;: {
        &quot;id&quot;: 1,
        &quot;firstname&quot;: &quot;John&quot;,
        &quot;lastname&quot;: &quot;Doe&quot;,
        &quot;company&quot;: &quot;Demo Company&quot;,
        &quot;type&quot;: &quot;lead&quot;,
        &quot;email&quot;: &quot;john@example.com&quot;,
        &quot;phone&quot;: &quot;+1234567890&quot;,
        &quot;created_at&quot;: &quot;2024-02-08 10:00:00&quot;,
        &quot;updated_at&quot;: &quot;2024-02-08 10:00:00&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (404, not found):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;error&quot;,
    &quot;message&quot;: &quot;Contact not found&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1--subdomain--contacts--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1--subdomain--contacts--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1--subdomain--contacts--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1--subdomain--contacts--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1--subdomain--contacts--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1--subdomain--contacts--id-" data-method="GET"
      data-path="api/v1/{subdomain}/contacts/{id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1--subdomain--contacts--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1--subdomain--contacts--id-"
                    onclick="tryItOut('GETapi-v1--subdomain--contacts--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1--subdomain--contacts--id-"
                    onclick="cancelTryOut('GETapi-v1--subdomain--contacts--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1--subdomain--contacts--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/{subdomain}/contacts/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1--subdomain--contacts--id-"
               value="Bearer {YOUR_ACCESS_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_ACCESS_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1--subdomain--contacts--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1--subdomain--contacts--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>subdomain</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="subdomain"                data-endpoint="GETapi-v1--subdomain--contacts--id-"
               value="architecto"
               data-component="url">
    <br>
<p>Example: <code>architecto</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="GETapi-v1--subdomain--contacts--id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the contact. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="contact-management-PUTapi-v1--subdomain--contacts--id-">Update Contact</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Update an existing contact's information.</p>

<span id="example-requests-PUTapi-v1--subdomain--contacts--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "https://whatsmark-saas.test/api/v1/architecto/contacts/1" \
    --header "Authorization: Bearer {YOUR_ACCESS_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"firstname\": \"John\",
    \"lastname\": \"Doe\",
    \"company\": \"Acme Corp\",
    \"type\": \"lead\",
    \"email\": \"john@example.com (must be unique)\",
    \"phone\": \"+911234567890 (must be unique)\",
    \"status_id\": 1,
    \"source_id\": 1,
    \"description\": \"Potential client from website\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://whatsmark-saas.test/api/v1/architecto/contacts/1"
);

const headers = {
    "Authorization": "Bearer {YOUR_ACCESS_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "firstname": "John",
    "lastname": "Doe",
    "company": "Acme Corp",
    "type": "lead",
    "email": "john@example.com (must be unique)",
    "phone": "+911234567890 (must be unique)",
    "status_id": 1,
    "source_id": 1,
    "description": "Potential client from website"
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'https://whatsmark-saas.test/api/v1/architecto/contacts/1';
$response = $client-&gt;put(
    $url,
    [
        'headers' =&gt; [
            'Authorization' =&gt; 'Bearer {YOUR_ACCESS_TOKEN}',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
        'json' =&gt; [
            'firstname' =&gt; 'John',
            'lastname' =&gt; 'Doe',
            'company' =&gt; 'Acme Corp',
            'type' =&gt; 'lead',
            'email' =&gt; 'john@example.com (must be unique)',
            'phone' =&gt; '+911234567890 (must be unique)',
            'status_id' =&gt; 1,
            'source_id' =&gt; 1,
            'description' =&gt; 'Potential client from website',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>


<div class="python-example">
    <pre><code class="language-python">import requests
import json

url = 'https://whatsmark-saas.test/api/v1/architecto/contacts/1'
payload = {
    "firstname": "John",
    "lastname": "Doe",
    "company": "Acme Corp",
    "type": "lead",
    "email": "john@example.com (must be unique)",
    "phone": "+911234567890 (must be unique)",
    "status_id": 1,
    "source_id": 1,
    "description": "Potential client from website"
}
headers = {
  'Authorization': 'Bearer {YOUR_ACCESS_TOKEN}',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}

response = requests.request('PUT', url, headers=headers, json=payload)
response.json()</code></pre></div>

</span>

<span id="example-responses-PUTapi-v1--subdomain--contacts--id-">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;success&quot;,
    &quot;message&quot;: &quot;Contact updated successfully&quot;,
    &quot;data&quot;: {
        &quot;id&quot;: 1,
        &quot;firstname&quot;: &quot;John&quot;,
        &quot;lastname&quot;: &quot;Doe&quot;,
        &quot;email&quot;: &quot;john@example.com&quot;,
        &quot;updated_at&quot;: &quot;2024-02-08 11:00:00&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-PUTapi-v1--subdomain--contacts--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTapi-v1--subdomain--contacts--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-v1--subdomain--contacts--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTapi-v1--subdomain--contacts--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-v1--subdomain--contacts--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTapi-v1--subdomain--contacts--id-" data-method="PUT"
      data-path="api/v1/{subdomain}/contacts/{id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTapi-v1--subdomain--contacts--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTapi-v1--subdomain--contacts--id-"
                    onclick="tryItOut('PUTapi-v1--subdomain--contacts--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTapi-v1--subdomain--contacts--id-"
                    onclick="cancelTryOut('PUTapi-v1--subdomain--contacts--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTapi-v1--subdomain--contacts--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>api/v1/{subdomain}/contacts/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="PUTapi-v1--subdomain--contacts--id-"
               value="Bearer {YOUR_ACCESS_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_ACCESS_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTapi-v1--subdomain--contacts--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PUTapi-v1--subdomain--contacts--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>subdomain</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="subdomain"                data-endpoint="PUTapi-v1--subdomain--contacts--id-"
               value="architecto"
               data-component="url">
    <br>
<p>Example: <code>architecto</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="PUTapi-v1--subdomain--contacts--id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the contact. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>firstname</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="firstname"                data-endpoint="PUTapi-v1--subdomain--contacts--id-"
               value="John"
               data-component="body">
    <br>
<p>The first name of the contact. Example: <code>John</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>lastname</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="lastname"                data-endpoint="PUTapi-v1--subdomain--contacts--id-"
               value="Doe"
               data-component="body">
    <br>
<p>The last name of the contact. Example: <code>Doe</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>company</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="text" style="display: none"
                              name="company"                data-endpoint="PUTapi-v1--subdomain--contacts--id-"
               value="Acme Corp"
               data-component="body">
    <br>
<p>The company name. Example: <code>Acme Corp</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="type"                data-endpoint="PUTapi-v1--subdomain--contacts--id-"
               value="lead"
               data-component="body">
    <br>
<p>The contact type (lead/customer). Example: <code>lead</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="PUTapi-v1--subdomain--contacts--id-"
               value="john@example.com (must be unique)"
               data-component="body">
    <br>
<p>The contact's email address. Example: <code>john@example.com (must be unique)</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>phone</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="phone"                data-endpoint="PUTapi-v1--subdomain--contacts--id-"
               value="+911234567890 (must be unique)"
               data-component="body">
    <br>
<p>The contact's phone number. Example: <code>+911234567890 (must be unique)</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>status_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="status_id"                data-endpoint="PUTapi-v1--subdomain--contacts--id-"
               value="1"
               data-component="body">
    <br>
<p>The status ID. Example: <code>1</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>source_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="source_id"                data-endpoint="PUTapi-v1--subdomain--contacts--id-"
               value="1"
               data-component="body">
    <br>
<p>The source ID. Example: <code>1</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>description</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="text" style="display: none"
                              name="description"                data-endpoint="PUTapi-v1--subdomain--contacts--id-"
               value="Potential client from website"
               data-component="body">
    <br>
<p>Any additional notes. Example: <code>Potential client from website</code></p>
        </div>
        </form>

                    <h2 id="contact-management-DELETEapi-v1--subdomain--contacts--id-">Delete Contact</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Remove a contact from the system.</p>

<span id="example-requests-DELETEapi-v1--subdomain--contacts--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "https://whatsmark-saas.test/api/v1/architecto/contacts/1" \
    --header "Authorization: Bearer {YOUR_ACCESS_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://whatsmark-saas.test/api/v1/architecto/contacts/1"
);

const headers = {
    "Authorization": "Bearer {YOUR_ACCESS_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'https://whatsmark-saas.test/api/v1/architecto/contacts/1';
$response = $client-&gt;delete(
    $url,
    [
        'headers' =&gt; [
            'Authorization' =&gt; 'Bearer {YOUR_ACCESS_TOKEN}',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>


<div class="python-example">
    <pre><code class="language-python">import requests
import json

url = 'https://whatsmark-saas.test/api/v1/architecto/contacts/1'
headers = {
  'Authorization': 'Bearer {YOUR_ACCESS_TOKEN}',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}

response = requests.request('DELETE', url, headers=headers)
response.json()</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1--subdomain--contacts--id-">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;success&quot;,
    &quot;message&quot;: &quot;Contact deleted successfully&quot;
}</code>
 </pre>
            <blockquote>
            <p>Example response (404):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;error&quot;,
    &quot;message&quot;: &quot;Contact not found&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-DELETEapi-v1--subdomain--contacts--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1--subdomain--contacts--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1--subdomain--contacts--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1--subdomain--contacts--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1--subdomain--contacts--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1--subdomain--contacts--id-" data-method="DELETE"
      data-path="api/v1/{subdomain}/contacts/{id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1--subdomain--contacts--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1--subdomain--contacts--id-"
                    onclick="tryItOut('DELETEapi-v1--subdomain--contacts--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1--subdomain--contacts--id-"
                    onclick="cancelTryOut('DELETEapi-v1--subdomain--contacts--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1--subdomain--contacts--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/{subdomain}/contacts/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="DELETEapi-v1--subdomain--contacts--id-"
               value="Bearer {YOUR_ACCESS_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_ACCESS_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1--subdomain--contacts--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1--subdomain--contacts--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>subdomain</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="subdomain"                data-endpoint="DELETEapi-v1--subdomain--contacts--id-"
               value="architecto"
               data-component="url">
    <br>
<p>Example: <code>architecto</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="DELETEapi-v1--subdomain--contacts--id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the contact. Example: <code>1</code></p>
            </div>
                    </form>

                <h1 id="status-management">Status Management</h1>

    <p>APIs for managing contact statuses within the tenant context</p>

                                <h2 id="status-management-POSTapi-v1--subdomain--statuses">Create Status</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Create a new status in the system.</p>

<span id="example-requests-POSTapi-v1--subdomain--statuses">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://whatsmark-saas.test/api/v1/architecto/statuses" \
    --header "Authorization: Bearer {YOUR_ACCESS_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"name\": \"Pending\",
    \"color\": \"#FF0000\",
    \"isdefault\": false
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://whatsmark-saas.test/api/v1/architecto/statuses"
);

const headers = {
    "Authorization": "Bearer {YOUR_ACCESS_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "name": "Pending",
    "color": "#FF0000",
    "isdefault": false
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'https://whatsmark-saas.test/api/v1/architecto/statuses';
$response = $client-&gt;post(
    $url,
    [
        'headers' =&gt; [
            'Authorization' =&gt; 'Bearer {YOUR_ACCESS_TOKEN}',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
        'json' =&gt; [
            'name' =&gt; 'Pending',
            'color' =&gt; '#FF0000',
            'isdefault' =&gt; false,
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>


<div class="python-example">
    <pre><code class="language-python">import requests
import json

url = 'https://whatsmark-saas.test/api/v1/architecto/statuses'
payload = {
    "name": "Pending",
    "color": "#FF0000",
    "isdefault": false
}
headers = {
  'Authorization': 'Bearer {YOUR_ACCESS_TOKEN}',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}

response = requests.request('POST', url, headers=headers, json=payload)
response.json()</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1--subdomain--statuses">
            <blockquote>
            <p>Example response (201, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;success&quot;,
    &quot;message&quot;: &quot;Status created successfully&quot;,
    &quot;data&quot;: {
        &quot;id&quot;: 1,
        &quot;name&quot;: &quot;Pending&quot;,
        &quot;color&quot;: &quot;#FF0000&quot;,
        &quot;isdefault&quot;: false,
        &quot;created_at&quot;: &quot;2024-02-08 10:00:00&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, validation error):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;error&quot;,
    &quot;message&quot;: &quot;Validation failed&quot;,
    &quot;errors&quot;: {
        &quot;name&quot;: [
            &quot;The name field is required.&quot;
        ]
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1--subdomain--statuses" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1--subdomain--statuses"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1--subdomain--statuses"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1--subdomain--statuses" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1--subdomain--statuses">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1--subdomain--statuses" data-method="POST"
      data-path="api/v1/{subdomain}/statuses"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1--subdomain--statuses', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1--subdomain--statuses"
                    onclick="tryItOut('POSTapi-v1--subdomain--statuses');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1--subdomain--statuses"
                    onclick="cancelTryOut('POSTapi-v1--subdomain--statuses');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1--subdomain--statuses"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/{subdomain}/statuses</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-v1--subdomain--statuses"
               value="Bearer {YOUR_ACCESS_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_ACCESS_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1--subdomain--statuses"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1--subdomain--statuses"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>subdomain</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="subdomain"                data-endpoint="POSTapi-v1--subdomain--statuses"
               value="architecto"
               data-component="url">
    <br>
<p>Example: <code>architecto</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="name"                data-endpoint="POSTapi-v1--subdomain--statuses"
               value="Pending"
               data-component="body">
    <br>
<p>The name of the status. Example: <code>Pending</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>color</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="text" style="display: none"
                              name="color"                data-endpoint="POSTapi-v1--subdomain--statuses"
               value="#FF0000"
               data-component="body">
    <br>
<p>The color code for the status. Example: <code>#FF0000</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>isdefault</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
                <label data-endpoint="POSTapi-v1--subdomain--statuses" style="display: none">
            <input type="radio" name="isdefault"
                   value="true"
                   data-endpoint="POSTapi-v1--subdomain--statuses"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="POSTapi-v1--subdomain--statuses" style="display: none">
            <input type="radio" name="isdefault"
                   value="false"
                   data-endpoint="POSTapi-v1--subdomain--statuses"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Whether this is the default status. Example: <code>false</code></p>
        </div>
        </form>

                    <h2 id="status-management-GETapi-v1--subdomain--statuses">List Statuses</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Get a list of statuses with optional pagination.</p>

<span id="example-requests-GETapi-v1--subdomain--statuses">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://whatsmark-saas.test/api/v1/architecto/statuses?page=1&amp;per_page=15" \
    --header "Authorization: Bearer {YOUR_ACCESS_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://whatsmark-saas.test/api/v1/architecto/statuses"
);

const params = {
    "page": "1",
    "per_page": "15",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Authorization": "Bearer {YOUR_ACCESS_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'https://whatsmark-saas.test/api/v1/architecto/statuses';
$response = $client-&gt;get(
    $url,
    [
        'headers' =&gt; [
            'Authorization' =&gt; 'Bearer {YOUR_ACCESS_TOKEN}',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
        'query' =&gt; [
            'page' =&gt; '1',
            'per_page' =&gt; '15',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>


<div class="python-example">
    <pre><code class="language-python">import requests
import json

url = 'https://whatsmark-saas.test/api/v1/architecto/statuses'
params = {
  'page': '1',
  'per_page': '15',
}
headers = {
  'Authorization': 'Bearer {YOUR_ACCESS_TOKEN}',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}

response = requests.request('GET', url, headers=headers, params=params)
response.json()</code></pre></div>

</span>

<span id="example-responses-GETapi-v1--subdomain--statuses">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;success&quot;,
    &quot;data&quot;: [
        {
            &quot;id&quot;: 1,
            &quot;name&quot;: &quot;Active&quot;,
            &quot;color&quot;: &quot;#00FF00&quot;,
            &quot;isdefault&quot;: true,
            &quot;created_at&quot;: &quot;2024-02-08 10:00:00&quot;,
            &quot;updated_at&quot;: &quot;2024-02-08 10:00:00&quot;
        }
    ],
    &quot;meta&quot;: {
        &quot;current_page&quot;: 1,
        &quot;total&quot;: 10,
        &quot;per_page&quot;: 15
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (401, unauthenticated):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;error&quot;,
    &quot;message&quot;: &quot;Invalid API token&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1--subdomain--statuses" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1--subdomain--statuses"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1--subdomain--statuses"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1--subdomain--statuses" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1--subdomain--statuses">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1--subdomain--statuses" data-method="GET"
      data-path="api/v1/{subdomain}/statuses"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1--subdomain--statuses', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1--subdomain--statuses"
                    onclick="tryItOut('GETapi-v1--subdomain--statuses');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1--subdomain--statuses"
                    onclick="cancelTryOut('GETapi-v1--subdomain--statuses');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1--subdomain--statuses"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/{subdomain}/statuses</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1--subdomain--statuses"
               value="Bearer {YOUR_ACCESS_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_ACCESS_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1--subdomain--statuses"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1--subdomain--statuses"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>subdomain</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="subdomain"                data-endpoint="GETapi-v1--subdomain--statuses"
               value="architecto"
               data-component="url">
    <br>
<p>Example: <code>architecto</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="number" style="display: none"
               step="any"               name="page"                data-endpoint="GETapi-v1--subdomain--statuses"
               value="1"
               data-component="query">
    <br>
<p>The page number. Example: <code>1</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETapi-v1--subdomain--statuses"
               value="15"
               data-component="query">
    <br>
<p>Number of items per page. Example: <code>15</code></p>
            </div>
                </form>

                    <h2 id="status-management-GETapi-v1--subdomain--statuses--id-">Get Status Details</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Get detailed information about a specific status.</p>

<span id="example-requests-GETapi-v1--subdomain--statuses--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://whatsmark-saas.test/api/v1/architecto/statuses/1" \
    --header "Authorization: Bearer {YOUR_ACCESS_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://whatsmark-saas.test/api/v1/architecto/statuses/1"
);

const headers = {
    "Authorization": "Bearer {YOUR_ACCESS_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'https://whatsmark-saas.test/api/v1/architecto/statuses/1';
$response = $client-&gt;get(
    $url,
    [
        'headers' =&gt; [
            'Authorization' =&gt; 'Bearer {YOUR_ACCESS_TOKEN}',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>


<div class="python-example">
    <pre><code class="language-python">import requests
import json

url = 'https://whatsmark-saas.test/api/v1/architecto/statuses/1'
headers = {
  'Authorization': 'Bearer {YOUR_ACCESS_TOKEN}',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}

response = requests.request('GET', url, headers=headers)
response.json()</code></pre></div>

</span>

<span id="example-responses-GETapi-v1--subdomain--statuses--id-">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;success&quot;,
    &quot;data&quot;: {
        &quot;id&quot;: 1,
        &quot;name&quot;: &quot;Active&quot;,
        &quot;color&quot;: &quot;#00FF00&quot;,
        &quot;isdefault&quot;: true,
        &quot;created_at&quot;: &quot;2024-02-08 10:00:00&quot;,
        &quot;updated_at&quot;: &quot;2024-02-08 10:00:00&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (404, not found):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;error&quot;,
    &quot;message&quot;: &quot;Status not found&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1--subdomain--statuses--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1--subdomain--statuses--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1--subdomain--statuses--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1--subdomain--statuses--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1--subdomain--statuses--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1--subdomain--statuses--id-" data-method="GET"
      data-path="api/v1/{subdomain}/statuses/{id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1--subdomain--statuses--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1--subdomain--statuses--id-"
                    onclick="tryItOut('GETapi-v1--subdomain--statuses--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1--subdomain--statuses--id-"
                    onclick="cancelTryOut('GETapi-v1--subdomain--statuses--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1--subdomain--statuses--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/{subdomain}/statuses/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1--subdomain--statuses--id-"
               value="Bearer {YOUR_ACCESS_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_ACCESS_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1--subdomain--statuses--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1--subdomain--statuses--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>subdomain</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="subdomain"                data-endpoint="GETapi-v1--subdomain--statuses--id-"
               value="architecto"
               data-component="url">
    <br>
<p>Example: <code>architecto</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="GETapi-v1--subdomain--statuses--id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the status. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="status-management-PUTapi-v1--subdomain--statuses--id-">Update Status</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Update an existing status's information.</p>

<span id="example-requests-PUTapi-v1--subdomain--statuses--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "https://whatsmark-saas.test/api/v1/architecto/statuses/1" \
    --header "Authorization: Bearer {YOUR_ACCESS_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"name\": \"In Progress\",
    \"color\": \"#0000FF\",
    \"isdefault\": true
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://whatsmark-saas.test/api/v1/architecto/statuses/1"
);

const headers = {
    "Authorization": "Bearer {YOUR_ACCESS_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "name": "In Progress",
    "color": "#0000FF",
    "isdefault": true
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'https://whatsmark-saas.test/api/v1/architecto/statuses/1';
$response = $client-&gt;put(
    $url,
    [
        'headers' =&gt; [
            'Authorization' =&gt; 'Bearer {YOUR_ACCESS_TOKEN}',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
        'json' =&gt; [
            'name' =&gt; 'In Progress',
            'color' =&gt; '#0000FF',
            'isdefault' =&gt; true,
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>


<div class="python-example">
    <pre><code class="language-python">import requests
import json

url = 'https://whatsmark-saas.test/api/v1/architecto/statuses/1'
payload = {
    "name": "In Progress",
    "color": "#0000FF",
    "isdefault": true
}
headers = {
  'Authorization': 'Bearer {YOUR_ACCESS_TOKEN}',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}

response = requests.request('PUT', url, headers=headers, json=payload)
response.json()</code></pre></div>

</span>

<span id="example-responses-PUTapi-v1--subdomain--statuses--id-">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;success&quot;,
    &quot;message&quot;: &quot;Status updated successfully&quot;,
    &quot;data&quot;: {
        &quot;id&quot;: 1,
        &quot;name&quot;: &quot;In Progress&quot;,
        &quot;color&quot;: &quot;#0000FF&quot;,
        &quot;updated_at&quot;: &quot;2024-02-08 11:00:00&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-PUTapi-v1--subdomain--statuses--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTapi-v1--subdomain--statuses--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-v1--subdomain--statuses--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTapi-v1--subdomain--statuses--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-v1--subdomain--statuses--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTapi-v1--subdomain--statuses--id-" data-method="PUT"
      data-path="api/v1/{subdomain}/statuses/{id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTapi-v1--subdomain--statuses--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTapi-v1--subdomain--statuses--id-"
                    onclick="tryItOut('PUTapi-v1--subdomain--statuses--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTapi-v1--subdomain--statuses--id-"
                    onclick="cancelTryOut('PUTapi-v1--subdomain--statuses--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTapi-v1--subdomain--statuses--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>api/v1/{subdomain}/statuses/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="PUTapi-v1--subdomain--statuses--id-"
               value="Bearer {YOUR_ACCESS_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_ACCESS_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTapi-v1--subdomain--statuses--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PUTapi-v1--subdomain--statuses--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>subdomain</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="subdomain"                data-endpoint="PUTapi-v1--subdomain--statuses--id-"
               value="architecto"
               data-component="url">
    <br>
<p>Example: <code>architecto</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="PUTapi-v1--subdomain--statuses--id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the status. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="text" style="display: none"
                              name="name"                data-endpoint="PUTapi-v1--subdomain--statuses--id-"
               value="In Progress"
               data-component="body">
    <br>
<p>The name of the status. Example: <code>In Progress</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>color</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="text" style="display: none"
                              name="color"                data-endpoint="PUTapi-v1--subdomain--statuses--id-"
               value="#0000FF"
               data-component="body">
    <br>
<p>The color code for the status. Example: <code>#0000FF</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>isdefault</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
                <label data-endpoint="PUTapi-v1--subdomain--statuses--id-" style="display: none">
            <input type="radio" name="isdefault"
                   value="true"
                   data-endpoint="PUTapi-v1--subdomain--statuses--id-"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="PUTapi-v1--subdomain--statuses--id-" style="display: none">
            <input type="radio" name="isdefault"
                   value="false"
                   data-endpoint="PUTapi-v1--subdomain--statuses--id-"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Whether this is the default status. Example: <code>true</code></p>
        </div>
        </form>

                    <h2 id="status-management-DELETEapi-v1--subdomain--statuses--id-">Delete Status</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Remove a status from the system.</p>

<span id="example-requests-DELETEapi-v1--subdomain--statuses--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "https://whatsmark-saas.test/api/v1/architecto/statuses/1" \
    --header "Authorization: Bearer {YOUR_ACCESS_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://whatsmark-saas.test/api/v1/architecto/statuses/1"
);

const headers = {
    "Authorization": "Bearer {YOUR_ACCESS_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'https://whatsmark-saas.test/api/v1/architecto/statuses/1';
$response = $client-&gt;delete(
    $url,
    [
        'headers' =&gt; [
            'Authorization' =&gt; 'Bearer {YOUR_ACCESS_TOKEN}',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>


<div class="python-example">
    <pre><code class="language-python">import requests
import json

url = 'https://whatsmark-saas.test/api/v1/architecto/statuses/1'
headers = {
  'Authorization': 'Bearer {YOUR_ACCESS_TOKEN}',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}

response = requests.request('DELETE', url, headers=headers)
response.json()</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1--subdomain--statuses--id-">
            <blockquote>
            <p>Example response (400):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;error&quot;,
    &quot;message&quot;: &quot;Cannot delete the default status&quot;
}</code>
 </pre>
            <blockquote>
            <p>Example response (404, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
  &quot;status&quot;: &quot;success&quot;,
  &quot;message&quot;: &quot;Status deleted successfully&quot;
}
* @response {
  &quot;status&quot;: &quot;error&quot;,
  &quot;message&quot;: &quot;Status not found&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-DELETEapi-v1--subdomain--statuses--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1--subdomain--statuses--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1--subdomain--statuses--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1--subdomain--statuses--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1--subdomain--statuses--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1--subdomain--statuses--id-" data-method="DELETE"
      data-path="api/v1/{subdomain}/statuses/{id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1--subdomain--statuses--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1--subdomain--statuses--id-"
                    onclick="tryItOut('DELETEapi-v1--subdomain--statuses--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1--subdomain--statuses--id-"
                    onclick="cancelTryOut('DELETEapi-v1--subdomain--statuses--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1--subdomain--statuses--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/{subdomain}/statuses/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="DELETEapi-v1--subdomain--statuses--id-"
               value="Bearer {YOUR_ACCESS_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_ACCESS_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1--subdomain--statuses--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1--subdomain--statuses--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>subdomain</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="subdomain"                data-endpoint="DELETEapi-v1--subdomain--statuses--id-"
               value="architecto"
               data-component="url">
    <br>
<p>Example: <code>architecto</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="DELETEapi-v1--subdomain--statuses--id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the status. Example: <code>1</code></p>
            </div>
                    </form>

                <h1 id="source-management">Source Management</h1>

    <p>APIs for managing contact sources within the tenant context</p>

                                <h2 id="source-management-POSTapi-v1--subdomain--sources">Create Source</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Create a new source in the system.</p>

<span id="example-requests-POSTapi-v1--subdomain--sources">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://whatsmark-saas.test/api/v1/architecto/sources" \
    --header "Authorization: Bearer {YOUR_ACCESS_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"name\": \"Referral\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://whatsmark-saas.test/api/v1/architecto/sources"
);

const headers = {
    "Authorization": "Bearer {YOUR_ACCESS_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "name": "Referral"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'https://whatsmark-saas.test/api/v1/architecto/sources';
$response = $client-&gt;post(
    $url,
    [
        'headers' =&gt; [
            'Authorization' =&gt; 'Bearer {YOUR_ACCESS_TOKEN}',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
        'json' =&gt; [
            'name' =&gt; 'Referral',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>


<div class="python-example">
    <pre><code class="language-python">import requests
import json

url = 'https://whatsmark-saas.test/api/v1/architecto/sources'
payload = {
    "name": "Referral"
}
headers = {
  'Authorization': 'Bearer {YOUR_ACCESS_TOKEN}',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}

response = requests.request('POST', url, headers=headers, json=payload)
response.json()</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1--subdomain--sources">
            <blockquote>
            <p>Example response (201, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;success&quot;,
    &quot;message&quot;: &quot;Source created successfully&quot;,
    &quot;data&quot;: {
        &quot;id&quot;: 1,
        &quot;name&quot;: &quot;Referral&quot;,
        &quot;created_at&quot;: &quot;2024-02-08 10:00:00&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, validation error):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;error&quot;,
    &quot;message&quot;: &quot;Validation failed&quot;,
    &quot;errors&quot;: {
        &quot;name&quot;: [
            &quot;The name field is required.&quot;
        ]
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1--subdomain--sources" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1--subdomain--sources"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1--subdomain--sources"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1--subdomain--sources" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1--subdomain--sources">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1--subdomain--sources" data-method="POST"
      data-path="api/v1/{subdomain}/sources"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1--subdomain--sources', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1--subdomain--sources"
                    onclick="tryItOut('POSTapi-v1--subdomain--sources');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1--subdomain--sources"
                    onclick="cancelTryOut('POSTapi-v1--subdomain--sources');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1--subdomain--sources"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/{subdomain}/sources</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-v1--subdomain--sources"
               value="Bearer {YOUR_ACCESS_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_ACCESS_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1--subdomain--sources"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1--subdomain--sources"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>subdomain</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="subdomain"                data-endpoint="POSTapi-v1--subdomain--sources"
               value="architecto"
               data-component="url">
    <br>
<p>Example: <code>architecto</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="name"                data-endpoint="POSTapi-v1--subdomain--sources"
               value="Referral"
               data-component="body">
    <br>
<p>The name of the source. Example: <code>Referral</code></p>
        </div>
        </form>

                    <h2 id="source-management-GETapi-v1--subdomain--sources">List Sources</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Get a list of sources with optional pagination.</p>

<span id="example-requests-GETapi-v1--subdomain--sources">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://whatsmark-saas.test/api/v1/architecto/sources?page=1&amp;per_page=15" \
    --header "Authorization: Bearer {YOUR_ACCESS_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://whatsmark-saas.test/api/v1/architecto/sources"
);

const params = {
    "page": "1",
    "per_page": "15",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Authorization": "Bearer {YOUR_ACCESS_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'https://whatsmark-saas.test/api/v1/architecto/sources';
$response = $client-&gt;get(
    $url,
    [
        'headers' =&gt; [
            'Authorization' =&gt; 'Bearer {YOUR_ACCESS_TOKEN}',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
        'query' =&gt; [
            'page' =&gt; '1',
            'per_page' =&gt; '15',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>


<div class="python-example">
    <pre><code class="language-python">import requests
import json

url = 'https://whatsmark-saas.test/api/v1/architecto/sources'
params = {
  'page': '1',
  'per_page': '15',
}
headers = {
  'Authorization': 'Bearer {YOUR_ACCESS_TOKEN}',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}

response = requests.request('GET', url, headers=headers, params=params)
response.json()</code></pre></div>

</span>

<span id="example-responses-GETapi-v1--subdomain--sources">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;success&quot;,
    &quot;data&quot;: [
        {
            &quot;id&quot;: 1,
            &quot;name&quot;: &quot;Website&quot;,
            &quot;created_at&quot;: &quot;2024-02-08 10:00:00&quot;,
            &quot;updated_at&quot;: &quot;2024-02-08 10:00:00&quot;
        }
    ],
    &quot;meta&quot;: {
        &quot;current_page&quot;: 1,
        &quot;total&quot;: 10,
        &quot;per_page&quot;: 15
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (401, unauthenticated):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;error&quot;,
    &quot;message&quot;: &quot;Invalid API token&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1--subdomain--sources" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1--subdomain--sources"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1--subdomain--sources"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1--subdomain--sources" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1--subdomain--sources">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1--subdomain--sources" data-method="GET"
      data-path="api/v1/{subdomain}/sources"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1--subdomain--sources', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1--subdomain--sources"
                    onclick="tryItOut('GETapi-v1--subdomain--sources');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1--subdomain--sources"
                    onclick="cancelTryOut('GETapi-v1--subdomain--sources');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1--subdomain--sources"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/{subdomain}/sources</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1--subdomain--sources"
               value="Bearer {YOUR_ACCESS_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_ACCESS_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1--subdomain--sources"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1--subdomain--sources"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>subdomain</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="subdomain"                data-endpoint="GETapi-v1--subdomain--sources"
               value="architecto"
               data-component="url">
    <br>
<p>Example: <code>architecto</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="number" style="display: none"
               step="any"               name="page"                data-endpoint="GETapi-v1--subdomain--sources"
               value="1"
               data-component="query">
    <br>
<p>The page number. Example: <code>1</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETapi-v1--subdomain--sources"
               value="15"
               data-component="query">
    <br>
<p>Number of items per page. Example: <code>15</code></p>
            </div>
                </form>

                    <h2 id="source-management-GETapi-v1--subdomain--sources--id-">Get Source Details</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Get detailed information about a specific source.</p>

<span id="example-requests-GETapi-v1--subdomain--sources--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://whatsmark-saas.test/api/v1/architecto/sources/1" \
    --header "Authorization: Bearer {YOUR_ACCESS_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://whatsmark-saas.test/api/v1/architecto/sources/1"
);

const headers = {
    "Authorization": "Bearer {YOUR_ACCESS_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'https://whatsmark-saas.test/api/v1/architecto/sources/1';
$response = $client-&gt;get(
    $url,
    [
        'headers' =&gt; [
            'Authorization' =&gt; 'Bearer {YOUR_ACCESS_TOKEN}',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>


<div class="python-example">
    <pre><code class="language-python">import requests
import json

url = 'https://whatsmark-saas.test/api/v1/architecto/sources/1'
headers = {
  'Authorization': 'Bearer {YOUR_ACCESS_TOKEN}',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}

response = requests.request('GET', url, headers=headers)
response.json()</code></pre></div>

</span>

<span id="example-responses-GETapi-v1--subdomain--sources--id-">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;success&quot;,
    &quot;data&quot;: {
        &quot;id&quot;: 1,
        &quot;name&quot;: &quot;Website&quot;,
        &quot;created_at&quot;: &quot;2024-02-08 10:00:00&quot;,
        &quot;updated_at&quot;: &quot;2024-02-08 10:00:00&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (404, not found):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;error&quot;,
    &quot;message&quot;: &quot;Source not found&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1--subdomain--sources--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1--subdomain--sources--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1--subdomain--sources--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1--subdomain--sources--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1--subdomain--sources--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1--subdomain--sources--id-" data-method="GET"
      data-path="api/v1/{subdomain}/sources/{id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1--subdomain--sources--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1--subdomain--sources--id-"
                    onclick="tryItOut('GETapi-v1--subdomain--sources--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1--subdomain--sources--id-"
                    onclick="cancelTryOut('GETapi-v1--subdomain--sources--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1--subdomain--sources--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/{subdomain}/sources/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1--subdomain--sources--id-"
               value="Bearer {YOUR_ACCESS_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_ACCESS_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1--subdomain--sources--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1--subdomain--sources--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>subdomain</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="subdomain"                data-endpoint="GETapi-v1--subdomain--sources--id-"
               value="architecto"
               data-component="url">
    <br>
<p>Example: <code>architecto</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="GETapi-v1--subdomain--sources--id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the source. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="source-management-PUTapi-v1--subdomain--sources--id-">Update Source</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Update an existing source's information.</p>

<span id="example-requests-PUTapi-v1--subdomain--sources--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "https://whatsmark-saas.test/api/v1/architecto/sources/1" \
    --header "Authorization: Bearer {YOUR_ACCESS_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"name\": \"Referral Program\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://whatsmark-saas.test/api/v1/architecto/sources/1"
);

const headers = {
    "Authorization": "Bearer {YOUR_ACCESS_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "name": "Referral Program"
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'https://whatsmark-saas.test/api/v1/architecto/sources/1';
$response = $client-&gt;put(
    $url,
    [
        'headers' =&gt; [
            'Authorization' =&gt; 'Bearer {YOUR_ACCESS_TOKEN}',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
        'json' =&gt; [
            'name' =&gt; 'Referral Program',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>


<div class="python-example">
    <pre><code class="language-python">import requests
import json

url = 'https://whatsmark-saas.test/api/v1/architecto/sources/1'
payload = {
    "name": "Referral Program"
}
headers = {
  'Authorization': 'Bearer {YOUR_ACCESS_TOKEN}',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}

response = requests.request('PUT', url, headers=headers, json=payload)
response.json()</code></pre></div>

</span>

<span id="example-responses-PUTapi-v1--subdomain--sources--id-">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;success&quot;,
    &quot;message&quot;: &quot;Source updated successfully&quot;,
    &quot;data&quot;: {
        &quot;id&quot;: 1,
        &quot;name&quot;: &quot;Referral Program&quot;,
        &quot;updated_at&quot;: &quot;2024-02-08 11:00:00&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-PUTapi-v1--subdomain--sources--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTapi-v1--subdomain--sources--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-v1--subdomain--sources--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTapi-v1--subdomain--sources--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-v1--subdomain--sources--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTapi-v1--subdomain--sources--id-" data-method="PUT"
      data-path="api/v1/{subdomain}/sources/{id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTapi-v1--subdomain--sources--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTapi-v1--subdomain--sources--id-"
                    onclick="tryItOut('PUTapi-v1--subdomain--sources--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTapi-v1--subdomain--sources--id-"
                    onclick="cancelTryOut('PUTapi-v1--subdomain--sources--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTapi-v1--subdomain--sources--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>api/v1/{subdomain}/sources/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="PUTapi-v1--subdomain--sources--id-"
               value="Bearer {YOUR_ACCESS_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_ACCESS_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTapi-v1--subdomain--sources--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PUTapi-v1--subdomain--sources--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>subdomain</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="subdomain"                data-endpoint="PUTapi-v1--subdomain--sources--id-"
               value="architecto"
               data-component="url">
    <br>
<p>Example: <code>architecto</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="PUTapi-v1--subdomain--sources--id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the source. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="name"                data-endpoint="PUTapi-v1--subdomain--sources--id-"
               value="Referral Program"
               data-component="body">
    <br>
<p>The name of the source. Example: <code>Referral Program</code></p>
        </div>
        </form>

                    <h2 id="source-management-DELETEapi-v1--subdomain--sources--id-">Delete Source</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Remove a source from the system.</p>

<span id="example-requests-DELETEapi-v1--subdomain--sources--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "https://whatsmark-saas.test/api/v1/architecto/sources/1" \
    --header "Authorization: Bearer {YOUR_ACCESS_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://whatsmark-saas.test/api/v1/architecto/sources/1"
);

const headers = {
    "Authorization": "Bearer {YOUR_ACCESS_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'https://whatsmark-saas.test/api/v1/architecto/sources/1';
$response = $client-&gt;delete(
    $url,
    [
        'headers' =&gt; [
            'Authorization' =&gt; 'Bearer {YOUR_ACCESS_TOKEN}',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>


<div class="python-example">
    <pre><code class="language-python">import requests
import json

url = 'https://whatsmark-saas.test/api/v1/architecto/sources/1'
headers = {
  'Authorization': 'Bearer {YOUR_ACCESS_TOKEN}',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}

response = requests.request('DELETE', url, headers=headers)
response.json()</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1--subdomain--sources--id-">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;success&quot;,
    &quot;message&quot;: &quot;Source deleted successfully&quot;
}</code>
 </pre>
            <blockquote>
            <p>Example response (404):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;error&quot;,
    &quot;message&quot;: &quot;Source not found&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-DELETEapi-v1--subdomain--sources--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1--subdomain--sources--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1--subdomain--sources--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1--subdomain--sources--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1--subdomain--sources--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1--subdomain--sources--id-" data-method="DELETE"
      data-path="api/v1/{subdomain}/sources/{id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1--subdomain--sources--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1--subdomain--sources--id-"
                    onclick="tryItOut('DELETEapi-v1--subdomain--sources--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1--subdomain--sources--id-"
                    onclick="cancelTryOut('DELETEapi-v1--subdomain--sources--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1--subdomain--sources--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/{subdomain}/sources/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="DELETEapi-v1--subdomain--sources--id-"
               value="Bearer {YOUR_ACCESS_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_ACCESS_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1--subdomain--sources--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1--subdomain--sources--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>subdomain</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="subdomain"                data-endpoint="DELETEapi-v1--subdomain--sources--id-"
               value="architecto"
               data-component="url">
    <br>
<p>Example: <code>architecto</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="DELETEapi-v1--subdomain--sources--id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the source. Example: <code>1</code></p>
            </div>
                    </form>

                <h1 id="group-management">Group Management</h1>

    <p>APIs for managing contact groups within the tenant context</p>

                                <h2 id="group-management-GETapi-v1--subdomain--groups">List Groups</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Get a list of Groups with optional pagination.</p>

<span id="example-requests-GETapi-v1--subdomain--groups">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://whatsmark-saas.test/api/v1/architecto/groups?page=1&amp;per_page=15" \
    --header "Authorization: Bearer {YOUR_ACCESS_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://whatsmark-saas.test/api/v1/architecto/groups"
);

const params = {
    "page": "1",
    "per_page": "15",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Authorization": "Bearer {YOUR_ACCESS_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'https://whatsmark-saas.test/api/v1/architecto/groups';
$response = $client-&gt;get(
    $url,
    [
        'headers' =&gt; [
            'Authorization' =&gt; 'Bearer {YOUR_ACCESS_TOKEN}',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
        'query' =&gt; [
            'page' =&gt; '1',
            'per_page' =&gt; '15',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>


<div class="python-example">
    <pre><code class="language-python">import requests
import json

url = 'https://whatsmark-saas.test/api/v1/architecto/groups'
params = {
  'page': '1',
  'per_page': '15',
}
headers = {
  'Authorization': 'Bearer {YOUR_ACCESS_TOKEN}',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}

response = requests.request('GET', url, headers=headers, params=params)
response.json()</code></pre></div>

</span>

<span id="example-responses-GETapi-v1--subdomain--groups">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;success&quot;,
    &quot;data&quot;: [
        {
            &quot;id&quot;: 19,
            &quot;tenant_id&quot;: 13,
            &quot;name&quot;: &quot;group2&quot;,
            &quot;created_at&quot;: &quot;2025-07-14T06:53:01.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-07-14T06:53:01.000000Z&quot;
        }
    ]
}</code>
 </pre>
            <blockquote>
            <p>Example response (401, unauthenticated):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;error&quot;,
    &quot;message&quot;: &quot;Invalid API token&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1--subdomain--groups" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1--subdomain--groups"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1--subdomain--groups"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1--subdomain--groups" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1--subdomain--groups">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1--subdomain--groups" data-method="GET"
      data-path="api/v1/{subdomain}/groups"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1--subdomain--groups', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1--subdomain--groups"
                    onclick="tryItOut('GETapi-v1--subdomain--groups');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1--subdomain--groups"
                    onclick="cancelTryOut('GETapi-v1--subdomain--groups');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1--subdomain--groups"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/{subdomain}/groups</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1--subdomain--groups"
               value="Bearer {YOUR_ACCESS_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_ACCESS_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1--subdomain--groups"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1--subdomain--groups"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>subdomain</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="subdomain"                data-endpoint="GETapi-v1--subdomain--groups"
               value="architecto"
               data-component="url">
    <br>
<p>Example: <code>architecto</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="number" style="display: none"
               step="any"               name="page"                data-endpoint="GETapi-v1--subdomain--groups"
               value="1"
               data-component="query">
    <br>
<p>The page number. Example: <code>1</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETapi-v1--subdomain--groups"
               value="15"
               data-component="query">
    <br>
<p>Number of items per page. Example: <code>15</code></p>
            </div>
                </form>

                    <h2 id="group-management-GETapi-v1--subdomain--groups--id-">Get Groups Details</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Get detailed information about a specific Groups.</p>

<span id="example-requests-GETapi-v1--subdomain--groups--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://whatsmark-saas.test/api/v1/architecto/groups/1" \
    --header "Authorization: Bearer {YOUR_ACCESS_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://whatsmark-saas.test/api/v1/architecto/groups/1"
);

const headers = {
    "Authorization": "Bearer {YOUR_ACCESS_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'https://whatsmark-saas.test/api/v1/architecto/groups/1';
$response = $client-&gt;get(
    $url,
    [
        'headers' =&gt; [
            'Authorization' =&gt; 'Bearer {YOUR_ACCESS_TOKEN}',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>


<div class="python-example">
    <pre><code class="language-python">import requests
import json

url = 'https://whatsmark-saas.test/api/v1/architecto/groups/1'
headers = {
  'Authorization': 'Bearer {YOUR_ACCESS_TOKEN}',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}

response = requests.request('GET', url, headers=headers)
response.json()</code></pre></div>

</span>

<span id="example-responses-GETapi-v1--subdomain--groups--id-">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;success&quot;,
    &quot;data&quot;: {
        &quot;id&quot;: 19,
        &quot;tenant_id&quot;: 13,
        &quot;name&quot;: &quot;group2&quot;,
        &quot;created_at&quot;: &quot;2025-07-14T06:53:01.000000Z&quot;,
        &quot;updated_at&quot;: &quot;2025-07-14T06:53:01.000000Z&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (404, not found):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;error&quot;,
    &quot;message&quot;: &quot;Groups not found&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1--subdomain--groups--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1--subdomain--groups--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1--subdomain--groups--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1--subdomain--groups--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1--subdomain--groups--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1--subdomain--groups--id-" data-method="GET"
      data-path="api/v1/{subdomain}/groups/{id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1--subdomain--groups--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1--subdomain--groups--id-"
                    onclick="tryItOut('GETapi-v1--subdomain--groups--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1--subdomain--groups--id-"
                    onclick="cancelTryOut('GETapi-v1--subdomain--groups--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1--subdomain--groups--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/{subdomain}/groups/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1--subdomain--groups--id-"
               value="Bearer {YOUR_ACCESS_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_ACCESS_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1--subdomain--groups--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1--subdomain--groups--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>subdomain</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="subdomain"                data-endpoint="GETapi-v1--subdomain--groups--id-"
               value="architecto"
               data-component="url">
    <br>
<p>Example: <code>architecto</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="GETapi-v1--subdomain--groups--id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the Groups. Example: <code>1</code></p>
            </div>
                    </form>

                <h1 id="template-management">Template Management</h1>

    <p>APIs for managing WhatsApp templates within the tenant context</p>

                                <h2 id="template-management-GETapi-v1--subdomain--templates">List Templates</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Get a list of Templates with optional pagination.</p>

<span id="example-requests-GETapi-v1--subdomain--templates">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://whatsmark-saas.test/api/v1/architecto/templates?page=1&amp;per_page=15" \
    --header "Authorization: Bearer {YOUR_ACCESS_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://whatsmark-saas.test/api/v1/architecto/templates"
);

const params = {
    "page": "1",
    "per_page": "15",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Authorization": "Bearer {YOUR_ACCESS_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'https://whatsmark-saas.test/api/v1/architecto/templates';
$response = $client-&gt;get(
    $url,
    [
        'headers' =&gt; [
            'Authorization' =&gt; 'Bearer {YOUR_ACCESS_TOKEN}',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
        'query' =&gt; [
            'page' =&gt; '1',
            'per_page' =&gt; '15',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>


<div class="python-example">
    <pre><code class="language-python">import requests
import json

url = 'https://whatsmark-saas.test/api/v1/architecto/templates'
params = {
  'page': '1',
  'per_page': '15',
}
headers = {
  'Authorization': 'Bearer {YOUR_ACCESS_TOKEN}',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}

response = requests.request('GET', url, headers=headers, params=params)
response.json()</code></pre></div>

</span>

<span id="example-responses-GETapi-v1--subdomain--templates">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;success&quot;,
    &quot;data&quot;: [
        {
            &quot;id&quot;: 1,
            &quot;tenant_id&quot;: 2,
            &quot;template_id&quot;: 510070465356446,
            &quot;template_name&quot;: &quot;welcome_to_whatsbot&quot;,
            &quot;language&quot;: &quot;en&quot;,
            &quot;status&quot;: &quot;APPROVED&quot;,
            &quot;category&quot;: &quot;MARKETING&quot;,
            &quot;header_data_format&quot;: &quot;IMAGE&quot;,
            &quot;header_data_text&quot;: null,
            &quot;header_params_count&quot;: 0,
            &quot;body_data&quot;: &quot;Welcome to WhatsBot - WhatsApp Marketing, Bot &amp; Chat Module for Perfex CRM Support! 😊\n\nYou can explore and interact with our module in multiple ways:\n\n1️⃣ *Buy Module*: If you want to purchase the module 🛒.\n\n3️⃣ *Explore All Features*: To read detailed documentation and explore all features, on our online documentation 📚.&quot;,
            &quot;body_params_count&quot;: 0,
            &quot;footer_data&quot;: null,
            &quot;footer_params_count&quot;: 0,
            &quot;buttons_data&quot;: &quot;[{\&quot;type\&quot;:\&quot;URL\&quot;,\&quot;text\&quot;:\&quot;Buy Module\&quot;,\&quot;url\&quot;:\&quot;https:\\/\\/codecanyon.net\\/item\\/whatsbot-whatsapp-marketing-bot-chat-module-for-perfex-crm\\/53052338\&quot;},{\&quot;type\&quot;:\&quot;URL\&quot;,\&quot;text\&quot;:\&quot;Watch youtube Demo\&quot;,\&quot;url\&quot;:\&quot;https:\\/\\/youtu.be\\/tihN9GyXuzI\&quot;},{\&quot;type\&quot;:\&quot;QUICK_REPLY\&quot;,\&quot;text\&quot;:\&quot;more\&quot;}]&quot;,
            &quot;created_at&quot;: &quot;2025-07-12T10:44:50.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-07-12T10:44:50.000000Z&quot;
        }
    ]
}</code>
 </pre>
            <blockquote>
            <p>Example response (401, unauthenticated):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;error&quot;,
    &quot;message&quot;: &quot;Invalid API token&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1--subdomain--templates" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1--subdomain--templates"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1--subdomain--templates"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1--subdomain--templates" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1--subdomain--templates">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1--subdomain--templates" data-method="GET"
      data-path="api/v1/{subdomain}/templates"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1--subdomain--templates', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1--subdomain--templates"
                    onclick="tryItOut('GETapi-v1--subdomain--templates');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1--subdomain--templates"
                    onclick="cancelTryOut('GETapi-v1--subdomain--templates');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1--subdomain--templates"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/{subdomain}/templates</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1--subdomain--templates"
               value="Bearer {YOUR_ACCESS_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_ACCESS_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1--subdomain--templates"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1--subdomain--templates"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>subdomain</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="subdomain"                data-endpoint="GETapi-v1--subdomain--templates"
               value="architecto"
               data-component="url">
    <br>
<p>Example: <code>architecto</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="number" style="display: none"
               step="any"               name="page"                data-endpoint="GETapi-v1--subdomain--templates"
               value="1"
               data-component="query">
    <br>
<p>The page number. Example: <code>1</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETapi-v1--subdomain--templates"
               value="15"
               data-component="query">
    <br>
<p>Number of items per page. Example: <code>15</code></p>
            </div>
                </form>

                    <h2 id="template-management-GETapi-v1--subdomain--templates--id-">Get Templates Details</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Get detailed information about a specific Templates.</p>

<span id="example-requests-GETapi-v1--subdomain--templates--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://whatsmark-saas.test/api/v1/architecto/templates/1" \
    --header "Authorization: Bearer {YOUR_ACCESS_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://whatsmark-saas.test/api/v1/architecto/templates/1"
);

const headers = {
    "Authorization": "Bearer {YOUR_ACCESS_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'https://whatsmark-saas.test/api/v1/architecto/templates/1';
$response = $client-&gt;get(
    $url,
    [
        'headers' =&gt; [
            'Authorization' =&gt; 'Bearer {YOUR_ACCESS_TOKEN}',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>


<div class="python-example">
    <pre><code class="language-python">import requests
import json

url = 'https://whatsmark-saas.test/api/v1/architecto/templates/1'
headers = {
  'Authorization': 'Bearer {YOUR_ACCESS_TOKEN}',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}

response = requests.request('GET', url, headers=headers)
response.json()</code></pre></div>

</span>

<span id="example-responses-GETapi-v1--subdomain--templates--id-">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;success&quot;,
    &quot;data&quot;: {
        &quot;id&quot;: 1,
        &quot;tenant_id&quot;: 2,
        &quot;template_id&quot;: 510070465356446,
        &quot;template_name&quot;: &quot;welcome_to_whatsbot&quot;,
        &quot;language&quot;: &quot;en&quot;,
        &quot;status&quot;: &quot;APPROVED&quot;,
        &quot;category&quot;: &quot;MARKETING&quot;,
        &quot;header_data_format&quot;: &quot;IMAGE&quot;,
        &quot;header_data_text&quot;: null,
        &quot;header_params_count&quot;: 0,
        &quot;body_data&quot;: &quot;Welcome to WhatsBot - WhatsApp Marketing, Bot &amp; Chat Module for Perfex CRM Support! 😊\n\nYou can explore and interact with our module in multiple ways:\n\n1️⃣ *Buy Module*: If you want to purchase the module 🛒.\n\n3️⃣ *Explore All Features*: To read detailed documentation and explore all features, on our online documentation 📚.&quot;,
        &quot;body_params_count&quot;: 0,
        &quot;footer_data&quot;: null,
        &quot;footer_params_count&quot;: 0,
        &quot;buttons_data&quot;: &quot;[{\&quot;type\&quot;:\&quot;URL\&quot;,\&quot;text\&quot;:\&quot;Buy Module\&quot;,\&quot;url\&quot;:\&quot;https:\\/\\/codecanyon.net\\/item\\/whatsbot-whatsapp-marketing-bot-chat-module-for-perfex-crm\\/53052338\&quot;},{\&quot;type\&quot;:\&quot;URL\&quot;,\&quot;text\&quot;:\&quot;Watch youtube Demo\&quot;,\&quot;url\&quot;:\&quot;https:\\/\\/youtu.be\\/tihN9GyXuzI\&quot;},{\&quot;type\&quot;:\&quot;QUICK_REPLY\&quot;,\&quot;text\&quot;:\&quot;more\&quot;}]&quot;,
        &quot;created_at&quot;: &quot;2025-07-12T10:44:50.000000Z&quot;,
        &quot;updated_at&quot;: &quot;2025-07-12T10:44:50.000000Z&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (404, not found):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;error&quot;,
    &quot;message&quot;: &quot;Template not found&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1--subdomain--templates--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1--subdomain--templates--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1--subdomain--templates--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1--subdomain--templates--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1--subdomain--templates--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1--subdomain--templates--id-" data-method="GET"
      data-path="api/v1/{subdomain}/templates/{id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1--subdomain--templates--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1--subdomain--templates--id-"
                    onclick="tryItOut('GETapi-v1--subdomain--templates--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1--subdomain--templates--id-"
                    onclick="cancelTryOut('GETapi-v1--subdomain--templates--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1--subdomain--templates--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/{subdomain}/templates/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1--subdomain--templates--id-"
               value="Bearer {YOUR_ACCESS_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_ACCESS_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1--subdomain--templates--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1--subdomain--templates--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>subdomain</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="subdomain"                data-endpoint="GETapi-v1--subdomain--templates--id-"
               value="architecto"
               data-component="url">
    <br>
<p>Example: <code>architecto</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="GETapi-v1--subdomain--templates--id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the Templates. Example: <code>1</code></p>
            </div>
                    </form>

                <h1 id="template-bot-management">Template Bot Management</h1>

    <p>APIs for managing WhatsApp template bots within the tenant context</p>

                                <h2 id="template-bot-management-GETapi-v1--subdomain--templatebots">List TemplateBot</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Get a list of TemplateBot with optional pagination.</p>

<span id="example-requests-GETapi-v1--subdomain--templatebots">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://whatsmark-saas.test/api/v1/architecto/templatebots?page=1&amp;per_page=15" \
    --header "Authorization: Bearer {YOUR_ACCESS_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://whatsmark-saas.test/api/v1/architecto/templatebots"
);

const params = {
    "page": "1",
    "per_page": "15",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Authorization": "Bearer {YOUR_ACCESS_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'https://whatsmark-saas.test/api/v1/architecto/templatebots';
$response = $client-&gt;get(
    $url,
    [
        'headers' =&gt; [
            'Authorization' =&gt; 'Bearer {YOUR_ACCESS_TOKEN}',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
        'query' =&gt; [
            'page' =&gt; '1',
            'per_page' =&gt; '15',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>


<div class="python-example">
    <pre><code class="language-python">import requests
import json

url = 'https://whatsmark-saas.test/api/v1/architecto/templatebots'
params = {
  'page': '1',
  'per_page': '15',
}
headers = {
  'Authorization': 'Bearer {YOUR_ACCESS_TOKEN}',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}

response = requests.request('GET', url, headers=headers, params=params)
response.json()</code></pre></div>

</span>

<span id="example-responses-GETapi-v1--subdomain--templatebots">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;success&quot;,
    &quot;data&quot;: [
        {
            &quot;id&quot;: 1,
            &quot;tenant_id&quot;: 2,
            &quot;name&quot;: &quot;tbot1&quot;,
            &quot;rel_type&quot;: &quot;lead&quot;,
            &quot;template_id&quot;: 510070465356446,
            &quot;header_params&quot;: &quot;[]&quot;,
            &quot;body_params&quot;: &quot;[]&quot;,
            &quot;footer_params&quot;: &quot;[]&quot;,
            &quot;filename&quot;: &quot;tenant/2/template-bot/CTzitrN1GbYUu6qbbaMElkOlv3qTaZTXPVyNXDAM.png&quot;,
            &quot;trigger&quot;: &quot;heloo&quot;,
            &quot;reply_type&quot;: 1,
            &quot;is_bot_active&quot;: 1,
            &quot;created_at&quot;: &quot;2025-07-12T11:16:01.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-07-12T11:16:01.000000Z&quot;,
            &quot;sending_count&quot;: 0
        }
    ],
    &quot;meta&quot;: {
        &quot;current_page&quot;: 1,
        &quot;total&quot;: 10,
        &quot;per_page&quot;: 15
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (401, unauthenticated):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;error&quot;,
    &quot;message&quot;: &quot;Invalid API token&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1--subdomain--templatebots" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1--subdomain--templatebots"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1--subdomain--templatebots"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1--subdomain--templatebots" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1--subdomain--templatebots">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1--subdomain--templatebots" data-method="GET"
      data-path="api/v1/{subdomain}/templatebots"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1--subdomain--templatebots', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1--subdomain--templatebots"
                    onclick="tryItOut('GETapi-v1--subdomain--templatebots');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1--subdomain--templatebots"
                    onclick="cancelTryOut('GETapi-v1--subdomain--templatebots');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1--subdomain--templatebots"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/{subdomain}/templatebots</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1--subdomain--templatebots"
               value="Bearer {YOUR_ACCESS_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_ACCESS_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1--subdomain--templatebots"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1--subdomain--templatebots"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>subdomain</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="subdomain"                data-endpoint="GETapi-v1--subdomain--templatebots"
               value="architecto"
               data-component="url">
    <br>
<p>Example: <code>architecto</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="number" style="display: none"
               step="any"               name="page"                data-endpoint="GETapi-v1--subdomain--templatebots"
               value="1"
               data-component="query">
    <br>
<p>The page number. Example: <code>1</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETapi-v1--subdomain--templatebots"
               value="15"
               data-component="query">
    <br>
<p>Number of items per page. Example: <code>15</code></p>
            </div>
                </form>

                    <h2 id="template-bot-management-GETapi-v1--subdomain--templatebots--id-">Get TemplateBot Details</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Get detailed information about a specific TemplateBot.</p>

<span id="example-requests-GETapi-v1--subdomain--templatebots--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://whatsmark-saas.test/api/v1/architecto/templatebots/1" \
    --header "Authorization: Bearer {YOUR_ACCESS_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://whatsmark-saas.test/api/v1/architecto/templatebots/1"
);

const headers = {
    "Authorization": "Bearer {YOUR_ACCESS_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'https://whatsmark-saas.test/api/v1/architecto/templatebots/1';
$response = $client-&gt;get(
    $url,
    [
        'headers' =&gt; [
            'Authorization' =&gt; 'Bearer {YOUR_ACCESS_TOKEN}',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>


<div class="python-example">
    <pre><code class="language-python">import requests
import json

url = 'https://whatsmark-saas.test/api/v1/architecto/templatebots/1'
headers = {
  'Authorization': 'Bearer {YOUR_ACCESS_TOKEN}',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}

response = requests.request('GET', url, headers=headers)
response.json()</code></pre></div>

</span>

<span id="example-responses-GETapi-v1--subdomain--templatebots--id-">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;success&quot;,
    &quot;data&quot;: {
        &quot;id&quot;: 1,
        &quot;tenant_id&quot;: 2,
        &quot;name&quot;: &quot;tbot1&quot;,
        &quot;rel_type&quot;: &quot;lead&quot;,
        &quot;template_id&quot;: 510070465356446,
        &quot;header_params&quot;: &quot;[]&quot;,
        &quot;body_params&quot;: &quot;[]&quot;,
        &quot;footer_params&quot;: &quot;[]&quot;,
        &quot;filename&quot;: &quot;tenant/2/template-bot/CTzitrN1GbYUu6qbbaMElkOlv3qTaZTXPVyNXDAM.png&quot;,
        &quot;trigger&quot;: &quot;heloo&quot;,
        &quot;reply_type&quot;: 1,
        &quot;is_bot_active&quot;: 1,
        &quot;created_at&quot;: &quot;2025-07-12T11:16:01.000000Z&quot;,
        &quot;updated_at&quot;: &quot;2025-07-12T11:16:01.000000Z&quot;,
        &quot;sending_count&quot;: 0
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (404, not found):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;error&quot;,
    &quot;message&quot;: &quot;Template bot not found&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1--subdomain--templatebots--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1--subdomain--templatebots--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1--subdomain--templatebots--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1--subdomain--templatebots--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1--subdomain--templatebots--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1--subdomain--templatebots--id-" data-method="GET"
      data-path="api/v1/{subdomain}/templatebots/{id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1--subdomain--templatebots--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1--subdomain--templatebots--id-"
                    onclick="tryItOut('GETapi-v1--subdomain--templatebots--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1--subdomain--templatebots--id-"
                    onclick="cancelTryOut('GETapi-v1--subdomain--templatebots--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1--subdomain--templatebots--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/{subdomain}/templatebots/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1--subdomain--templatebots--id-"
               value="Bearer {YOUR_ACCESS_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_ACCESS_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1--subdomain--templatebots--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1--subdomain--templatebots--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>subdomain</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="subdomain"                data-endpoint="GETapi-v1--subdomain--templatebots--id-"
               value="architecto"
               data-component="url">
    <br>
<p>Example: <code>architecto</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="GETapi-v1--subdomain--templatebots--id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the TemplateBot. Example: <code>1</code></p>
            </div>
                    </form>

                <h1 id="message-bot-management">Message Bot Management</h1>

    <p>APIs for managing WhatsApp message bots within the tenant context</p>

                                <h2 id="message-bot-management-GETapi-v1--subdomain--messagebots">List Messagebots</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Get a list of Messagebots</p>

<span id="example-requests-GETapi-v1--subdomain--messagebots">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://whatsmark-saas.test/api/v1/architecto/messagebots?page=1&amp;per_page=15" \
    --header "Authorization: Bearer {YOUR_ACCESS_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://whatsmark-saas.test/api/v1/architecto/messagebots"
);

const params = {
    "page": "1",
    "per_page": "15",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Authorization": "Bearer {YOUR_ACCESS_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'https://whatsmark-saas.test/api/v1/architecto/messagebots';
$response = $client-&gt;get(
    $url,
    [
        'headers' =&gt; [
            'Authorization' =&gt; 'Bearer {YOUR_ACCESS_TOKEN}',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
        'query' =&gt; [
            'page' =&gt; '1',
            'per_page' =&gt; '15',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>


<div class="python-example">
    <pre><code class="language-python">import requests
import json

url = 'https://whatsmark-saas.test/api/v1/architecto/messagebots'
params = {
  'page': '1',
  'per_page': '15',
}
headers = {
  'Authorization': 'Bearer {YOUR_ACCESS_TOKEN}',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}

response = requests.request('GET', url, headers=headers, params=params)
response.json()</code></pre></div>

</span>

<span id="example-responses-GETapi-v1--subdomain--messagebots">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;success&quot;,
    &quot;data&quot;: [
        {
            &quot;id&quot;: 3,
            &quot;tenant_id&quot;: 2,
            &quot;name&quot;: &quot;mbot3&quot;,
            &quot;rel_type&quot;: &quot;lead&quot;,
            &quot;reply_text&quot;: &quot;hello from reply text&quot;,
            &quot;reply_type&quot;: 1,
            &quot;trigger&quot;: &quot;mbot3&quot;,
            &quot;bot_header&quot;: null,
            &quot;bot_footer&quot;: null,
            &quot;button1&quot;: null,
            &quot;button1_id&quot;: null,
            &quot;button2&quot;: null,
            &quot;button2_id&quot;: null,
            &quot;button3&quot;: null,
            &quot;button3_id&quot;: null,
            &quot;button_name&quot;: null,
            &quot;button_url&quot;: null,
            &quot;addedfrom&quot;: 1,
            &quot;is_bot_active&quot;: true,
            &quot;sending_count&quot;: 0,
            &quot;filename&quot;: null,
            &quot;created_at&quot;: &quot;2025-07-12T12:18:33.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-07-12T12:18:33.000000Z&quot;
        }
    ],
    &quot;meta&quot;: {
        &quot;current_page&quot;: 1,
        &quot;total&quot;: 10,
        &quot;per_page&quot;: 15
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (401, unauthenticated):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;error&quot;,
    &quot;message&quot;: &quot;Invalid API token&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1--subdomain--messagebots" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1--subdomain--messagebots"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1--subdomain--messagebots"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1--subdomain--messagebots" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1--subdomain--messagebots">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1--subdomain--messagebots" data-method="GET"
      data-path="api/v1/{subdomain}/messagebots"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1--subdomain--messagebots', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1--subdomain--messagebots"
                    onclick="tryItOut('GETapi-v1--subdomain--messagebots');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1--subdomain--messagebots"
                    onclick="cancelTryOut('GETapi-v1--subdomain--messagebots');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1--subdomain--messagebots"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/{subdomain}/messagebots</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1--subdomain--messagebots"
               value="Bearer {YOUR_ACCESS_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_ACCESS_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1--subdomain--messagebots"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1--subdomain--messagebots"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>subdomain</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="subdomain"                data-endpoint="GETapi-v1--subdomain--messagebots"
               value="architecto"
               data-component="url">
    <br>
<p>Example: <code>architecto</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="number" style="display: none"
               step="any"               name="page"                data-endpoint="GETapi-v1--subdomain--messagebots"
               value="1"
               data-component="query">
    <br>
<p>The page number. Example: <code>1</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETapi-v1--subdomain--messagebots"
               value="15"
               data-component="query">
    <br>
<p>Number of items per page. Example: <code>15</code></p>
            </div>
                </form>

                    <h2 id="message-bot-management-GETapi-v1--subdomain--messagebots--id-">Get Messagebots Details</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Get detailed information about a specific Messagebots.</p>

<span id="example-requests-GETapi-v1--subdomain--messagebots--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://whatsmark-saas.test/api/v1/architecto/messagebots/1" \
    --header "Authorization: Bearer {YOUR_ACCESS_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://whatsmark-saas.test/api/v1/architecto/messagebots/1"
);

const headers = {
    "Authorization": "Bearer {YOUR_ACCESS_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'https://whatsmark-saas.test/api/v1/architecto/messagebots/1';
$response = $client-&gt;get(
    $url,
    [
        'headers' =&gt; [
            'Authorization' =&gt; 'Bearer {YOUR_ACCESS_TOKEN}',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>


<div class="python-example">
    <pre><code class="language-python">import requests
import json

url = 'https://whatsmark-saas.test/api/v1/architecto/messagebots/1'
headers = {
  'Authorization': 'Bearer {YOUR_ACCESS_TOKEN}',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}

response = requests.request('GET', url, headers=headers)
response.json()</code></pre></div>

</span>

<span id="example-responses-GETapi-v1--subdomain--messagebots--id-">
            <blockquote>
            <p>Example response (200, success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;success&quot;,
    &quot;data&quot;: {
        &quot;id&quot;: 1,
        &quot;tenant_id&quot;: 2,
        &quot;name&quot;: &quot;messagebot 1&quot;,
        &quot;rel_type&quot;: &quot;lead&quot;,
        &quot;reply_text&quot;: &quot;hello from message bot testing&quot;,
        &quot;reply_type&quot;: 1,
        &quot;trigger&quot;: &quot;hello&quot;,
        &quot;bot_header&quot;: null,
        &quot;bot_footer&quot;: null,
        &quot;button1&quot;: null,
        &quot;button1_id&quot;: null,
        &quot;button2&quot;: null,
        &quot;button2_id&quot;: null,
        &quot;button3&quot;: null,
        &quot;button3_id&quot;: null,
        &quot;button_name&quot;: null,
        &quot;button_url&quot;: null,
        &quot;addedfrom&quot;: 2,
        &quot;is_bot_active&quot;: true,
        &quot;sending_count&quot;: 0,
        &quot;filename&quot;: null,
        &quot;created_at&quot;: &quot;2025-07-12T11:44:58.000000Z&quot;,
        &quot;updated_at&quot;: &quot;2025-07-12T11:44:58.000000Z&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (404, not found):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;error&quot;,
    &quot;message&quot;: &quot;Message bot not found&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1--subdomain--messagebots--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1--subdomain--messagebots--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1--subdomain--messagebots--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1--subdomain--messagebots--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1--subdomain--messagebots--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1--subdomain--messagebots--id-" data-method="GET"
      data-path="api/v1/{subdomain}/messagebots/{id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1--subdomain--messagebots--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1--subdomain--messagebots--id-"
                    onclick="tryItOut('GETapi-v1--subdomain--messagebots--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1--subdomain--messagebots--id-"
                    onclick="cancelTryOut('GETapi-v1--subdomain--messagebots--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1--subdomain--messagebots--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/{subdomain}/messagebots/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1--subdomain--messagebots--id-"
               value="Bearer {YOUR_ACCESS_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_ACCESS_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1--subdomain--messagebots--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1--subdomain--messagebots--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>subdomain</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="subdomain"                data-endpoint="GETapi-v1--subdomain--messagebots--id-"
               value="architecto"
               data-component="url">
    <br>
<p>Example: <code>architecto</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="GETapi-v1--subdomain--messagebots--id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the status. Example: <code>1</code></p>
            </div>
                    </form>

            

        
    </div>
    <div class="dark-box">
                    <div class="lang-selector">
                                                        <button type="button" class="lang-button" data-language-name="bash">bash</button>
                                                        <button type="button" class="lang-button" data-language-name="javascript">javascript</button>
                                                        <button type="button" class="lang-button" data-language-name="php">php</button>
                                                        <button type="button" class="lang-button" data-language-name="python">python</button>
                            </div>
            </div>
</div>
</body>
</html>
