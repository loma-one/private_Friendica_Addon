# Description

This addon for Friendica provides an onboarding widget in the sidebar. It retrieves profiles from the local directory and suggests them to the user for discovery. Contacts that the user is already following are automatically filtered out.
Features

    - Display of up to 9 profile pictures in a compact stack design.
    - Automatic filtering of existing contacts (Relationship Status greater than 0).
    - Local caching of API data for 3 hours to save system resources.
    - Admin area to monitor cache status and manually clear the pool.
    - Timestamps for the last and next scheduled update shown in the admin panel.

### Installation

    - Copy the files into the addon/follow/ directory.
    - Activate the addon via the Friendica administration panel or the command line.
    - Each user can enable the widget for their sidebar in their individual addon settings.

### Requirements

    Enabled API interface (local directory)
