# RemoteEmoji Hybrid

**Combines local emoji packs with dynamic custom emojis via the Friendica API.**

## Author

- **Matthias Ebers** [Friendica Profile](https://loma.ml/profile/feb)

## Description

**RemoteEmoji Hybrid** is a Friendica addon that combines local emoji packs with the dynamic integration of custom emojis through the standard Fediverse API (`/api/v1/custom_emojis`). It allows the use of custom emojis from remote Fediverse instances while continuing to support locally installed emoji packs.

## Features

- **Local Emojis**: Loads emojis from a local `emoji_pack.json` file and renders them in posts and comments.
- **Remote Emojis**: Fetches custom emojis from remote Friendica instances via the API and inserts them dynamically.
- **Caching**: Caches remote emoji data for 12 hours to improve performance and reduce API requests.
- **Conflict Prevention**: Prevents duplicate emojis when local and remote emojis share the same shortcode.
- **Custom Rendering**: Displays emojis as 20×20 pixel images using `object-fit: contain` for consistent appearance.

## Installation

1. **Install the addon**  
   Copy the `remoteemoji` directory into the `/addon/` directory of your Friendica installation.

2. **Enable the addon**
   - Open the Friendica administration panel.
   - Navigate to **Addons**.
   - Enable the **RemoteEmoji Hybrid** addon.

## Configuration

- **Cache Duration**: Remote emojis are cached for **12 hours**. If fetching fails, an empty cache is stored for **1 hour** before another request is attempted.
- **Emoji Size**: By default, emojis are rendered at **20×20 pixels**. This can be adjusted in the source code.

## Usage

- **Local Emojis**: Use the shortcode (e.g. `:my_emoji:`) in posts or comments.
- **Remote Emojis**: Are loaded automatically when the author of a post belongs to a remote Friendica instance that provides custom emojis via the API.

---

## License

This addon is released under the **MIT License**. See [LICENSE](https://mit-license.org/) for details.
