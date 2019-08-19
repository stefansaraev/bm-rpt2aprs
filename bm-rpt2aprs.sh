#!/bin/sh

cd $(dirname $0)
while : ; do
  php -q bm-rpt2aprs.php
  sleep 900 # 15min
done
