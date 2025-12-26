# Changelog

## [0.0.2] - 2025-12-26
### Added
- **Team Assignments**: Ability to assign multiple team members to a case with specific roles (Lead, Investigator, Researcher, etc.).
- **Team Roles Taxonomy**: Fully customizable roles for team assignments, manageable via ParaDB > Taxonomies.
- **Enhanced Mobile Log Chat**: "Log My Actions" now features automatic geolocation, rapid-entry focus management, and improved AJAX reliability.
- **Log Media Thumbnails**: Image uploads in logs are now displayed as compact thumbnails across all admin views.
- **Activity Linking in Log Book**: The public Log Book now allows linking entries to specific investigation activities.

### Fixed
- **Live Mode Refresh**: Resolved an issue where "Live Mode (tail)" in the Field Log Viewer would not refresh automatically.
- **Field Log Saving**: Fixed database errors when saving logs with empty coordinates or missing activity IDs.
- **Admin Access**: Resolved "Sorry, you are not allowed to access this page" errors when accessing the hidden mobile log chat.
- **Blank Page on Submission**: Fixed PHP syntax errors and AJAX handling that caused blank screens after submitting log entries.
- **GitHub Actions Triage**: Fixed permission and token issues in the automated triage workflows.

### Changed
- Renamed "Assigned To" to **"Case Manager"** in case views to distinguish from the broader investigation team.
- Standardized version numbering and `@since` tags.

## [1.0.0] - Previous Stable
- Initial comprehensive restructuring to WordPress Standards.
- Implementation of Case, Activity, and Report management.
- Environmental data auto-fetching.
- Role-based access control.

---
* (2 September 2021). Began converting from WPBB to AP-ParaDB ~~<bchabot@gmail.com>
* (1 September 2021). Init. ~~<bchabot@gmail.com>