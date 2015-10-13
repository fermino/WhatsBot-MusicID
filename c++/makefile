# WhatsBot-MusicID makefile

	sdkdepends = musicid_stream lookup_local lookup_localstream storage_sqlite dsp manager

	appname = identifystream

	build_dir = ../../builds

	include $(build_dir)/platform_vars.mk

	# C++ wrapper

	extra_srcs += ../../wrappers/gnsdk_cplusplus/src_wrapper ../../wrappers/gnsdk_cplusplus/src_helpers

	# Audio lib

	extra_srcs += ../../xtralibs/bass

	# IPP lib

	extra_srcs += ../../xtralibs/ipp

	include $(build_dir)/rules_samples.mk

	# Platform dependencies
	ifeq ($(IMPLDIR),win)
		platformlibs += winmm.lib
	else ifeq ($(IMPLDIR), mac)
		platformlibs += -framework AudioToolbox
	endif