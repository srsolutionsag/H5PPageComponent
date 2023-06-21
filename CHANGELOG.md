# H5PPageComponent Changelog

## 3.1.1

- Fixed an issue where H5P contents could not be found if the parent object was requested via "goto" link, because the "
  ref_id" was missing in the URL.
- Fixed an issue where the import button did not appear if the new setting from v3.1.0 has been set to "allow".
- Fixed an issue where the renderer was exchanged if the main plugin (H5P) was not installed, which lead to a database
  error.

## 3.1.0

- Adopted new general setting from the main plugin (H5P) which allows/disallows the import of H5P contents.
- Fixed an issue where the main plugin (H5P) needed to be active in order to use this plugin, because the custom
  renderer was not exchanged.
- Fixed an issue where the main plugin (H5P) needed to be active during the update process, which lead to plugin-updates
  being broken via GUI.

## 3.0.2

- Fixed an null-pointer exception which ocurred sometimes if the requested object was not found.
- Fixed an issue where H5P contents could only be used in 'copa' ILIAS objects.

## 3.0.1

- Adopted the `ITranslator` introduced with the latest version of H5P's main plugin.
- Fixed an issue where H5P content could not be imported due to a wrong command being used.

## 3.0.0

- Added compatibility with refactored main Plugin (https://github.com/srsolutionsag/H5P).
- Replaced legacy implementation (fluxlabs) of the H5P editor integration by a custom UI component, which is available
  for UI component `Form`'s.
- Replaced legacy implementation (fluxlabs) of H5P content integrations by custom UI components.
- Removed unnecessary root-folder files (git-ignore and CI-config).
- Applied PSR-12 to the whole codebase except composer packages.
- Replaced all `filter_input` calls by ILIAS>=8 request wrappers. To maintain ILIAS<8 compatibility the implementation
  has been copied and can easily be replaced in the future.
- Replaced all legacy PHP type-casts (e.g. `intval($x)`) by proper type-casts (like `(int) $x`).

## 2.1.2

- Content will actually be removed from the database again when deleted on a content page (COPage).
- Added Captainhook config to streamline commit messages.

## 1.5.2

- Remove generate readme and auto_version_tag_ci

## 1.5.1

- Update keywords

## 1.5.0

- Changes for latest H5P repository plugin

## 1.4.2

- Update readme

## 1.4.1

- Fixes

## 1.4.0

- ILIAS 6 support
- Min. PHP 7.0
- Remove ILIAS 5.3 support

## 1.3.5

- Fix working in portfolio pages

## 1.3.4

- Improve import content

## 1.3.3

- Fixes

## 1.3.2

- Some improvments

## 1.3.1

- Some improvments

## 1.3.0

- Import/Export contents

## 1.2.1

- Fix in learning module

## 1.2.0

- Supports ILIAS 5.4
- Remove ILIAS 5.2 support

## 1.1.2

- Fix move and cut/past because bug on ILIAS

## 1.1.1

- PHPVersionChecker

## 1.1.0

- Refactoring
- Supports now correctly cloning and deleting H5P page components in ILIAS 5.3

## 1.0.0

- First version
