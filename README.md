# Rolls

A template to get started with Nextcloud app development.

## Dev

```sh
TEXT_LATEST_TAG="29.0.2"

cd workspace/server/apps-extra
wget "https://github.com/nextcloud/text/archive/refs/tags/v${TEXT_LATEST_TAG}.zip"
unzip "v${TEXT_LATEST_TAG}.zip"
mv "text-${TEXT_LATEST_TAG}" "text"
rm "v${TEXT_LATEST_TAG}.zip"

# move into the plugin directory
cd text
make
```

HTTPS
https://juliushaertl.github.io/nextcloud-docker-dev/basics/ssl/