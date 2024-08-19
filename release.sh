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

mkdir rolls
cp release.tar.gz rolls/

cd rolls
tar -zxf release.tar.gz
rm release.tar.gz

cd ..
tar -czf release.tar.gz rolls

rm -r rolls
	
openssl dgst -sha512 -sign ~/.nextcloud/certificates/rolls.key ./release.tar.gz | openssl base64 > release.sign