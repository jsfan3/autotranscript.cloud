#!/bin/bash

# Usage: transcript.sh /path/video.srt

if [ ! -f "$1" ]; then
    echo "<error>Error loading the transcript!</error"
    exit 1
fi

sed -r '/^[0-9]+$/{N;d}' "$1"

exit 0
