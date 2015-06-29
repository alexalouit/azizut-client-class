azizut-client-class
===================

PHP Client class for azizut shorten service

# Usage


## Engage connection
```
require_once 'classes/class.azizut.php';
$azizut = new azizut("server", "username", "password");
```

## Insert a link:

OOP style:
```
$azizut->url = "link to shorten";
$azizut->insert();
print $azizut->shorturl; // or $azizut->link (complete url)
```

Procedural style:
```
$azizut->insert("link to shorten");

print_r($azizut->shorturl); // or $azizut->link (complete url)
```

## Delete a link by shorturl / by longurl (slowest as by shortlink, attention may be multiple!):

OOP style:
```
$azizut->shorturl = "shorturl"; // or $azizut->url = "url";
$azizut->delete();
if($azizut->valid) {
	print "ok";
} else {
	print "problem";
}
```

Procedural style:
```
if($azizut->delete("shorturl")) { // or if($azizut->delete("", "url")) {
	print "ok";
} else {
	print "problem";
}
```

## Get a link by shorturl / last by long url

OOP style:
```
$azizut->shorturl = "shorten"; // or $azizut->url = "url";
$azizut->get();
print_r($azizut->response->data);
```

Procedural style:
```
print_r($azizut->get("shorten")); // or print_r($azizut->get("", "url"));
```

## Get links list:

OOP style:
```
$azizut->get();
print_r($azizut->response->data);
```

Procedural style:
```
print_r($azizut->get());
```

## Get link/links stats:

OOP style:
```
//$azizut->shorturl = "shorten"; // or $azizut->url = "url";
$azizut->stats = TRUE;
$azizut->get();
print_r($azizut->response->data);
```

Procedural style:
```
//print_r($azizut->get("shorten", "", TRUE)); // or print_r($azizut->get("", "url", TRUE));
print_r($azizut->get("", "", TRUE));
```

## Get links list with pagination:

OOP style:
```
$azizut->start = (int);
$azizut->limit = (int);
$azizut->get();
print $azizut->response->data;
```

Procedural style:
```
print_r($azizut->get("", "", "", $start, $limit));
```

## Update a link:

Coming soon..


In OOP, see $azizut->response for complete server response
