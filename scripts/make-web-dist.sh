#!/bin/sh
set -e
. ./scripts/variables.sh

rm -rf ./dist
mkdir -p ./dist

split -d -b50M ./client.apk
apkparts="$(echo x0*)"
apksize="$(($(stat -c %s ./client.apk)/1000000))"

cat ./download.template.html |
	sed -e "s|{SERVER_DOMAIN}|${server_domain}|" |
	sed -e "s|{APK_PARTS}|${apkparts}|" |
	sed -e "s|{APK_SIZE}|${apksize}|" |
	sed -e "s|{BUILD_DATE}|$(date)|"
cat > ./dist/download.html

for path in apps watchfaces api micolor $apkparts
do
	if [ -e "./${path}" ]
	then cp -r "./${path}" "./dist/${path}"
	fi
done
