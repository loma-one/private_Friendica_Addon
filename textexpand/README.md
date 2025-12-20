# TextExpand Addon for Friendica

**TextExpand** is a performance-oriented addon for the Friendica social network. It automatically collapses long posts in the network stream based on a user-defined character limit, improving stream readability and user experience.



## Features

* **Smart Collapsing:** Automatically hides long posts behind a "show more" link.
* **Bidirectional Toggle:** Includes a "show less" link at the end of expanded posts to allow re-collapsing.
* **HTML5 Safety:** Uses advanced DOM parsing to ensure HTML tags (like forum quotes or formatting) are never broken when cutting text.
* **Multi-byte Safe:** Full support for UTF-8 characters, ensuring emojis and special symbols do not cause encoding errors.
* **User Customizable:** Each user can set their own character limit via the settings panel.
* **Zero JS Overhead:** Relies on Friendica's native `openClose()` function, removing the need for external `.js` files.

---

## Technical File Documentation & Changes

This section documents the transition from the legacy `showmore` logic to the modernized **TextExpand** structure.

### 1. `textexpand.php` (Core Logic)
The backend logic was fully refactored for stability and performance:
* **Namespace Migration:** All functions renamed from `showmore_*` to `textexpand_*`.
* **Reliable Encoding:** Implemented a Meta-Tag based UTF-8 injection for `DOMDocument` to prevent "vanishing text" bugs during parsing.
* **Visible Length Measurement:** The `textexpand_get_body_length` function now utilizes the `textContent` property of the DOM body, effectively ignoring HTML tags and measuring only what the user actually reads.
* **HTML Repair Strategy:** The `textexpand_cutitem` function wraps shortened content in a temporary `<div>` container. This forces the PHP DOM parser to automatically close any tags opened within the snippet (e.g., inside forum quotes), preventing the layout of the entire page from breaking.



### 2. `textexpand.css` (Styling)
The stylesheet was rewritten to ensure compatibility with modern, responsive Friendica themes:
* **Block Layout:** Forced `.textexpand-teaser` and `.textexpand-content` to `display: block`. This prevents "inline-sticking" where the toggle link appears in the middle of a sentence.
* **Link Styling:** Enhanced the `.textexpand-wrap` class with bold fonts and specific margins to improve touch-target size for mobile users.
* **Visual Separation:** The `.textexpand-less` class adds a dashed top border at the end of long posts to visually signal the end of the content.

### 3. `settings.tpl` (User Interface)
The template was updated to sync with the new configuration keys:
* **ID Synchronization:** Updated `$enabled` and `$chars` variables to match the new database keys.
* **Standardized UI:** Uses native Friendica CSS classes (`settings-block`, `settings-submit-wrapper`) for a seamless look within the admin panel.

---

## Installation

1.  Navigate to your Friendica `addon` directory.
2.  Create a folder named `textexpand`.
3.  Upload `textexpand.php`, `textexpand.css`, and `settings.tpl` into that folder.
4.  Activate the addon in the Friendica Admin panel.

## Configuration

Navigate to **Settings -> Addon Settings -> TextExpand**:
* **Enable TextExpand:** Toggle the addon on or off.
* **Character Limit:** Define the threshold for collapsing (Default: 1100 characters).
