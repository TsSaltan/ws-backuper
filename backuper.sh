#!/usr/bin/env bash

DIR="$(dirname "$(realpath "$0")")"
php -f "$DIR/src/backuper.php"