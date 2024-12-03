#!/bin/sh
set -e

# Read static server domain from INI to shell variable
eval "$(sed -e 's| ||g' ./static.ini | grep -iF server_domain=)"
