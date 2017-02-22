# batch-mongo-mariadb

# Run local env

1. Run docker-compose with `-d`
  ```
  docker-compose up -d
  ```

2. Import mongo collections (only first time)
  ```
  docker exec -it <mongo container name> mongorestore --nsInclude '*'  --db dbmongo  /data/dbmongo
  ```

# Test

  ```
  composer test
  ```

# Run job
Set a name then run code. There are many ways to set a name job.
Priority to get name a job: argument in console, parameter in function, value in .env

1. Set name job in console with param `--job`

  Example:
  ```
  php index.php --job ShopsSimpleJob
  ```

2. Set name job in code.

  Example:
  ```
  \BatchMongoRDB\Core\JobRunner::run('ShopsSimpleJob');
  ```

3. Set name in .env

  Example
  ```
  DEFAULT_JOBS=ShopsSimpleJob
  ```
