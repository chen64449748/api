#!/bin/sh

#创建日志文件夹及定义日志文件名称
dir_today="/data1/log/cli/agent/user/goods.addnew.check.gwsd/`date +%Y/%m`"
log_path="/data1/log/cli/agent/user/goods.addnew.check.gwsd/`date +%Y/%m/%d`.log"
/bin/mkdir -p $dir_today > /dev/null 2>&1

count=`/bin/ps -ef | grep /shell/user/goods.addnew.check.php | grep -v grep | grep domain=gwsd.bookuu.com | wc -l`

file_uri="/shell/user/goods.addnew.php"
zc=`/bin/ps -ef | grep '/usr/local/php5/bin/php' | grep $file_uri | grep -v grep | grep domain=gwsd.bookuu.com | wc -l`
if [[ $zc -gt 0 ]]; then
    /bin/ps -ef | grep '/usr/local/php5/bin/php' | grep $file_uri | grep -v grep | grep domain=gwsd.bookuu.com | awk '{print $2}' | xargs kill -9
fi



if [[ $count -lt 1 ]]; then
    /usr/local/php5/bin/php /data/agent/system/shell/user/goods.addnew.check.php --domain=gwsd.bookuu.com >> $log_path &
fi;
