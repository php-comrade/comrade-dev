FROM mongo:3

ADD ./exporteddb  /exporteddb

CMD mongo "$MONGO_DB" --eval "db.dropDatabase()"; \
    find /exporteddb/*  -printf "%f\n" | \
    xargs -L 1 -I {} basename {} .json | \
    xargs -L 1 -I {} \
        mongoimport -d "$MONGO_DB" --host="$MONGO_HOST" --port="$MONGO_PORT" \
        --type=json --jsonArray --drop --collection {} --file /exporteddb/{}.json
