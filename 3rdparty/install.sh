#!/bin/sh

#cd $1

PROGRESS_FILE=/tmp/jeedom/JeedomConnect/dependance
touch ${PROGRESS_FILE}
echo 0 > ${PROGRESS_FILE}
echo "Lancement de l'installation/mise à jour des dépendances JeedomConnect"

BASEDIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
cd ${BASEDIR}

echo "Download composer installer"
echo 10 > ${PROGRESS_FILE}

EXPECTED_CHECKSUM="$(wget -q -O - https://composer.github.io/installer.sig)"
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"

if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]
then
    >&2 echo 'ERROR: Invalid installer checksum'
    rm composer-setup.php
	rm composer.lock
    exit 1
fi

echo "Install composer"
echo 50 > ${PROGRESS_FILE}

php composer-setup.php --quiet
RESULT=$?
rm composer-setup.php
echo "Install packages"
echo 75 > ${PROGRESS_FILE}

php composer.phar install

echo 100 > ${PROGRESS_FILE}
echo "Everything is successfully installed!"

rm composer.lock
rm ${PROGRESS_FILE}

