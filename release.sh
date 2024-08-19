#!/usr/bin/bash

npm run build

tar -czf release.tar.gz \
	appinfo/* \
	img/* \
	js/* \
	lib/* \
	templates/* \
	composer.json \
	composer.lock \
	README.md