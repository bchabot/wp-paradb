# Changelog

## [0.0.3] - 2025-12-26
### Added
- **Case Visibility & Protection**: Added Visibility settings (Public, Private/Protected, Internal) and password protection for cases.
- **Data Sanitization**: New "Sanitize Front End" option for cases to automatically redact witness/client names and specific locations on public pages.
- **Log Management**: Administrators and Team Leaders can now edit and delete field log entries directly from the back end.
- **Embedded Maps**: Added embedded map views for log entries. Clicking coordinates now opens a map modal.
- **View All on Map**: New button in Log Viewer to see all entry locations on a single map.
- **Geolocation in Witness Forms**: Added GPS location buttons and Address Book (Location) autocomplete to both public and admin witness forms.
- **Witness to Client Conversion**: Added a one-click button to create a Client record from an existing Witness Account.
- **Standalone Mobile Logging**: The "Log My Actions" view can now be opened in a standalone browser tab for a cleaner, full-screen mobile experience.
- **Configurable Log Chat**: Added settings for scrollback limit and "Enter to Send" behavior in the mobile log chat.

### Fixed
- **Nested Form Errors**: Resolved an issue where nested forms in the relationship section caused validation errors and "attachment target" requirements during case updates.
- **Witness Form Errors**: Fixed `account_address` and `incident_time` undefined array key warnings.
- **Public Submission Reliability**: Improved database insertion logic to handle empty optional fields correctly, resolving "Failed to submit witness report" errors.
- **UI Cleanup**: Removed unnecessary required markers (red stars) from phenomena selection fields.
- **UX Improvements**: Field log sections are now collapsed by default on case and activity edit pages to reduce clutter.

### Changed
- **Default Team Roles**: Updated default roles to include "Sensitivity Specialist" and "Team Owner" to match original ParaDB specifications.
- **Case Listing**: Renamed "Assignee" to "Case Manager" for clarity.
- **Version Management**: Implemented an automated database upgrade routine.

## [0.0.2] - 2025-12-26
- Team Assignments implementation.
- Mobile Log Chat enhancements.
- GitHub Actions triage fixes.

## [1.0.0] - Previous Stable
- Initial comprehensive restructuring to WordPress Standards.
- Implementation of Case, Activity, and Report management.
- Environmental data auto-fetching.
- Role-based access control.

---
* (2 September 2021). Began converting from WPBB to AP-ParaDB ~~<bchabot@gmail.com>
* (1 September 2021). Init. ~~<bchabot@gmail.com>
