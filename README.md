Toggle API
==========

An API for managing your toggles, uses Redis to store the toggle collection.

![build status](https://github.com/qandidate-labs/qandidate-toggle-api/actions/workflows/ci.yml/badge.svg)

## About

Read our blog post series about this repository at:
- http://labs.qandidate.com/blog/2014/08/18/a-new-feature-toggling-library-for-php/
- http://labs.qandidate.com/blog/2014/08/19/open-sourcing-our-feature-toggle-api-and-ui/

## Installation

Install the dependencies with composer:

```
make dependencies
```

Configuration is determined based on environment variables. See the `.env.*` files.
You can override the values in the file with environment values.
The default configuration is mainly for local development.

The environment variable `TOGGLE__ALLOWED_ORIGINS` should be valid JSON. This is to allow arrays.

## Running the tests

We use PHPUnit, so to run the tests simply run:

```
docker-compose up -d
make test
```

## Running the app

With your favorite webserver (or with `php -S 127.0.0.1:1337 -t public` for local testing) point your document root to the `public` folder.

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

## License

MIT, see LICENSE.
