#!/bin/bash
	cd ./c++/
	make
	cd ../

	echo Copying files...

	# Directories

	mkdir ./build
	mkdir ./build/MusicID

	# C++ app

	cp ./c++/lib*            ./build/MusicID/
	cp ./c++/identifystream  ./build/MusicID/

	# PHP app

	cp ./php/*.php               ./build/MusicID/

	# Ready!

	cd ./build/

	echo
	echo Now copy ./build/* to WhatsBot/tmp/ and run install.php