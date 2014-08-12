Toggle API
==========

An API for managing your toggles, uses Redis to store the toggle collection.

## Installation

Install the dependencies with composer:

```
$ composer install
```

The default configuration is mainly for local development. For running production you should create your own configuration file.

Copy the `config.json.dist` to `config.json` and adjust where needed.


## Running the tests

We use PHPUnit, so to run the tests simply run:

```
$ phpunit
```

## Running the app

With your favorite webserver (or with `php -S` for local testing) point your document root to the web folder.

## Endpoints

#### Retrieve the toggles

`GET /toggles`

#### Create or update a toggle

`PUT /toggles/{name}`

Example request:

```
{
   "conditions" : [
      {
         "name" : "operator-condition",
         "operator" : {
            "name" : "less-than",
            "value" : "1337"
         },
         "key" : "user_id"
      }
   ],
   "name" : "foo",
   "status" : "conditionally-active",
   "originalName" : "foo"
}
```

NOTE: PUT doesn't remove the previous toggle if you rename it. So if you want to rename _foo_ to _bar_, you would have to `PUT` _bar_ and `DELETE` _foo_.

#### Delete a toggle

`DELETE /toggles/{name}`
