#!/usr/bin/env bash
dir=$(cd $(dirname "${BASH_SOURCE[0]}") && pwd)

cd $dir
sass --watch style.scss:style.css
