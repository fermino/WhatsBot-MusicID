/*
 * WhatsBot-MusicID
 */
	#include <iostream>
	#include <fstream>

	#include <cstdlib>

	#include "gnsdk.hpp"

	using namespace std;

	using namespace gracenote;
	using namespace gracenote::metadata;
	using namespace gracenote::musicid_stream;

	// Application Version
	gnsdk_cstr_t ApplicationVersion = "0.1";

	// Output delimiter
	string Delimiter = ";";

	// DisplayResponse function

	void DisplayResponse(GnResponseAlbums Albums);

	/*
	 * Events
	 *
	 * status:<status>;<% complete>;<bytes sent>;<bytes received>
	 * processing:status:<status>
	 * identifying:status:<status>
	 * error:<api>;<code>;<description>
	 */

	class MusicIDStreamEvents : public IGnMusicIdStreamEvents
	{
	public:
		virtual void StatusEvent(GnStatus Status, gnsdk_uint32_t Complete, gnsdk_size_t Sent, gnsdk_size_t Received, IGnCancellable& Canceller)
		{
			cout << "status:" << Status << Delimiter << Complete << Delimiter << Sent << Delimiter << Received << endl;

			GNSDK_UNUSED(Canceller);
		}

		void MusicIdStreamProcessingStatusEvent(GnMusicIdStreamProcessingStatus Status, IGnCancellable& Canceller)
		{
			cout << "processing:status:" << Status << endl;

			GNSDK_UNUSED(Canceller);
		}

		void MusicIdStreamIdentifyingStatusEvent(GnMusicIdStreamIdentifyingStatus Status, IGnCancellable& Canceller)
		{
			cout << "identifying:status:" << Status << endl;

			if(Status == kStatusIdentifyingEnded)
				Canceller.SetCancel(true);
		}

		void MusicIdStreamAlbumResult(GnResponseAlbums& Albums, IGnCancellable& Canceller)
		{
			DisplayResponse(Albums);

			GNSDK_UNUSED(Canceller);
		}

		void MusicIdStreamIdentifyCompletedWithError(GnError& Error)
		{
			cout << "error:" << Error.ErrorAPI() << Delimiter << hex << Error.ErrorCode() << Delimiter << Error.ErrorDescription() << endl;
		}
	};

	/*
	 * The audio source, std::cin
	 */

	class AudioSource : public IGnAudioSource
	{
	public:
		AudioSource(gnsdk_uint32_t __SamplesPerSecond = 44100, gnsdk_uint32_t __SampleSizeInBits = 16, gnsdk_uint32_t __NumberOfChannels = 2)
		{
			_SamplesPerSecond = __SamplesPerSecond;
			_SampleSizeInBits = __SampleSizeInBits;
			_NumberOfChannels = __NumberOfChannels;
		}

		gnsdk_uint32_t SourceInit()
		{
			if(!cin.fail())
			{
				cin.seekg(44);

				return 0;
			}

			return -1;
		}

		gnsdk_size_t GetData(gnsdk_byte_t* DataBuffer, gnsdk_size_t DataSize)
		{
			cin.read((gnsdk_char_t*) DataBuffer, DataSize);

			return cin.gcount();
		}

		void SourceClose()
		{ }

		gnsdk_uint32_t SamplesPerSecond()
		{ return _SamplesPerSecond; }

		gnsdk_uint32_t SampleSizeInBits()
		{ return _SampleSizeInBits; }

		gnsdk_uint32_t NumberOfChannels()
		{ return _NumberOfChannels; }

	private:
		gnsdk_uint32_t _SamplesPerSecond;
		gnsdk_uint32_t _SampleSizeInBits;
		gnsdk_uint32_t _NumberOfChannels;
	};

	/*
	 * Storing user data
	 */

	class UserStore : public IGnUserStore
	{
	public:
		GnString LoadSerializedUser(gnsdk_cstr_t ClientID)
		{
			fstream UserRegFile;
			string FileName;
			string Serialized;
			GnString UserData;

			FileName = ClientID;
			FileName += "_user.txt";

			UserRegFile.open(FileName.c_str(), ios_base::in);

			if(!UserRegFile.fail())
			{
				UserRegFile >> Serialized;

				UserData = Serialized.c_str();
			}

			return UserData;
		}

		bool StoreSerializedUser(gnsdk_cstr_t ClientID, gnsdk_cstr_t UserData)
		{
			fstream UserRegFile;
			string FileName;

			FileName = ClientID;
			FileName += "_user.txt";

			UserRegFile.open(FileName.c_str(), ios_base::out);

			if(!UserRegFile.fail())
			{
				UserRegFile << UserData;

				return true;
			}

			return false;
		}
	};

	/*
	 * Let's do it!
	 */

	void IdentifyStream(GnUser& User, AudioSource& AudioSource)
	{
		MusicIDStreamEvents StreamEvents;
		GnMusicIdStream MusicIDStream(User, kPresetRadio, &StreamEvents);

		MusicIDStream.Options().ResultSingle(true);
		MusicIDStream.AudioProcessStart(AudioSource);
		MusicIDStream.IdentifyAlbum();
	}

	/*
	 * Show it all!
	 *
	 * match:<artist name>;<contributor name>;<album title>;<track number>;<track title>;<track duration>
	 */

	void DisplayResponse(GnResponseAlbums Albums)
	{
		album_iterator AlbumIterator = Albums.Albums().begin();

		for( ; AlbumIterator != Albums.Albums().end(); ++AlbumIterator)
			cout << "match:" << AlbumIterator->Artist().Name().Display() << Delimiter << AlbumIterator->Artist().Contributor().Name().Display() << Delimiter << AlbumIterator->Title().Display() << Delimiter << AlbumIterator->TrackMatched().TrackNumber() << Delimiter << AlbumIterator->TrackMatched().Title().Display() << Delimiter << AlbumIterator->TrackMatched().Duration() << endl;
	}

	/*
	 * Main method
	 *
	 * error:<api>;<code>;<description>
	 */

	int main(int ArgumentCount, char* Arguments[])
	{
		gnsdk_cstr_t ClientID = GNSDK_NULL;
		gnsdk_cstr_t ClientIDTag = GNSDK_NULL;

		gnsdk_cstr_t LicenseFile = GNSDK_NULL;

		gnsdk_uint32_t SamplesPerSecond = 44100;
		gnsdk_uint32_t SampleSizeInBits = 16;
		gnsdk_uint32_t NumberOfChannels = 2;

		if(ArgumentCount >= 4)
		{
			ClientID = Arguments[1];
			ClientIDTag = Arguments[2];

			LicenseFile = Arguments[3];

			// If any argument is not a number, atoi will return 0

			if(ArgumentCount >= 5)
				SamplesPerSecond = atoi(Arguments[4]);

			if(ArgumentCount >= 6)
				SampleSizeInBits = atoi(Arguments[5]);

			if(ArgumentCount >= 7)
				NumberOfChannels = atoi(Arguments[6]);

			if(ArgumentCount >= 8)
				Delimiter = Arguments[7];

			try
			{
				// Gracenote manager

				GnManager GracenoteManager(LicenseFile, kLicenseInputModeFilename);

				// Show info

				cout << "gnsdk:version:" << GracenoteManager.ProductVersion() << endl;
				cout << "gnsdk:builddate:" << GracenoteManager.BuildDate() << endl;

				/* Log (debug)
				 * GnLog Log("log", GnLogFilters().Error().Warning(), GnLogColumns().All(), GnLogOptions().MaxSize(0).Archive(false), GNSDK_NULL);
				 * Log.Enable(kLogPackageAllGNSDK);
				 */

				// User

				UserStore Store;
				GnUser User(Store, ClientID, ClientIDTag, ApplicationVersion);

				User.Options().LookupMode(kLookupModeOnline);

				// Locale

				GnLocale Locale(kLocaleGroupMusic, kLanguageEnglish, kRegionDefault, kDescriptorSimplified, User);

				Locale.SetGroupDefault();

				// Audio source

				AudioSource AudioSource(SamplesPerSecond, SampleSizeInBits, NumberOfChannels);

				// Let's do it!

				IdentifyStream(User, AudioSource);
			}
			catch(GnError& Error)
			{
				cout << "error:" << Error.ErrorAPI() << Delimiter << hex << Error.ErrorCode() << Delimiter << Error.ErrorDescription() << endl;

				return 1;
			}
		}
		else
			cout << "Usage: ./identifystream <ClientID> <ClientIDTag> [<SamplesPerSecond> <SampleSizeInBits> <NumberOfChannels> [<OutputDelimiter>]]" << endl;		

		return 0;
	}