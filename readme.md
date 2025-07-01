# WebServer Backuper
Make backups for your project files and databases to ZIP archive and upload to remote.
Backup configs should be stored into YAML file (*.backup.yml*) and environment (*.env*) file.

Supported remotes:
- Yandex.Disk

## Backup your project via script
1. Put configuration backup file *.backup.yml* (end *.env* is necessary) to your project directory
2. If nesessary

## Backup via docker-container
1. Put configuration backup file *.backup.yml* (end *.env* is necessary) to your project directory
2. Build the container `docker build -t ws-backuper . `
3. Mount your project directories or volumes to `/backup/{PROJECT_DIR_NAME}` and run container `docker run -it -v path/to/your/project:/backup/my_1_project/ -v path/to/your/project2:/backup/my_2_project/ ws-backuper`