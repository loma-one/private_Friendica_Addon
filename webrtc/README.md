WebRTC Addon
=============

This is a quick and dirty addon to add a [webrtc][1] website as an app. As webrtc
advances so rapidly there is s a chance this addon will be obsolete. Webrtc is
a new video and audio conferencing tool that is browser to browser
communication, no need to download specific software for just conferencing.
There are many different webrtc instances and because of the technology it is
really a person 2 person communication, using the server to only signal who
wants to talk to who, the actual transfer of the audio and video is directly
between the participants.

You can test it by visiting a known webrtc instance (i.e. [live.mayfirst.org](https://live.mayfirst.org))
create a room, you should be asked to share your camera and microphone (firefox
will let you choose one or the other, whereas chrome/chromium asks for both in
one question).

If the test is successful then proceed with copying the webrtc instance you
would like to use and place it in the config window and save. Now when you
open the app it will load the webrtc instance for you to use.

[1]: https://en.wikipedia.org/wiki/WebRTC

The add-on is opened in a separate window, allowing the security settings of the respective browser to take effect. It may be necessary to allow the opening of pop-up windows in the browser.
