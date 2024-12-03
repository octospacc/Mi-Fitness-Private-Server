#!/bin/sh
set -e
. ./scripts/variables.sh

# Get the latest APK to re-patch from README
wget -O ./client.apk "$(grep -iF .apk ./README.md | head -n1 | cut -d\< -f2 | cut -d\> -f1 | sed -e 's|Developer-Instance|localhost|')"

apktool d --no-res ./client.apk
cd ./client

url="https://127.0.0.1:8443"
for file in $(grep -liFR "${url}")
do sed -i -e "s|${url}|https://${server_domain}|g" "${file}"
done

apktool b .
java -jar ../uber-apk-signer.jar --overwrite -a ./dist/client.apk
mv ./dist/client.apk ../client.apk
cd ..
