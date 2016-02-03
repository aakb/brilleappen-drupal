Brilleappen
===========

Get all events:

```
http://brilleappen.vm/rest/views/events/
```

Get single event (with node id 1):

```
curl --silent --user rest:rest 'http://brilleappen.vm/node/1?_format=hal_json'
```

Add file to event:

```
curl --silent --location 'http://lorempixel.com/400/400/' | curl --request POST --user rest:rest --data-binary @- http://brilleappen.vm/brilleappen/file/d859ba64-c730-44fa-bb00-d2837e41720d
```
