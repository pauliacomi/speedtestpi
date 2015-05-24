# speedtestpi
A script that checks latency, upload and download from speedtest servers. Written in php and used on a raspberry pi.

## Usage

First run the gen_upload_files bash script to generate incrementing sized upload files. Then run the script by using 

php speedtest.php 

from the command line. The script requires dependencies in the form of curl and php. Results are logged in the /data/log.txt file

This script has been adapted from Janhouse
