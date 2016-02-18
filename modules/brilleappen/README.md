Brilleappen
===========

Get all events:

```
curl --silent http://brilleappen.vm/rest/views/events/
curl --silent http://brilleappen.vm/rest/views/events/?_format=xml
```

Get single event (with node id 1):

```
curl --silent 'http://brilleappen.vm/node/1?_format=hal_json'
curl --silent 'http://brilleappen.vm/node/1?_format=xml'
```

Add file to event (with uuid d859ba64-c730-44fa-bb00-d2837e41720d):

```
curl --silent --location 'http://lorempixel.com/400/400/' | curl --request POST --user rest:rest --data-binary @- http://brilleappen.vm/brilleappen/event/d859ba64-c730-44fa-bb00-d2837e41720d/file?type=image/jpeg
```

On success, the json response will look like this:

```
{
  "status": "OK",
  "message": "Media added to event \"The first event\"",
  "media_id": "0ccf2af3-ba5d-429d-8604-4d5f39715c1a",
  "notify_url": "http://brilleappen.hulk.aakb.dk/brilleappen/event/09c4846b-c3f9-44d6-b100-ad4eb5dd557b/notify/0ccf2af3-ba5d-429d-8604-4d5f39715c1a",
  "shareMessages": null
}
```

You can also share the uploaded file through the sharing methods defined on the Event (currently "instagram", "twitter", "email") by adding "share=yes":

```
curl --silent --location 'http://lorempixel.com/400/400/' | curl --request POST --user rest:rest --data-binary @- http://brilleappen.vm/brilleappen/event/d859ba64-c730-44fa-bb00-d2837e41720d/file?type=image/jpeg&share=yes
```

On success, the response looks like this:

```
{
  "status": "OK",
  "message": "Media added to event \"The first event\"",
  "media_id": "0ccf2af3-ba5d-429d-8604-4d5f39715c1a",
  "notify_url": "http://brilleappen.hulk.aakb.dk/brilleappen/event/09c4846b-c3f9-44d6-b100-ad4eb5dd557b/notify/0ccf2af3-ba5d-429d-8604-4d5f39715c1a",
  "shareMessages": {
    "twitter": "OK",
    "email": "OK",
  }
}
```

In this case, the file has succesfully been shared on Twitter and a notification email has been sent.

## Notifications

By POST'ing to the notify_url returned, you can share a previously uploaded media file

```
curl --request POST --user rest:rest http://brilleappen.vm/brilleappen/event/09c4846b-c3f9-44d6-b100-ad4eb5dd557b/notify/82c6cfea-bcee-458b-bc00-a52277a565e5
```

and get this response

```
{
  "status": "OK",
  "shareMessages": {
    "twitter": "OK",
    "email": "OK",
  }
}
```

Create a new Event:

```
curl --request POST --user rest:rest http://brilleappen.vm/brilleappen/event/create --data-binary @- <<'EOF'
{
	"title": "The event title"
}
EOF
```

Response on success:

```
{
  "status": "OK",
  "url": "http://brilleappen.vm/node/118?_format=json",
}
```

You can also pass an Event type, e.g. "breakdown"
```
curl --request POST --user rest:rest http://brilleappen.vm/brilleappen/event/create --data-binary @- <<'EOF'
{
	"title": "Oh, no!",
	"type": "breakdown"
}
EOF
```
