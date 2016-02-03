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
curl --silent --location 'http://lorempixel.com/400/400/' | curl --request POST --user rest:rest --data-binary @- http://brilleappen.vm/brilleappen/file/d859ba64-c730-44fa-bb00-d2837e41720d
```
