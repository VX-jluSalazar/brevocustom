# Create an event

POST https://api.brevo.com/v3/events
Content-Type: application/json

Create an event to track a contact's interaction.

Reference: https://developers.brevo.com/reference/create-event

## OpenAPI Specification

```yaml
openapi: 3.1.0
info:
  title: Brevo API
  version: 1.0.0
paths:
  /events:
    post:
      operationId: create-event
      summary: Create an event
      description: Create an event to track a contact's interaction.
      tags:
        - subpackage_event
      parameters:
        - name: api-key
          in: header
          description: >-
            The API key should be passed in the request headers as `api-key` for
            authentication.
          required: true
          schema:
            type: string
      responses:
        '200':
          description: Successful response
        '400':
          description: bad request
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/CreateEventRequestBadRequestError'
        '401':
          description: bad request
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/CreateEventRequestUnauthorizedError'
      requestBody:
        content:
          application/json:
            schema:
              type: object
              properties:
                contact_properties:
                  type: object
                  additionalProperties:
                    $ref: >-
                      #/components/schemas/EventsPostRequestBodyContentApplicationJsonSchemaContactProperties
                  description: >-
                    Properties defining the state of the contact associated to
                    this event. Useful to update contact attributes defined in
                    your contacts database while passing the event. For example:
                    **"FIRSTNAME": "Jane" , "AGE": 37**
                event_date:
                  type: string
                  format: date-time
                  description: >-
                    ISO 8601 timestamp of when the event occurred (e.g.
                    "2024-01-24T17:39:57+01:00"). If no value is passed, the
                    timestamp of the event creation is used.
                event_name:
                  type: string
                  description: >-
                    The name of the event that occurred. This is how you will
                    find your event in Brevo. Limited to 255 characters; only
                    alphanumeric characters, hyphens (-), and underscores (_)
                    are allowed.
                event_properties:
                  type: object
                  additionalProperties:
                    $ref: >-
                      #/components/schemas/EventsPostRequestBodyContentApplicationJsonSchemaEventProperties
                  description: >-
                    Properties of the event. Top level properties and nested
                    properties can be used to better segment contacts and
                    personalise workflow conditions. The following field types
                    are supported: string, number, boolean (true/false), date
                    (Timestamp e.g. "2024-01-24T17:39:57+01:00"). Keys are
                    limited to 255 characters, alphanumerical characters and - _
                    only. Size is limited to 50KB.
                identifiers:
                  $ref: >-
                    #/components/schemas/EventsPostRequestBodyContentApplicationJsonSchemaIdentifiers
                  description: >-
                    Identifies the contact associated with the event. At least
                    one identifier is required.
                object:
                  $ref: >-
                    #/components/schemas/EventsPostRequestBodyContentApplicationJsonSchemaObject
                  description: >-
                    Identifiers of the object record associated with this event.
                    Ignored if the object type or identifier for this record
                    does not exist on the account.
              required:
                - event_name
                - identifiers
servers:
  - url: https://api.brevo.com/v3
components:
  schemas:
    EventsPostRequestBodyContentApplicationJsonSchemaContactProperties:
      oneOf:
        - type: string
        - type: integer
        - type: boolean
      title: EventsPostRequestBodyContentApplicationJsonSchemaContactProperties
    EventsPostRequestBodyContentApplicationJsonSchemaEventProperties:
      oneOf:
        - type: string
        - type: integer
        - type: boolean
        - type: object
          additionalProperties:
            description: Any type
        - type: array
          items:
            description: Any type
      title: EventsPostRequestBodyContentApplicationJsonSchemaEventProperties
    EventsPostRequestBodyContentApplicationJsonSchemaIdentifiers:
      type: object
      properties:
        contact_id:
          type: integer
          format: int64
          description: >-
            Internal unique contact ID. When present, this takes priority over
            all other identifiers for event attribution and contact resolution.
        email_id:
          type: string
          description: Email Id associated with the event
        ext_id:
          type: string
          description: ext_id associated with the event
        landline_number_id:
          type: string
          description: landline_number associated with the event
        phone_id:
          type: string
          description: SMS associated with the event
        whatsapp_id:
          type: string
          description: whatsapp associated with the event
      description: >-
        Identifies the contact associated with the event. At least one
        identifier is required.
      title: EventsPostRequestBodyContentApplicationJsonSchemaIdentifiers
    EventsPostRequestBodyContentApplicationJsonSchemaObjectIdentifiers:
      type: object
      properties:
        ext_id:
          type: string
          description: External object ID
        id:
          type: string
          description: Internal object ID
      description: Identifiers for the object.
      title: EventsPostRequestBodyContentApplicationJsonSchemaObjectIdentifiers
    EventsPostRequestBodyContentApplicationJsonSchemaObject:
      type: object
      properties:
        identifiers:
          $ref: >-
            #/components/schemas/EventsPostRequestBodyContentApplicationJsonSchemaObjectIdentifiers
          description: Identifiers for the object.
        type:
          type: string
          description: Type of object (e.g., subscription, vehicle, etc.)
      description: >-
        Identifiers of the object record associated with this event. Ignored if
        the object type or identifier for this record does not exist on the
        account.
      title: EventsPostRequestBodyContentApplicationJsonSchemaObject
    EventsPostResponsesContentApplicationJsonSchemaCode:
      type: string
      enum:
        - invalid_parameter
        - missing_parameter
        - out_of_range
        - campaign_processing
        - campaign_sent
        - document_not_found
        - not_enough_credits
        - permission_denied
        - duplicate_parameter
        - duplicate_request
        - method_not_allowed
        - unauthorized
        - account_under_validation
        - not_acceptable
        - bad_request
        - unprocessable_entity
        - Domain does not exist
        - Contact email not found
        - Attribute not found
        - Category id not found
        - Invalid parameters passed
        - Record(s) for identifier not found
        - Returned when query params are invalid
        - Returned when invalid data posted
        - Feed not found
        - Campaign ID not found
        - api-key not found
        - DMARC policy requires domain authentication
        - DNS records not properly configured
        - Invalid OTP code provided
        - OTP code has expired
        - Domain already exists in your account
        - The sum of all IP weights must equal 100
        - Authentication failed
        - Insufficient credits
        - Request already processed
      description: Error code displayed in case of a failure
      title: EventsPostResponsesContentApplicationJsonSchemaCode
    CreateEventRequestBadRequestError:
      type: object
      properties:
        code:
          $ref: >-
            #/components/schemas/EventsPostResponsesContentApplicationJsonSchemaCode
          description: Error code displayed in case of a failure
        message:
          type: string
          description: Readable message associated to the failure
      required:
        - code
        - message
      title: CreateEventRequestBadRequestError
    CreateEventRequestUnauthorizedError:
      type: object
      properties:
        code:
          $ref: >-
            #/components/schemas/EventsPostResponsesContentApplicationJsonSchemaCode
          description: Error code displayed in case of a failure
        message:
          type: string
          description: Readable message associated to the failure
      required:
        - code
        - message
      title: CreateEventRequestUnauthorizedError
  securitySchemes:
    api-key:
      type: apiKey
      in: header
      name: api-key
      description: >-
        The API key should be passed in the request headers as `api-key` for
        authentication.

```

## SDK Code Examples

```typescript
import { BrevoClient } from "@getbrevo/brevo";

async function main() {
    const client = new BrevoClient({
        apiKey: "YOUR_API_KEY_HERE",
    });
    await client.event.createEvent({
        eventName: "video_played",
    });
}
main();

```

```python
from brevo import Brevo

client = Brevo(
    api_key="YOUR_API_KEY_HERE",
)

client.event.create_event(
    event_name="video_played",
)

```

```php
<?php

namespace Example;

use Brevo\Brevo;
use Brevo\Event\Requests\CreateEventRequest;

$client = new Brevo(
    apiKey: 'YOUR_API_KEY_HERE',
);
$client->event->createEvent(
    new CreateEventRequest([
        'eventName' => 'video_played',
    ]),
);

```

```go
package main

import (
	"fmt"
	"strings"
	"net/http"
	"io"
)

func main() {

	url := "https://api.brevo.com/v3/events"

	payload := strings.NewReader("{\n  \"event_name\": \"video_played\"\n}")

	req, _ := http.NewRequest("POST", url, payload)

	req.Header.Add("api-key", "<apiKey>")
	req.Header.Add("Content-Type", "application/json")

	res, _ := http.DefaultClient.Do(req)

	defer res.Body.Close()
	body, _ := io.ReadAll(res.Body)

	fmt.Println(res)
	fmt.Println(string(body))

}
```

```ruby
require 'uri'
require 'net/http'

url = URI("https://api.brevo.com/v3/events")

http = Net::HTTP.new(url.host, url.port)
http.use_ssl = true

request = Net::HTTP::Post.new(url)
request["api-key"] = '<apiKey>'
request["Content-Type"] = 'application/json'
request.body = "{\n  \"event_name\": \"video_played\"\n}"

response = http.request(request)
puts response.read_body
```

```java
import com.mashape.unirest.http.HttpResponse;
import com.mashape.unirest.http.Unirest;

HttpResponse<String> response = Unirest.post("https://api.brevo.com/v3/events")
  .header("api-key", "<apiKey>")
  .header("Content-Type", "application/json")
  .body("{\n  \"event_name\": \"video_played\"\n}")
  .asString();
```

```csharp
using RestSharp;

var client = new RestClient("https://api.brevo.com/v3/events");
var request = new RestRequest(Method.POST);
request.AddHeader("api-key", "<apiKey>");
request.AddHeader("Content-Type", "application/json");
request.AddParameter("application/json", "{\n  \"event_name\": \"video_played\"\n}", ParameterType.RequestBody);
IRestResponse response = client.Execute(request);
```

```swift
import Foundation

let headers = [
  "api-key": "<apiKey>",
  "Content-Type": "application/json"
]
let parameters = ["event_name": "video_played"] as [String : Any]

let postData = JSONSerialization.data(withJSONObject: parameters, options: [])

let request = NSMutableURLRequest(url: NSURL(string: "https://api.brevo.com/v3/events")! as URL,
                                        cachePolicy: .useProtocolCachePolicy,
                                    timeoutInterval: 10.0)
request.httpMethod = "POST"
request.allHTTPHeaderFields = headers
request.httpBody = postData as Data

let session = URLSession.shared
let dataTask = session.dataTask(with: request as URLRequest, completionHandler: { (data, response, error) -> Void in
  if (error != nil) {
    print(error as Any)
  } else {
    let httpResponse = response as? HTTPURLResponse
    print(httpResponse)
  }
})

dataTask.resume()
```