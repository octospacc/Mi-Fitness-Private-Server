# Mi Fitness Private Server

_Want to get started quick? Download the pre-patched APK: <https://github.com/octospacc/Mi-Fitness-Private-Server/releases/latest/download/Mi-Fitness-Private-Server-v1-Developer-Instance.apk>._

Reverse engineering effort to create a private, self-hostable reimplementation of the Mi Fitness API and CDN servers. This will allow usage of the official app in complete privacy and with many extra features.

Project roadmap and currently implemented features are as follows (suggestions and help is welcome).

General:

* [ ] Login / Signup
* [ ] User and device data sync

Device configuration:

* [ ] Mi Band 9

Watches and bands:

* [x] Watchface gallery
    * [x] Installing
    * [ ] Featured content
    * [ ] Categories
* [x] Watchface management
    * [x] Applying
    * [x] Uninstalling
    * [ ] In-app watchface editing
* [x] Apps gallery and management

We need help! Feel free to contribute with either documentation, code, or testing. We also need some hands to fill up the repository with external content.

## Usage

### Server Setup (optional)

Get `index.php` and set it up on a web server (even just localhost on your phone) with a dedicated domain and HTTPS encryption with a trusted certificate. (Please search on the web for how to setup a PHP server application and how to work with HTTPS.) Optionally download all the other files in this repo, like watchfaces and apps, from both the `main` and the `bin` git branch, in order to have a pre-filled server from the start.

This step is optional, as you can just use our main online instance of the server, without hosting anything yourself, if you are ok with the limitations (eg. you can't add custom files for download, since you're not the admin).

### Client App Setup (mandatory)

To use the private server, you need to either intercept and redirect traffic on your mobile device from the official server to the private one, or you can use a modified version of the mobile app.

On Android, the latter is the easiest option. Multiple flavours of pre-patched APKs are available for you to just download and install: a version that connects to our main developer instance (`...Developer-Instance.apk`), and one that connects to `https://127.0.0.1:8443` (`...localhost.apk`).

Pre-patched APKs are available in GitHub releases; latest at <https://github.com/octospacc/Mi-Fitness-Private-Server/releases/latest>. You must first uninstall your current Mi Fitness app to install the modified version!

#### Personally Patching Mi Fitness

In case you want to reproduce a patched APK yourself, which is also needed if you want to make the app connect to your own server on an address other than localhost, these are the instructions. Required tools are a UNIX shell and [Apktool](https://apktool.org).

Note: for now only m0tral's APK has been used as a base to make our patched APKs, since it has some modifications which make it easier to work with on the server side, and has some region-specific features unlocked compared to the official app. (After the patch, the app doesn't connect to its private server anymore, and as such only the content provided by our private server is available.) Any help for adapting this to work with the official Mi Fitness app is welcome.

0. Get a supported APK; latest tested is `v3.33.6i m0tral v1.39` <https://miwatch.conversmod.ru/micolor/app/latest_v139>
1. Decompile the app's smali code: `apktool d --no-res THE_ORIGINAL_FILE.apk`
2. Move into the newly-created folder with the decompiled app
3. Change all URL strings from the official CDN domains with your own (starting with `https://`):
    ```sh
    for subdomain in www miwatch; do
        url="https://${subdomain}.conversmod.ru"
        for file in $(grep -liFR "${url}"); do
            sed -i -e "s|${url}|YOUR_OWN_SERVER_URL|g" "${file}"
        done
    done
    ```
4. Recompile the app into an APK with `apktool b .`
5. Sign the APK for installation; you can use [Uber Apk Signer](https://favr.dev/opensource/uber-apk-signer/): `java -jar uber-apk-signer.jar -a dist/THE_ORIGINAL_FILE.apk`

## Credits and Copyright

Mi Fitness is the proprietary application of Xiaomi and fully their copyright, as is m0tral's APK with his modifications his copyright. We are not affiliated with either, and these parts are not redistributed in any branch of the repository. Our own modifications are fully documented and can be recreated independently.

The `bin` branch of the repo hosts some extra precompiled assets, not needed for the base functionality of the server, but solely provided for convenience and preservation of specific extra features (eg. apps and watchface galleries). Following a fair-use ideal, only public files available for free are uploaded, and credits are provided inside the files and/or in [ThirdPartyContent.md](./ThirdPartyContent.md). Please open an issue if you feel like this infringes on your copyright and we will take down the illicit content.

The private server code is fully original and my own copyright. It will be later released under a free software license to be determined.

