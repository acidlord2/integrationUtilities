import json
import urllib3
import base64
from urllib.parse import urlencode

def lambda_handler(event, context):
    """
    AWS Lambda handler that forwards requests to MoySklad API
    """
    
    # Initialize HTTP client
    http = urllib3.PoolManager()
    
    try:
        # Log basic info for debugging
        print(f"Lambda invoked - Path: {event.get('rawPath', 'N/A')}, Query: {event.get('rawQueryString', 'N/A')}")
        
        # Extract request details from Lambda Function URL event
        request_context = event.get('requestContext', {})
        http_info = request_context.get('http', {})
        
        method = http_info.get('method', 'GET')
        path = http_info.get('path', '/')
        
        # Handle query parameters from Lambda Function URL - use raw query string to preserve encoding
        raw_query_string = event.get('rawQueryString', '')
        query_params = event.get('queryStringParameters') or {}
        if query_params is None:
            query_params = {}
            
        headers = event.get('headers') or {}
        body = event.get('body')
        is_base64 = event.get('isBase64Encoded', False)
        
        # Build target URL using raw query string to preserve original encoding
        base_url = 'https://api.moysklad.ru'
        target_url = base_url + path
        
        # Use raw query string if available, otherwise fall back to re-encoding parsed parameters
        if raw_query_string:
            target_url += '?' + raw_query_string
            print(f"Using raw query string, URL length: {len(target_url)}")
        elif query_params and any(query_params.values()):
            target_url += '?' + urlencode(query_params)
            print(f"Using encoded params, URL length: {len(target_url)}")
        else:
            print("No query parameters found")
        
        # Prepare headers for forwarding
        forward_headers = {}
        for key, value in headers.items():
            # Skip AWS-specific headers and problematic headers
            if (not key.lower().startswith(('x-amz', 'x-forwarded', 'cloudfront', 'postman-token')) and 
                key.lower() != 'host'):
                forward_headers[key] = value

        # Set proper host header for MoySklad
        forward_headers['Host'] = 'api.moysklad.ru'
        
        # Ensure we have required headers for MoySklad API
        if 'accept' not in [k.lower() for k in forward_headers.keys()]:
            forward_headers['Accept'] = 'application/json'
        if 'accept-encoding' not in [k.lower() for k in forward_headers.keys()]:
            forward_headers['Accept-Encoding'] = 'gzip, deflate'
        
        # Handle body encoding - only include body if it exists and is not empty
        request_body = None
        if body and body.strip():  # Only if body exists and is not just whitespace
            if is_base64:
                request_body = base64.b64decode(body)
            else:
                request_body = body.encode('utf-8') if isinstance(body, str) else body
        
        # Prepare request parameters
        request_params = {
            'method': method,
            'url': target_url,
            'headers': forward_headers,
            'timeout': urllib3.Timeout(connect=10, read=30)
        }
        
        # Only include body if it exists
        if request_body is not None:
            request_params['body'] = request_body
        
        # Make the request to MoySklad API
        response = http.request(**request_params)
        
        # Prepare response headers - forward all headers from MoySklad
        response_headers = {}
        for key, value in response.headers.items():
            # Forward all headers except those that AWS Lambda manages internally and compression headers
            if key.lower() not in ['connection', 'transfer-encoding', 'content-encoding']:
                response_headers[key] = value
      
        # Return original response body as string (urllib3 handles decompression automatically)
        response_body = response.data.decode('utf-8') if response.data else ''
        
        return {
            'statusCode': response.status,
            'headers': response_headers,
            'body': response_body,
            'isBase64Encoded': False
        }
        
    except Exception as e:
        # Return error response
        return {
            'statusCode': 500,
            'headers': {
                'Content-Type': 'application/json'
            },
            'body': json.dumps({
                'error': 'Proxy error',
                'message': str(e)
            })
        }

# For local testing
if __name__ == '__main__':
    # Test event from actual Lambda logs
    test_event = {
        "version": "2.0",
        "routeKey": "$default",
        "rawPath": "/api/remap/1.2/entity/customerorder/",
        "rawQueryString": "filter=agent=https://api.moysklad.ru/api/remap/1.2/entity/counterparty/e1490fb8-7054-11ea-0a80-01220017723b;organization=https://api.moysklad.ru/api/remap/1.2/entity/organization/f3e8ac0c-62ad-11ea-0a80-03e30022f0a0;deliveryPlannedMoment%3E=2025-11-26%2000:00:00;deliveryPlannedMoment%3C=2025-11-26%2023:59:59;state=https://api.moysklad.ru/api/remap/1.2/entity/customerorder/metadata/states/9d61e479-013c-11e9-9107-504800115e4b;&limit=100&offset=0",
        "headers": {
            "authorization": "Basic YWNpZGxvcmRAMTBrb2xnb3RvazpWazZrRzQ4a2VmdTRuaVI=",
            "x-amzn-tls-cipher-suite": "TLS_AES_128_GCM_SHA256",
            "x-amzn-tls-version": "TLSv1.3",
            "x-amzn-trace-id": "Root=1-69278fe5-2a8ff1462da7f7817f8eec99",
            "x-forwarded-proto": "https",
            "postman-token": "f7bf6aad-1cc8-4d8c-b905-d7331e91dfa3",
            "host": "giq6db6lr33hnmo24gzo3vdhuy0mvcya.lambda-url.ca-central-1.on.aws",
            "x-forwarded-port": "443",
            "x-forwarded-for": "66.49.218.22",
            "accept-encoding": "gzip, deflate, br",
            "accept": "*/*",
            "user-agent": "PostmanRuntime/7.49.1"
        },
        "requestContext": {
            "accountId": "anonymous",
            "apiId": "giq6db6lr33hnmo24gzo3vdhuy0mvcya",
            "domainName": "giq6db6lr33hnmo24gzo3vdhuy0mvcya.lambda-url.ca-central-1.on.aws",
            "domainPrefix": "giq6db6lr33hnmo24gzo3vdhuy0mvcya",
            "http": {
                "method": "GET",
                "path": "/api/remap/1.2/entity/customerorder/",
                "protocol": "HTTP/1.1",
                "sourceIp": "66.49.218.22",
                "userAgent": "PostmanRuntime/7.49.1"
            },
            "requestId": "952c7007-b616-418f-8f06-a03dc7c48687",
            "routeKey": "$default",
            "stage": "$default",
            "time": "26/Nov/2025:23:40:21 +0000",
            "timeEpoch": 1764200421774
        },
        "isBase64Encoded": False
    }
    
    result = lambda_handler(test_event, None)
    print(json.dumps(result, indent=2))