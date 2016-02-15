#!/usr/bin/env bash
dir=$(cd $(dirname "${BASH_SOURCE[0]}") && pwd)

cd $dir
sass --watch --compass style.scss:style.css
