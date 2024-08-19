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
	
openssl dgst -sha512 -sign ~/.nextcloud/certificates/rolls.key ./release.tar.gz | openssl base64 > release.sign