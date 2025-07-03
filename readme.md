# WebServer Backuper
Make backups for your project files and databases to ZIP archive and upload to remote.
Backup configs should be stored into YAML file (*.backup.yml*) and environment (*.env*) file.

Supported remotes:
- Yandex.Disk ([how to get access-token](https://github.com/tssaltan/php-server-backup?tab=readme-ov-file#yandexdisk))

## Backup your project using *script*
1. Put configuration backup file *.backup.yml* (and *.env* is necessary) to your project directory
2. Run *backuper.sh* from your project directory

## Backup your project using *docker container*
1. Put configuration backup file *.backup.yml* (and *.env* is necessary) to your project directory
2. Build the container `docker build -t ws-backuper . `
3. Mount your project directories or volumes to `/backup/{PROJECT_DIR_NAME}` and run container
```bash
docker run -it -v path/to/your/project:/backup/my_1_project/ -v path/to/your/project2:/backup/my_2_project/ ws-backuper
```
4. Add task to crontab: 
```bash
crontab -e
```
Put your preferred schedule
```
0 2 * * * cd /path/to/your/project && docker run -it -v path/to/your/project:/backup/my_1_project/ -v path/to/your/project2:/backup/my_2_project/ ws-backuper
```

## Backup your project using *docker container* via *docker compose*
1. Put configuration backup file *.backup.yml* (and *.env* is necessary) to your project directory
2. Add service to your compose file:
```yaml
    backuper:
        container_name: backuper
        restart: never
        image: tssaltan/ws-backuper:latest
        volumes:
            -  ./projects/my_project1:/backup/my_project1:Z
            -  ./projects/my_project2:/backup/my_project2:Z
```
3. Run compose service: `docker compose up backuper` (use *--build* param for first start)
4. Add task to crontab: 
```bash
crontab -e
```
Put your preferred schedule
```
0 2 * * * cd /path/to/your/project && docker compose up --abort-on-container-exit backuper
```