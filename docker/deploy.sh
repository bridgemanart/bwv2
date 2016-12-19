#!/usr/bin/env bash

docker rm -f $(docker stop $(docker ps -aq))
docker volume rm $(docker volume ls)

docker-compose up --build &
sleep 20

chmod 777 ./../deploy.sh
docker exec -it docker_phpfpm_1 ./deploy.sh

sleep 20
docker ps
echo "----------------------> Ready to work! <----------------"