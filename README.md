## Microservice for Twogether
This application is making use of normal OOP structure while
having a simple docker environment ready for setup. Simply
follow the below from root folder

## Installing Vendor
docker run --rm --interactive --tty \
  --volume $PWD:/app \
  composer install

### Build docker
docker-compose build

## Usage
### Run command without connecting
docker-compose run --rm php-cli php init.php < employees.txt


## Connect to image / if not running it direct
docker ps
docker exec -it <container_id> /bin/sh
i.e docker exec -it 9d4928fe9534 /bin/sh

## Run file
php init.php < employees.txt
This will create a CSV file in the root directory called employees.<timestamp>.csv

## Main Features

* Importing txt file of employees.txt
* Filter input based on Name and date
* Output in CSV: Date, Number of Small Cakes, Number of Large Cakes, Names of people
  getting cake
