#!/bin/sh
set -e

git clone --depth 1 --branch bin https://gitlab.com/octospacc/Mi-Fitness-Private-Server ./resources.tmp
mv ./resources.tmp/apps ./apps
mv ./resources.tmp/watchfaces ./watchfaces
