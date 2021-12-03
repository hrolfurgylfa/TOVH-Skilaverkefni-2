mkdir db_backups > /dev/null 2>&1 && ddev export-db --gzip=false | gzip > "db_backups/backup_$(date "+%F_%H-%M-%S").gz"
