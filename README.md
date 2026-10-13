# prospecterapp

# Requirements

docker          : 20+ with the compose plugin (`docker compose`)
make

Add in your /etc/hosts :

127.0.0.1 local.prospecter.io
127.0.0.1 static.local.prospecter.io

# Build env (images, composer, yarn, assets, database + fixtures)
make install

# Launch the stack
make start

Then open https://local.prospecter.io (self-signed certificate) and log in with `prospector` / `password`.

# Stop the stack
make stop

# Tests
make test
