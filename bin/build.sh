#! /bin/bash

# Stop this script once an error occurs
set -e

function require()
{
    which $1 > /dev/null 2>&1 || (echo "Command '$1' not found!"; exit 1)
}

function composer_install()
{
    ${BASEPATH}/composer.phar --working-dir=$1 install --no-dev
}

require curl
require php
require bower

BINPATH=$(dirname $0)
BASEPATH="$BINPATH/.."

# Install/update composer and dependencies
if [ ! -f ${BASEPATH}/composer.phar ]; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=${BASEPATH}
fi

${BASEPATH}/composer.phar self-update

composer_install ${BASEPATH}

if [ -d "${BASEPATH}/extensions" ]; then
    for EXTENSION in ${BASEPATH}/extensions/*; do
        echo "Running composer for extension: `basename ${EXTENSION}`"

        if [ -f ${EXTENSION}/composer.json ]; then
            composer_install ${EXTENSION}
        else
            echo "Composer not configured! Skipping..."
        fi
    done
fi

# Build frontend (pushd and popd is required to allow running this script from everywhere!)
pushd ${BASEPATH}/httpdocs > /dev/null
bower install
bower prune
popd > /dev/null