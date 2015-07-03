# Changelog for Qandidate toggle API

## 0.3.x

- Replaced `igorw/config-service-provider` with `vlucas/phpdotenv`. With this method there is [more flexibility to deploy] the API
  `.env.dist` gives you the same configuration as `config.json.dist` gave you before.
  But you will need to recreate your config if you adjusted the configuration.
  Note that `TOGGLE__ALLOWED_ORIGINS` should be a JSON string and the name for the redis connection changed
  from `uri` to `dsn` (`TOGGLE__REDIS_DSN`).

[more flexibility to deploy]: http://12factor.net/config
