#!/usr/bin/bash

npm install
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