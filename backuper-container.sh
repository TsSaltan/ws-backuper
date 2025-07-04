#!/bin/sh

# Base directory containing the backups
BASE_DIR="/backup"
CURRENT_DIR="$(dirname "$(realpath "$0")")"

# Check if the base directory exists
if [ ! -d "$BASE_DIR" ]; then
    echo "Backup directory $BASE_DIR does not exist."
    exit 1
fi

# Iterate through all subdirectories in the base directory
for dir in "$BASE_DIR"/*; do
    if [ -d "$dir" ]; then
        # Change to the subdirectory
        cd "$dir" || { echo "Failed to change directory to $dir"; continue; }
        
        echo "Running backup script in $dir..."
        sh -c "$CURRENT_DIR/backuper.sh"
    fi
done