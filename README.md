## Installation

First install and enable [H5P repository plugin](https://github.com/studer-raimann/H5P).

### Install H5P page component plugin

Start at your ILIAS root directory
```bash
mkdir -p Customizing/global/plugins/Services/COPage/PageComponent
cd Customizing/global/plugins/Services/COPage/PageComponent
git clone https://github.com/studer-raimann/H5PPageComponent.git H5PPageComponent
```
Update and activate the plugin in the ILIAS Plugin Administration

### Dependencies
* [composer](https://getcomposer.org)
* [H5P repository plugin](https://github.com/studer-raimann/H5P)

Please use it for further development!

### Adjustment suggestions
* Adjustment suggestions by pull requests on https://git.studer-raimann.ch/ILIAS/Plugins/H5PPageComponent/tree/develop
* Adjustment suggestions which are not yet worked out in detail by Jira tasks under https://jira.studer-raimann.ch/projects/PLH5P
* Bug reports under https://jira.studer-raimann.ch/projects/PLH5P
* For external users please send an email to support-custom1@studer-raimann.ch
