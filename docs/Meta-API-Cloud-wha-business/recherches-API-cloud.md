POST :
https://graph.facebook.com/{{Version}}/{{Phone-Number-ID}}/messages

Bearer token:
{{User-Access-Token}}

exemple test avec curl:
curl -i -X POST \
  https://graph.facebook.com/v22.0/660953787095211/messages \
  -H 'Authorization: Bearer EAAQ93dlFUw4BO1ZBIcWpFAsZB0iHA4BDeRSHW9Ehz4P4ltiQjiZCzhhQf77BzTuZCLjlE25HsPfzr5dWfqmPe3Kz619R6YQ7vqXtL0ZCtgOVx5JKIZB8kcSdXW0zuFvUb9oaFZC98Yr77bqx1DBHvylpSKxUcCRgo50CgvQdAYk1y7fAr2z2ypiSA3SimVGiRtOQIUBPZASqyvp5drs9IZBPRXqMPZCioZD' \
  -H 'Content-Type: application/json' \
  -d '{ "messaging_product": "whatsapp", "to": "2250777104936", "type": "template", "template": { "name": "hello_world", "language": { "code": "en_US" } } }'