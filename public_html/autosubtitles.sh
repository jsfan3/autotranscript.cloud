#!/bin/bash

#Usage: autosubtitles.sh <url of mp4 video> <full path to srt file> <language>

### Start of locking code ###
PID="$$"
LOCK_FILE="/var/tmp/bash_script.lock"
SCRIPT=`basename "$0"`

messageAlreadyShown=false

while [[ -e "$LOCK_FILE" ]] || [[ $PID -gt $(pgrep -f --oldest "^(/.*)?${SCRIPT}\s") ]] ;
do

    count=$(pgrep -f "^(/.*)?${SCRIPT}\s" | wc -l)

    if [[ $count -eq 1 ]] # The lock file can be removed
    then
        rm "$LOCK_FILE"
        break
    fi

    if [[ $PID -eq $(pgrep -f --oldest "^(/.*)?${SCRIPT}\s") ]] # It's my turn
    then
        rm "$LOCK_FILE"
        break
    fi

    if $messageAlreadyShown
    then
        echo "PLEASE BE PATIENT: the server has queued other videos...<br />I'm waiting it terminates before processing yours... <alert><italic>Keep this page open! :-)</italic></alert><br /><br />"
        $messageAlreadyShown=true
    fi
    sleep 10
done

touch "$LOCK_FILE"
### End of locking code ###

### Start of the main of the script ###

VIDEO_FILE="video.mp4"
VIDEO_URL="$1"
LANGUAGE="$3"

MYDIR=$(dirname "$2")
SAVEDIR=$(pwd)

mkdir -p "$MYDIR"
cd "$MYDIR"

echo "I'm downloading the video: it can take a while, so <alert><italic>please wait...</italic></alert>"
rm -f "$VIDEO_FILE"
#echo "$VIDEO_URL"
wget -q -O "$VIDEO_FILE" "$VIDEO_URL"

exit_status=$?
if [ $exit_status -eq 0 ]; then
    echo "Download completed successfully!"
else
    echo "<error>I cannot download the file!</error>"
    exit 1
fi

echo "I'm generating the subtitles... <alert><italic>please keep this window open, it can take a long time!</italic></alert> <img src='waiting.gif' style='vertical-align: middle;' />"
/var/www/autosubfixed -C 10 -S "$LANGUAGE" -D "$LANGUAGE" -F srt "$VIDEO_FILE" &> autosub.log

exit_status=$?
if [ $exit_status -eq 0 ]; then
    echo "Subtitles generated successfully!"
    rm -f "$VIDEO_FILE"
else
    echo "<error>I cannot generate the subtitles!</error>"
    exit 1
fi

cd "$SAVEDIR"
### End of the main of the script ###

rm "$LOCK_FILE"
exit 0

