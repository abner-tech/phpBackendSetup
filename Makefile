include .env


## db/psql: connect to the database using psql (terminal)
.PHONY: db/psql
db/psql: 
	psql ${testingDB}

## db/migrations/new name=$1: create a new database migration
.PHONY db/migrations/new:
	@echo 'creating migration fles for ${name}...'
	migrate create -seq -ext=.sql -dir=./migrations ${name}

## db/migrations/up: apply all up database migrations
.PHONY: db/migrations/up
db/migrations/up:
	@echo 'Running UP migrations...'
	migrate -path ./migrations -database ${testingDB} up

.PHONY: db/migrations/down
db/migrations/down:
	@echo 'Running DOWN migrations...'
	migrate -path ./migrations -database ${testingDB} down


.PHONY: db/migrations/version
db/migrations/version:
	@echo 'Checking current database migration version.....'
	migrate -path ./migrations -database ${testingDB} version

.PHONY: db/migrations/force
db/migrations/force:
	@echo 'Chenging current database migration version to ${version}'
	migrate -path ./migrations -database ${testingDB} force ${version}

.PHONY: db/migrations/goto
db/migrations/goto:
	@echo 'ROlling back to version: ${version}'
	migrate -path ./migrations -database ${testingDB} goto ${version}

# .PHONY: db/migrations/down
# db/migrations/step_down:
# 	@echo 'Reverting migrations ${num} steps back'
# 	migrate -path ./migrations -database ${testingDB} down ${num}