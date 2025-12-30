## NextCStorage for Friendica

**NextCStorage** is a Friendica addon that automatically mirrors your uploaded media (images and files) to an external cloud storage provider using **Open Cloud Mesh (OCM)** and **WebDAV**.

It ensures that every photo or file you upload to Friendica is safely backed up in a specific directory on your Nextcloud, ownCloud, or any OCM-compatible storage.

---

### Features

* **Zero-Config Discovery**: Automatically finds your Cloud's WebDAV endpoint using Open Cloud Mesh (OCM) just by entering your Cloud ID (e.g., `user@example.com`).
* **Smart Fallback**: If OCM discovery is restricted by firewalls or SSL issues, the addon automatically falls back to standard Nextcloud/ownCloud path structures.
* **Automatic Backup**: Seamlessly hooks into Friendica's upload process to mirror files instantly.
* **Custom Target Directory**: Define a specific folder in your cloud for backups (e.g., "Friendica_Uploads").
* **Auto-Provisioning**: Automatically creates the target folder in your cloud during the first setup.
* **Bootstrap 3 UI**: Native look and feel within the Friendica user settings.
* **Secure Disconnect**: Allows users to wipe their cloud credentials from the Friendica server at any time.

---

### Installation

1.  Navigate to your Friendica `addon/` directory.
2.  Create a folder named `nextcstorage`.
3.  Upload the add-on files to this folder
4.  Go to your Friendica Admin Panel -> Addons.
5.  Find NextCStorage in the list and click Enable.

---

### Configuration

Every user can link their own private cloud:

1. Go to Settings -> Addon Settings -> NextCStorage.
2. Cloud ID: Enter your full Cloud ID (e.g., username@my-cloud-provider.com).
3. App Password:

    * **Recommendation**: Do not use your main login password.
    * Go to your Nextcloud/ownCloud Settings -> Security and create a new App Password (Token) named "Friendica".

4. Target Directory: Enter the name of the folder where you want to store your files (e.g., Friendica).
5. Save: The system will verify the connection and create the folder for you.

---

Technical Requirements

* **Friendica:** Version 2025.x or newer (utilizing DI container and PSR-3 logging).
* **PHP:** allow_url_fopen must be enabled.
* **SSL:** Works best with valid SSL certificates, though it includes a fallback for self-signed certificates.
        
---

### Troubleshooting

* **Status "Discovery failed":** Double-check your Cloud ID format. Ensure your cloud provider supports OCM or standard WebDAV access.
* **Uploads not appearing:** Check the Friendica system logs at view/log/friendica.log. Look for entries tagged with NextCStorage.
* **Permission Denied:** Ensure the App Password has sufficient permissions to create folders and upload files.
        
---

License
This project is licensed under the MIT License.
