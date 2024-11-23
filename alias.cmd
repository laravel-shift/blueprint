::@echo off

::set PATH=%PATH%;C:\Program Files\Git\cmd

:: Laravel
DOSKEY serve= cls $T php artisan serve --host=127.0.0.1
::DOSKEY art serve=php artisan serve --host=127.0.0.1
DOSKEY art=php artisan $*
DOSKEY tinker=php artisan tinker

DOSKEY refresh=php artisan migrate:refresh --seed

DOSKEY fresh= cls $T php artisan migrate:fresh --seed

:: 2 Commands in 1 Row
DOSKEY test=cls $T php artisan test
DOSKEY testf=cls $T php artisan test --filter $*

:: PHPUnit
DOSKEY phpunit="vendor/bin/phpunit"
DOSKEY pf="vendor/bin/phpunit" --filter $*

:: Composer Dump Autoload
DOSKEY dump= cls $T composer dump-autoload
DOSKEY composerdump= cls $T composer dump-autoload
DOSKEY cdump= cls $T composer dump-autoload

:: Git
:: DOSKEY gitcommit = git commit -m $*
DOSKEY gitcommit = cls $T git add . $T git commit -m $*
DOSKEY gits = cls $T git status
DOSKEY gitl = cls $T git log
DOSKEY gitlog = cls $T git log --pretty=oneline
DOSKEY gitloguser = cls $T git log --pretty="%C(Yellow)%h  %C(reset)%ad (%C(Green)%cr%C(reset))%x09 %C(Cyan)%an: %C(reset)%s" --date=short

DOSKEY gitconfig="C:\Program Files\Sublime Text 3\sublime_text.exe" "C:\Users\DELL\.gitconfig"
DOSKEY git-save = git add .$Tgit stash save --keep-index
DOSKEY wip = git add .$Tgit commit -m "WIP"
DOSKEY gitlog = git log --pretty=oneline

:: Node.js Commands
DOSKEY ndev=npm run dev
DOSKEY nstart=npm start
DOSKEY nbuild=npm run build
DOSKEY nwatch=npm run watch
DOSKEY ntest=npm run test
DOSKEY ngenerate=npm run generate
::DOSKEY gw=gulp watch
::DOSKEY nrw=npm run watch $*
::DOSKEY nrp=npm run prod $*
::DOSKEY nrt=npm run test $*
::DOSKEY nrtdd=npm run tdd $*
::DOSKEY nr=npm run $*

:: Swagger
DOSKEY swagger= cls $T php artisan l5-swagger:generate $T echo l5-swagger:generate is completed! $T php artisan serve





:: Common directories
::DOSKEY lh=cd "C:\Program Files (x86)\Ampps\www\$*"
::DOSKEY localhost=cd "C:\Program Files (x86)\Ampps\www\$*"
::DOSKEY nodes = C:$Tcd "C:\nodes\$*"

:: Common Programs
::DOSKEY ampps="C:\Program Files (x86)\Ampps\Ampps.exe"
::DOSKEY gitcmd="C:\Program Files\Git\bin\sh.exe" --login -i

:: Trying to be Linux?
::DOSKEY ls=dir
::DOSKEY cat=type
::DOSKEY ip=ipconfig
::DOSKEY rm=rmdir /S $*$Tdel $*
::DOSKEY mkdir=mkdir $1$Tcd $1
::DOSKEY touch=copy nul $* > nul
::DOSKEY clear=cls

:: Conemu
::DOSKEY reload=cls$Tcmd cur_console
::DOSKEY new=cmd -new_console:s$*

:: PHP
::DOSKEY php5="C:\Program Files (x86)\Ampps\php-5.6\php.exe" $*
:: Edit PHP.INI file
::DOSKEY phpini="C:\Program Files\Sublime Text 3\sublime_text.exe" "C:\Program Files (x86)\Ampps\php\php.ini"

:: Windows Shutdown set seconds
::DOSKEY st=shutdown /s /t $*
:: Shutdown abort
::DOSKEY sta=shutdown /a