# newsblur-export

## Unmaintained

Note that this project is no longer maintained. If I ever come back to it, it will be to rewrite it from scratch, most likely using node.js, and that would be a new project.

## Original description

(Unofficial) NewsBlur saved stories exporter

Exports your saved stories from https://www.newsblur.com/

## Usage
If you use the source from git, you'll need to download the dependencies using Composer.

Copy newsblur.ini.example to newsblur.ini, edit that file and add your own username and password.

Then run:

php export.php

If all goes well, your saved stories will be exported to a single file named starred_stories.json
