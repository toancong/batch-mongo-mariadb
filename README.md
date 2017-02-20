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
