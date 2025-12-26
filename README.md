# WordPress Paranormal Database (WP-ParaDB)

WP-ParaDB is a comprehensive, professional-grade WordPress plugin designed for paranormal researchers, parapsychologists, and investigation teams. It provides a standardized and secure platform for recording, archiving, and sharing paranormal witness reports, field observations, experiments, and detailed investigation cases.

## 🌟 Key Features

*   **Case Management:** Organize investigations into comprehensive cases with linked activities, reports, and evidence.
*   **Activity Logging:** Detailed tracking of site visits, interviews, and experiments.
*   **Environmental Data Integration:** Automatically fetch weather, astronomical (moon phase), astrological (planetary transits), and geomagnetic (Kp-Index) data based on location and time.
*   **Evidence Handling:** Securely upload and link photos, audio, and video files to cases and activities.
*   **Public Witness Submissions:** A customizable public form for witnesses to submit their experiences directly to your database.
*   **Privacy & Redaction:** Built-in tools to automatically or manually redact sensitive information from public-facing reports.
*   **Role-Based Access:** Specialized user roles (Director, Team Leader, Investigator) to manage team permissions.
*   **Data Maintenance:** Integrated tools for data backup (JSON), restoration, and complete scrubbing on uninstall.

## 🚀 Installation

1.  Download the latest release from the [releases page](https://github.com/bchabot/wp-paradb/releases).
2.  Upload the `wp-paradb` folder to your `/wp-content/plugins/` directory.
3.  Activate the plugin through the 'Plugins' menu in WordPress.
4.  Navigate to **ParaDB > Settings** to configure your API keys and default options.

## 📖 Usage & Shortcodes

Full documentation is available directly within the WordPress admin under **ParaDB > Documentation**.

### Main Shortcodes:
*   `[paradb_cases]` - Displays a searchable grid of all published cases.
*   `[paradb_single_case id="123"]` - Displays full details for a specific case.
*   `[paradb_witness_form]` - Displays the public witness submission form.
*   `[paradb_log_book]` - Displays a chronological log of all published activities and reports.

## 👥 Credits & Acknowledgments

WP-ParaDB is based on the original concepts and workflow of the **[ParaDB](https://github.com/szarkos/paradb)** project.

We would like to extend our sincere thanks to **Stephen Zarkos** ([szarkos](https://github.com/szarkos)) for the original idea and the significant work he put into the original ParaDB software. His contribution provided the inspiration and foundation for this WordPress implementation.

## 📄 License

This plugin is licensed under the [GPL v3 or later](LICENSE).

---

Developed and maintained by **Brian Chabot** ([brianchabot.org](https://brianchabot.org/)) 🔍👻